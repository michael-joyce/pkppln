<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Journal;
use AppBundle\Entity\TermOfUseRepository;
use AppBundle\Exception\SwordException;
use AppBundle\Services\BlackWhitelist;
use AppBundle\Utility\Namespaces;
use DateTime;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * SWORD v2 Controller to receive deposits.
 * 
 * @Route("/api/sword/2.0")
 */
class SwordController extends Controller {

    /**
     * Human-readable descriptions of the LOCKSS states.
     *
     * @var array
     */
    private static $lockssStates = array(
        'received' => 'LOCKSS is aware of the deposit.',
        'syncing' => 'LOCKSS boxes are downloading the deposit.',
        'agreement' => 'The PKP LOCKSS network agrees internally on content checksums.',
        'unknown' => 'The deposit is not known to LOCKSS.'
    );
    
    /**
     * Human-readable descriptions of the staging server processing states.
     *
     * @var array
     */
    private static $processingStates = array(
        'received' => 'The PLN has downloaded the deposit file.',
        'validated' => 'The PLN has validated the checksums and OJS export XML.',
        'sent' => 'The PLN has notified LOCKSS that the deposit is ready.',
        'unknown' => 'The deposit is in an unknown state.'
    );

    /**
     * Parse an XML string, register the namespaces it uses, and return the
     * result.
     *
     * @param string $content
     * @return SimpleXMLElement
     */
    private function parseXml($content) {
        $xml = new SimpleXMLElement($content);
        $ns = new Namespaces();
        $ns->registerNamespaces($xml);
        return $xml;
    }

    /**
     * Fetch an HTTP header. Checks for the header name, and a variant prefixed
     * with X-, and for the header as a query string parameter.
     *
     * @param Request $request
     * @param string $name
     * @return string|null
     */
    private function fetchHeader(Request $request, $name) {
        if ($request->headers->has($name)) {
            return $request->headers->get($name);
        }
        if ($request->headers->has("X-" . $name)) {
            return $request->headers->has("X-" . $name);
        }
        if ($request->query->has($name)) {
            return $request->query->get($name);
        }
        return null;
    }

    /**
     * Check if a journal's uuid is whitelised or blacklisted. The rules are:
     *
     * If the journal uuid is whitelisted, return true
     * If the journal uuid is blacklisted, return false
     * Return the pln_accepting parameter from parameters.yml
     *
     * @param string $journal_uuid
     * @return boolean
     */
    private function checkAccess($journal_uuid) {
        /** @var BlackWhitelist */
        $bw = $this->get('blackwhitelist');
        $this->get('monolog.logger.sword')->info("Checking access for {$journal_uuid}");
        if ($bw->isWhitelisted($journal_uuid)) {
            $this->get('monolog.logger.sword')->info("whitelisted {$journal_uuid}");
            return true;
        }
        if ($bw->isBlacklisted($journal_uuid)) {
            $this->get('monolog.logger.sword')->notice("blacklisted {$journal_uuid}");
            return false;
        }
        return $this->container->getParameter("pln_accepting");
    }
	
    /**
     * The journal with UUID $uuid has contacted the PLN. Add a record for the 
     * journal if there isn't one, otherwise update the timestamp.
     * 
     * @param string $uuid
     * @param string $url
     * @return Journal
     */
	private function journalContact($uuid, $url) {
		$em = $this->getDoctrine()->getManager();
		$journalRepo = $em->getRepository('AppBundle:Journal');
		$journal = $journalRepo->findOneBy(array(
			'uuid' => $uuid
		));
		if($journal !== null) {
			$journal->setTimestamp();
		} else {
            $journal = new Journal();
            $journal->setUuid($uuid);
            $journal->setUrl($url);
			$journal->setTimestamp();
            $journal->setTitle('unknown');
            $journal->setIssn('unknown');
            $journal->setStatus('new');
            $journal->setEmail('unknown@unknown.com');
            $em->persist($journal);            
        }
		if($journal->getStatus() !== 'new') {
			$journal->setStatus('healthy');
		}
        $em->flush($journal);
        return $journal;
	}
	
    /**
     * Fetch the terms of use from the database.
     * 
     * @todo does this really need to be a function?
     * 
     * @return TermOfUse[]
     */
	private function getTermsOfUse() {
        $em = $this->getDoctrine()->getManager();
        /** @var TermOfUseRepository */
        $repo = $em->getRepository("AppBundle:TermOfUse");
        $terms = $repo->getTerms();
		return $terms;
	}
    
    private function getNetworkMessage(Journal $journal) {
        if($journal->getOjsVersion() === null) {
            return $this->container->getParameter('network_default');
        }
        if(version_compare($journal->getOjsVersion(), $this->container->getParameter('min_ojs_version'), '>=')) {
            return $this->container->getParameter('network_accepting');
        }
        return $this->container->getParameter('network_oldojs');
    }

    /**
     * Return a SWORD service document for a journal. Requires On-Behalf-Of
     * and Journal-Url HTTP headers.
     *
     * @Route("/sd-iri", name="service_document")
     * @Method("GET")
     */
    public function serviceDocumentAction(Request $request) {
        /** @var LoggerInterface */
        $logger = $this->get('monolog.logger.sword');

        $obh = $this->fetchHeader($request, "On-Behalf-Of");
        $journalUrl = $this->fetchHeader($request, "Journal-Url");

        $accepting = $this->checkAccess($obh);
        $acceptingLog = 'not accepting';
        if ($accepting) {
            $acceptingLog = 'accepting';
        }
		
        $logger->notice("service document - {$request->getClientIp()} - {$obh} - {$journalUrl} - {$acceptingLog}");
        if ($obh === null) {
			throw new SwordException(400, "Missing On-Behalf-Of header for {$journalUrl}");
        }
        if ($journalUrl === null) {
			throw new SwordException(400, "Missing Journal-Url header for {$obh}");
        }
		
		$journal = $this->journalContact($obh, $journalUrl);
        
        /** @var Response */
        $response = $this->render("AppBundle:Sword:serviceDocument.xml.twig", array(
            "onBehalfOf" => $obh,
            "accepting" => $accepting ? 'Yes' : 'No',
            'message' => $this->getNetworkMessage($journal),
            "colIri" => $this->generateUrl(
                "create_deposit", 
				array("journal_uuid" => $obh), 
				UrlGeneratorInterface::ABSOLUTE_URL
            ),
            "terms" => $this->getTermsOfUse(),
        ));
        /** @var Response */
        $response->headers->set("Content-Type", "text/xml");
        return $response;
    }

    /**
     * Create a deposit.
     *
     * @Route("/col-iri/{journal_uuid}", name="create_deposit")
     * @Method("POST")
     */
    public function createDepositAction(Request $request, $journal_uuid) {
        /** @var LoggerInterface */
        $logger = $this->get('monolog.logger.sword');
		
        $accepting = $this->checkAccess($journal_uuid);
        $acceptingLog = 'not accepting';
        if ($accepting) {
            $acceptingLog = 'accepting';
        }
		
		$logger->notice("create deposit - {$request->getClientIp()} - {$journal_uuid} - {$acceptingLog}");
		if(! $accepting) {
			throw new SwordException(400, "Not authorized to create deposits.");
		}
		
        if ($this->checkAccess($journal_uuid) === false) {
            $logger->notice("create deposit [Not Authorized] - {$request->getClientIp()} - {$journal_uuid}");
            throw new SwordException(400, "Not authorized to make deposits.");
        }

        $xml = $this->parseXml($request->getContent());
		$journal = $this->get('journalbuilder')->fromXml($xml, $journal_uuid);
		$journal->setStatus('healthy');        
        $deposit = $this->get('depositbuilder')->fromXml($journal, $xml);

        /** @var Response */
        $response = $this->statementAction($request, $journal->getUuid(), $deposit->getDepositUuid());
        $response->headers->set(
            'Location',
            $deposit->getDepositReceipt(),
            true
        );
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }

    /**
     * Check that status of a deposit by fetching the sword statemt.
     *
     * @Route("/cont-iri/{journal_uuid}/{deposit_uuid}/state", name="statement")
     * @Method("GET")
     */
    public function statementAction(Request $request, $journal_uuid, $deposit_uuid) {
        /** @var LoggerInterface */
        $logger = $this->get('monolog.logger.sword');
        $accepting = $this->checkAccess($journal_uuid);
        $acceptingLog = 'not accepting';
        if ($accepting) {
            $acceptingLog = 'accepting';
        }
		
		$logger->notice("statement - {$request->getClientIp()} - {$journal_uuid} - {$acceptingLog}");
        
		if(! $accepting && !$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
			throw new SwordException(400, "Not authorized to request statements.");
		}
		
		$em = $this->getDoctrine()->getManager();

        /** @var Journal */
        $journal = $em->getRepository('AppBundle:Journal')->findOneBy(array('uuid' => $journal_uuid));
        if ($journal === null) {
            throw new SwordException(400, "Journal UUID not found.");
        }

        /** @var Deposit */
        $deposit = $em->getRepository('AppBundle:Deposit')->findOneBy(array('depositUuid' => $deposit_uuid));
        if ($deposit === null) {
            throw new SwordException(400, "Deposit UUID not found.");
        }

        if ($journal->getId() !== $deposit->getJournal()->getId()) {
            throw new SwordException(400, "Deposit does not belong to journal.");
        }
        
        $journal->setContacted(new DateTime());
		$journal->setStatus('healthy');
        $em->flush();
		
        $processingState = 'unknown';
        switch($deposit->getState()) {
            case 'depositedByJournal': 
            case 'harvested':
            case 'payload-validated':
            case 'bag-validated':
            case 'virus-checked':
            case 'xml-validated':
                $processingState = 'received';
                break;
            case 'reserialized':
                $processingState = 'validated';
                break;
            case 'deposited':
                $processingState = 'sent';
				break;
            default:
                $processingState = 'unknown';
                $logger->critical('Deposit ' . $deposit->getId() . ' is in unknown processing state ' . $deposit->getState());
                break;
        }
        
        $plnState = 'unknown';

        /** @var Response */
        $response = $this->render("AppBundle:Sword:statement.xml.twig", array(
            "deposit" => $deposit,
            "processingState" => $processingState,
            'processingStateDesc' => self::$processingStates[$processingState],
            'plnState' => $plnState,
            'plnStateDesc' => self::$lockssStates[$plnState]
        ));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    /**
     * Edit a deposit with an HTTP PUT.
     *
     * @Route("/cont-iri/{journal_uuid}/{deposit_uuid}/edit")
     * @Method("PUT")
     */
    public function editAction(Request $request, $journal_uuid, $deposit_uuid) {
        /** @var LoggerInterface */
        $logger = $this->get('monolog.logger.sword');

        $accepting = $this->checkAccess($journal_uuid);
        $acceptingLog = 'not accepting';
        if ($accepting) {
            $acceptingLog = 'accepting';
        }
		
		$logger->notice("edit deposit - {$request->getClientIp()} - {$journal_uuid} - {$acceptingLog}");
		if(! $accepting) {
			throw new SwordException(400, "Not authorized to edit deposits.");
		}

        $em = $this->getDoctrine()->getManager();

        /** @var Journal $journal */
        $journal = $em->getRepository('AppBundle:Journal')->findOneBy(array('uuid' => $journal_uuid));
        if ($journal === null) {
            throw new SwordException(400, "Journal UUID not found.");
        }

        /** @var Deposit $deposit */
        $deposit = $em->getRepository('AppBundle:Deposit')->findOneBy(array('depositUuid' => $deposit_uuid));
        if ($deposit === null) {
            throw new SwordException(400, "Deposit UUID not found.");
        }

        if ($journal->getId() !== $deposit->getJournal()->getId()) {
            throw new SwordException(400, "Deposit does not belong to journal.");
        }

        $journal->setContacted(new DateTime());
        $xml = $this->parseXml($request->getContent());
        $newDeposit = $this->get('depositbuilder')->fromXml($xml, 'edit');

        /** @var Response */
        $response = $this->statementAction($request, $journal_uuid, $deposit_uuid);
        $response->headers->set(
            'Location',
            $deposit->getDepositReceipt(),
            true
        );
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }
}

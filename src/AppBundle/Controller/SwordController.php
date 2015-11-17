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

    private static $states = array(
        'failed' => 'The deposit to the PKP PLN staging server (or LOCKSS-O-Matic) has failed.',
        'inProgress' => 'The deposit to the staging server has succeeded but the deposit has not yet been registered with the PLN.',
        'disagreement' => 'The PKP LOCKSS network is not in agreement on content checksums.',
        'agreement' => 'The PKP LOCKSS network agrees internally on content checksums.',
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
     * Fetch an HTTP header.
     *
     * @param Request $request
     * @param type $name
     * @return type
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
     * Run the XPath query on some xml. If a single element is found by the
     * xpath, return the string value of the element.
     *
     * @param SimpleXMLElement $xml
     * @param string $xpath
     * @return string|null
     * @throws Exception if there are too many elements.
     */
    private function getXmlValue(SimpleXMLElement $xml, $xpath) {
        $data = $xml->xpath($xpath);
        if (count($data) === 1) {
            $str = (string)$data[0];
            return trim($str);
        }
        if (count($data) === 0) {
            return null;
        }
        throw new Exception("Too many elements for '{$xpath}'");
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
        $this->get('monolog.logger.sword')->notice("Checking access for {$journal_uuid}");
        if($bw->isWhitelisted($journal_uuid)) {
            $this->get('monolog.logger.sword')->notice("whitelisted {$journal_uuid}");
            return true;
        }
        if($bw->isBlacklisted($journal_uuid)) {
            $this->get('monolog.logger.sword')->notice("blacklisted {$journal_uuid}");
            return false;
        }
        return $this->container->getParameter("pln_accepting");
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
            throw new SwordException(400, "Missing On-Behalf-Of header");
        }
        if ($journalUrl === null) {
            throw new SwordException(400, "Missing Journal-Url header");
        }
        $em = $this->getDoctrine()->getManager();
        /** @var TermOfUseRepository */
        $repo = $em->getRepository("AppBundle:TermOfUse");
        $terms = $repo->getTerms();

        /** @var Response */
        $response = $this->render("AppBundle:Sword:serviceDocument.xml.twig", array(
            "onBehalfOf" => $obh,
            "accepting" => $accepting,
            "colIri" => $this->generateUrl(
                    "create_deposit", 
                    array("journal_uuid" => $obh), 
                    UrlGeneratorInterface::ABSOLUTE_URL
            ),
            "terms" => $terms,
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

        if( $this->checkAccess($journal_uuid) === false) {
            $logger->notice("create deposit [Not Authorized] - {$request->getClientIp()} - {$journal_uuid}");
            throw new SwordException(400, "Not authorized to make deposits.");
        }
        $logger->notice("create deposit - {$request->getClientIp()} - {$journal_uuid}");

        $em = $this->getDoctrine()->getManager();
        $journalRepo = $em->getRepository('AppBundle:Journal');
        $journal = $journalRepo->findOneBy(array('uuid' => $journal_uuid));
        $xml = $this->parseXml($request->getContent());
        if ($journal === null) {
            $journal = $this->get('journalbuilder')->fromXml($xml, $journal_uuid);
        }
        
        $deposit = $this->get('depositbuilder')->fromXml($journal, $xml);
        
        /** @var Response */
        $response = $this->statementAction($request, $journal->getUuid(), $deposit->getDepositUuid());
        $response->headers->set(
                'Location',
                $deposit->getDepositReceipt(),
                true);
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

        if( $this->checkAccess($journal_uuid) === false) {
            $logger->notice("statement [not authorized] - {$request->getClientIp()} - {$journal_uuid} - {$deposit_uuid}");
            throw new SwordException(400, "Not authorized to request statements.");
        }

        $logger->notice("statement - {$request->getClientIp()} - {$journal_uuid} - {$deposit_uuid}");

        $em = $this->getDoctrine()->getManager();

        /** @var Journal */
        $journal = $em->getRepository('AppBundle:Journal')->findOneBy(array('uuid' => $journal_uuid));

        /** @var Deposit */
        $deposit = $em->getRepository('AppBundle:Deposit')->findOneBy(array('depositUuid' => $deposit_uuid));

        if($journal === null) {
            throw new SwordException(400, "Journal UUID not found.");
        }

        if($deposit === null) {
            throw new SwordException(400, "Deposit UUID not found.");
        }

        if($journal->getId() !== $deposit->getJournal()->getId()) {
            throw new SwordException(400, "Deposit does not belong to journal.");
        }

        $journal->setContacted(new DateTime());
        $em->flush();

        $state = 'The deposit is in an unknown state.';
        if(array_key_exists($deposit->getPlnState(), self::$states)) {
            $state = self::$states[$deposit->getPlnState()];
        }

        /** @var Response */
        $response = $this->render("AppBundle:Sword:statement.xml.twig", array(
            "deposit" => $deposit,
            "state" => $state,
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

        if( $this->checkAccess($journal_uuid) === false) {
            $logger->notice("edit [not authorized] - {$request->getClientIp()} - {$journal_uuid} - {$deposit_uuid}");
            throw new SwordException(400, "Not authorized to edit deposits.");
        }

        $logger->notice("edit - {$request->getClientIp()} - {$journal_uuid} - {$deposit_uuid}");
        
        $em = $this->getDoctrine()->getManager();

        /** @var Journal $journal */
        $journal = $em->getRepository('AppBundle:Journal')->findOneBy(array('uuid' => $journal_uuid));
        if($journal === null) {
            throw new SwordException(400, "Journal UUID not found.");
        }

        /** @var Deposit $deposit */
        $deposit = $em->getRepository('AppBundle:Deposit')->findOneBy(array('deposit_uuid' => $deposit_uuid));
        if($deposit === null) {
            throw new SwordException(400, "Deposit UUID not found.");
        }

        if($journal->getId() !== $deposit->getJournal()->getId()) {
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

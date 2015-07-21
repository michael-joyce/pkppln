<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Journal;
use AppBundle\Entity\TermOfUseRepository;
use AppBundle\Exception\SwordException;
use DateTime;
use Exception;
use J20\Uuid\Uuid;
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

    private static $namespaces = array(
        'atom' => 'http://www.w3.org/2005/Atom',
        'dc' => "http://purl.org/dc/terms/",
        'sword' => "http://purl.org/net/sword/terms/",
        'pkp' => 'http://pkp.sfu.ca/SWORD',
        'app' => 'http://www.w3.org/2007/app',
        'lom' => 'http://lockssomatic.info/SWORD2',
        'xsi' => 'http://www.w3.org/2001/XMLSchema-instance'
    );

    private static $states = array(
        'failed' => 'The deposit to the PKP PLN staging server (or LOCKSS-O-Matic) has failed.',
        'inProgress' => 'The deposit to the staging server has succeeded but the deposit has not yet been registered with the PLN.',
        'disagreement' => 'The PKP LOCKSS network is not in agreement on content checksums.',
        'agreement' => 'The PKP LOCKSS network agrees internally on content checksums.',
        'unknown' => 'The deposit is in an unknown state.'
    );

    private function parseXml($content) {
        $xml = new SimpleXMLElement($content);
        foreach (self::$namespaces as $key => $value) {
            $xml->registerXPathNamespace($key, $value);
        }
        return $xml;
    }

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

    private function getLocale(Request $request) {
        $languageHeader = $this->fetchHeader($request, "Accept-Language");
        $locale = locale_accept_from_http($languageHeader);
        if ($locale !== null) {
            return $locale;
        }
        return $this->container->getParameter("pln_defaultLocale");
    }

    private function getXmlValue(SimpleXMLElement $xml, $xpath) {
        $data = $xml->xpath($xpath);
        if (count($data) === 1) {
            return (string) $data[0];
        }
        if (count($data) === 0) {
            return null;
        }
        throw new Exception("Too many elements for '{$xpath}'");
    }

    private function checkAccess($journal_uuid) {
        $em = $this->getDoctrine()->getManager();
        $wlEntry = $em->getRepository('AppBundle:Whitelist')
                ->findOneBy(array('uuid' => $journal_uuid));

        if ($wlEntry !== null) {
            return true;
        }

        $blEntry = $em->getRepository('AppBundle:Blacklist')
                ->findOneBy(array('uuid' => $journal_uuid));
        if ($blEntry !== null) {
            return false;
        }

        return $this->container->getParameter("pln_accepting");
    }

    /**
     * @Route("/sd-iri", name="service_document")
     * @Method("GET")
     */
    public function serviceDocumentAction(Request $request) {
        /** @var LoggerInterface */
        $logger = $this->get('monolog.logger.sword');

        $obh = $this->fetchHeader($request, "On-Behalf-Of");
        $journalUrl = $this->fetchHeader($request, "Journal-Url");

        $locale = $this->getLocale($request);
        $accepting = $this->checkAccess($obh);
        $acceptingLog = 'not accepting';
        if ($accepting) {
            $acceptingLog = 'accepting';
        }

        $logger->notice("service document - {$request->getClientIp()} - {$locale} - {$obh} - {$journalUrl} - {$acceptingLog}");
        if ($obh === null) {
            throw new SwordException(400, "Missing On-Behalf-Of header");
        }
        if ($journalUrl === null) {
            throw new SwordException(400, "Missing Journal-Url header");
        }
        $em = $this->getDoctrine()->getManager();
        /** @var TermOfUseRepository */
        $repo = $em->getRepository("AppBundle:TermOfUse");
        $terms = $repo->getCurrentTerms($locale);

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
     * @Route("/col-iri/{journal_uuid}", name="create_deposit")
     * @Method("POST")
     */
    public function createDepositAction(Request $request, $journal_uuid) {
        /** @var LoggerInterface */
        $logger = $this->get('monolog.logger.sword');
        $logger->notice("create deposit - {$request->getClientIp()} - {$journal_uuid}");

        $em = $this->getDoctrine()->getManager();
        $journalRepo = $em->getRepository('AppBundle:Journal');
        $journal = $journalRepo->findOneBy(array('uuid' => $journal_uuid));
        $xml = $this->parseXml($request->getContent());
        if ($journal === null) {
            $journal = new Journal();
            $journal->setUuid($journal_uuid);
            $journal->setTitle($this->getXmlValue($xml, '//atom:title'));
            $journal->setUrl($this->getXmlValue($xml, '//pkp:journal_url'));
            $journal->setEmail($this->getXmlValue($xml, '//atom:email'));
            $journal->setIssn($this->getXmlValue($xml, '//pkp:issn'));
            $journal->setPublisherName($this->getXmlValue($xml, '//pkp:publisherName'));
            $journal->setPublisherUrl($this->getXmlValue($xml, '//pkp:publisherUrl'));
            $em->persist($journal);
        }

        $id = $this->getXmlValue($xml, '//atom:id');
        $deposit_uuid = substr($id, 9, 36);

        $deposit = new Deposit();
        $deposit->setAction('add');
        $deposit->setOutcome('success');
        $deposit->setChecksumType($this->getXmlValue($xml, 'pkp:content/@checksumType'));
        $deposit->setChecksumValue($this->getXmlValue($xml, 'pkp:content/@checksumValue'));
        $deposit->setDepositUuid($deposit_uuid);
        $deposit->setFileUuid(Uuid::v4(true));
        $deposit->setFileType('');
        $deposit->setIssue($this->getXmlValue($xml, 'pkp:content/@issue'));
        $deposit->setVolume($this->getXmlValue($xml, 'pkp:content/@volume'));
        $deposit->setPubDate(new DateTime($this->getXmlValue($xml, 'pkp:content/@pubdate')));
        $deposit->setJournal($journal);
        $deposit->setSize($this->getXmlValue($xml, 'pkp:content/@size'));
        $deposit->setUrl($this->getXmlValue($xml, 'pkp:content'));
        $deposit->setDepositReceipt($this->generateUrl(
                "statement", array(
                    'journal_uuid' => $journal_uuid,
                    'deposit_uuid' => $deposit->getDepositUuid(),
                ), UrlGeneratorInterface::ABSOLUTE_URL
                )
        );

        $em->persist($deposit);
        $em->flush();

        /** @var Response */
        $response = $this->statementAction($request, $journal_uuid, $deposit_uuid);
        $response->headers->set(
                'Location',
                $deposit->getDepositReceipt(),
                true);
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }

    /**
     * @Route("/cont-iri/{journal_uuid}/{deposit_uuid}/state", name="statement")
     * @Method("GET")
     */
    public function statementAction(Request $request, $journal_uuid, $deposit_uuid) {
        /** @var LoggerInterface */
        $logger = $this->get('monolog.logger.sword');
        $logger->notice("statement - {$request->getClientIp()} - {$journal_uuid} - {$deposit_uuid}");

        $em = $this->getDoctrine()->getManager();

        /** @var Journal */
        $journal = $em->getRepository('AppBundle:Journal')->findOneBy(array('uuid' => $journal_uuid));

        /** @var Deposit */
        $deposit = $em->getRepository('AppBundle:Deposit')->findOneBy(array('deposit_uuid' => $deposit_uuid));

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
     * @Route("/cont-iri/{journal_uuid}/{deposit_uuid}/edit")
     * @Method("PUT")
     */
    public function editAction(Request $request, $journal_uuid, $deposit_uuid) {
        /** @var LoggerInterface */
        $logger = $this->get('monolog.logger.sword');
        $logger->notice("edit - {$request->getClientIp()} - {$journal_uuid} - {$deposit_uuid}");
        
        $em = $this->getDoctrine()->getManager();

        /** @var Journal */
        $journal = $em->getRepository('AppBundle:Journal')->findOneBy(array('uuid' => $journal_uuid));

        /** @var Deposit */
        $deposit = $em->getRepository('AppBundle:Deposit')->findOneBy(array('deposit_uuid' => $deposit_uuid));

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
        
        $newDeposit = new Deposit();
        $newDeposit->setAction('edit');
        $newDeposit->setOutcome('success');
        $newDeposit->setChecksumType($this->getXmlValue($xml, 'pkp:content/@checksumType'));
        $newDeposit->setChecksumValue($this->getXmlValue($xml, 'pkp:content/@checksumValue'));
        $newDeposit->setDepositUuid($deposit_uuid);
        $newDeposit->setFileUuid(Uuid::v4(true));
        $newDeposit->setIssue($this->getXmlValue($xml, 'pkp:content/@issue'));
        $newDeposit->setVolume($this->getXmlValue($xml, 'pkp:content/@volume'));
        $newDeposit->setPubDate(new DateTime($this->getXmlValue($xml, 'pkp:content/@pubdate')));
        $newDeposit->setJournal($journal);
        $newDeposit->setSize($this->getXmlValue($xml, 'pkp:content/@size'));
        $newDeposit->setUrl($this->getXmlValue($xml, 'pkp:content'));
        $newDeposit->setDepositReceipt($this->generateUrl(
                "statement", array(
                    'journal_uuid' => $journal_uuid,
                    'deposit_uuid' => $newDeposit->getDepositUuid(),
                ), UrlGeneratorInterface::ABSOLUTE_URL
                )
        );

        $em->persist($deposit);
        $em->flush();

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

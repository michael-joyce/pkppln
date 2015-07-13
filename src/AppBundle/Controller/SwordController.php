<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Controller;

use AppBundle\Entity\TermOfUseRepository;
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
        'pkp'=> 'http://pkp.sfu.ca/SWORD',
        'app' => 'http://www.w3.org/2007/app',
        'lom' => 'http://lockssomatic.info/SWORD2',
        'xsi' => 'http://www.w3.org/2001/XMLSchema-instance'
    );

    private function parseXml($content) {
        $xml = new SimpleXMLElement($content);
        foreach($namespaces as $key => $value) {
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
            return $request->query->has($name);
        }
        return null;
    }

    private function getLocale(Request $request) {
        $languageHeader = $this->fetchHeader($request, "Accept-Language");
        $locale = locale_accept_from_http($languageHeader);
        if ($locale !== null) {
            return $locale;
        }
        return $this->container->getParameter("pln.defaultLocale");
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

        $logger->notice("service document - {$request->getClientIp()} - {$locale} - {$obh} - {$journalUrl}");
//        if($obh === null) {
//            return new Response("Missing On-Behalf-Of header.", 400);
//        }
//        if($journalUrl === null) {
//            return new Response("Missing Journal-URL header.", 400);
//        }

        $em = $this->getDoctrine()->getManager();
        /** @var TermOfUseRepository */
        $repo = $em->getRepository("AppBundle:TermOfUse");
        $terms = $repo->getCurrentTerms($locale);

        /** @var Response */
        $response = $this->render("AppBundle:Sword:serviceDocument.xml.twig", array(
            "accepting" => $this->container->getParameter("pln.accepting"),
            "maxUpload" => $this->container->getParameter("pln.maxUploadSize"),
            "checksumType" => $this->container->getParameter("pln.uploadChecksum"),
            "onBehalfOf" => $obh,
            "colIri" => $this->generateUrl(
                    "create_deposit",
                    array("uuid" => "uuid"),
                    UrlGeneratorInterface::ABSOLUTE_URL
            ),
            "terms" => $terms,
        ));
        $response->headers->set("Content-Type", "text/xml");
        return $response;
    }

    /**
     * @Route("/col-iri/{journal_uuid}", name="create_deposit")
     * @Method("POST")
     */
    public function createDepositAction(Request $request, $journal_uuid) {
        $xml = $this->parseXml($request->getContent());
        $title = $xml->xpath('//atom:title');
        /** @var LoggerInterface */
        $logger = $this->get('monolog.logger.sword');
        $logger->notice($title);

    }

    /**
     * @Route("/cont-iri/:journal_uuid/:deposit_uuid/state")
     * @Method("GET")
     */
    public function statementAction(Request $request, $journal_uuid, $deposit_uuid) {

    }

    /**
     * @Route("/cont-iri/:journal_uuid/:deposit_uuid/edit")
     * @Method("PUT")
     */
    public function editAction(Request $request, $journal_uuid, $deposit_uuid) {
        
    }

}

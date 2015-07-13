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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * SWORD v2 Controller to receive deposits.
 * 
 * @Route("/api/sword/2.0")
 */
class SwordController extends Controller {

    private function fetchHeader(Request $request, $name) {
        if ($request->headers->has($name)) {
            return $request->headers->get($name);
        }
        if ($request->headers->has('X-' . $name)) {
            return $request->headers->has('X-' . $name);
        }
        if ($request->query->has($name)) {
            return $request->query->has($name);
        }
        return null;
    }

    /**
     * @Route("/sd-iri", name="service_document")
     * @Method("GET")
     */
    public function serviceDocumentAction(Request $request) {
        $obh = $this->fetchHeader($request, 'On-Behalf-Of');
        $journalUrl = $this->fetchHeader($request, 'Journal-Url');
//        if($obh === null) {
//            return new Response('Missing On-Behalf-Of header.', 400);
//        }
//        if($journalUrl === null) {
//            return new Response('Missing Journal-URL header.', 400);
//        }
        $languageHeader = $this->fetchHeader($request, 'Accept-Language');
        $locale = locale_accept_from_http($languageHeader);
        if ($locale === null) {
            $locale = $accepting = $this->container->getParameter('pln.defaultLocale');
        }

        $em = $this->getDoctrine()->getManager();
        /** @var TermOfUseRepository */
        $repo = $em->getRepository('AppBundle:TermOfUse');
        $terms = $repo->getCurrentTerms($locale);

        /** @var Response */
        $response = $this->render('AppBundle:Sword:serviceDocument.xml.twig', array(
            'accepting' => $this->container->getParameter('pln.accepting'),
            'maxUpload' => $this->container->getParameter('pln.maxUploadSize'),
            'checksumType' => $this->container->getParameter('pln.uploadChecksum'),
            'onBehalfOf' => $obh,
            'colIri' => $this->generateUrl('create_deposit', array(
                'uuid' => "uuid"
            ), UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'terms' => $terms,
        ));
        $response->headers->set('Content-Type', 'text/xml');
        return $response;
    }

    /**
     * @Route("/col-iri/{uuid}", name="create_deposit")
     * @Method("POST")
     */
    public function createDepositAction(Request $request, $journal_uuid) {
        $body = $request->getContent();
    }
    
    /**
     * @Route('/cont-iri/:journal_uuid/:deposit_uuid/state')
     * @Method("GET")
     */
    public function statementAction(Request $request, $journal_uuid, $deposit_uuid) {
    }

    /**
     * @Route('/cont-iri/:journal_uuid/:deposit_uuid/state')
     * @Method("PUT")
     */
    public function editAction(Request $request, $journal_uuid, $deposit_uuid) {
    }
}

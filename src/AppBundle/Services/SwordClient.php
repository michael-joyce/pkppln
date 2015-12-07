<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Journal;
use AppBundle\Utility\Namespaces;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Stream\Stream;
use Monolog\Logger;
use SimpleXMLElement;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

/**
 * Experimental sword client.
 */
class SwordClient {

    private $sdIri;
    private $serverUuid;
    private $maxUpload;
    private $uploadChecksum;
    private $siteName;
    private $colIri;
    private $baseUri;

    /**
     * @var Namespaces
     */
    private $namespaces;

    /**
     * @var TwigEngine
     */
    private $templating;

    /**
     *
     * @var Router
     */
    private $router;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct($sdIri, $baseUri, $serverUuid) {
        $this->sdIri = $sdIri;
        $this->baseUri = $baseUri;
        $this->serverUuid = $serverUuid;
        $this->logger = null;
        $this->namespaces = new Namespaces();
    }

    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

    public function setTemplating(TwigEngine $templating) {
        $this->templating = $templating;
    }

    public function setRouter(Router $router) {
        $this->router = $router;
    }

    private function log($message, $context = array(), $level = 'info') {
        $this->logger->log($level, $message, $context);
    }

    /**
     * @param Journal $journal
     * @throws RequestException
     */
    public function serviceDocument(Journal $journal) {
        $client = new Client();
        $headers = array(
            'On-Behalf-Of' => $this->serverUuid,
            'Journal-Url' => $journal->getUrl(),
        );
        $this->logger->critical('Headers: ' . print_r($headers, true));
        try {
            $response = $client->get($this->sdIri, [ 'headers' => $headers]);
        } catch(RequestException $e) {
            $this->logger->critical($e->getMessage());
            if($e->hasResponse()) {
                $this->logger->critical($e->getResponse()->getBody());
            }
            throw $e;
        }
        $xml = new SimpleXMLElement($response->getBody());
        $this->namespaces->registerNamespaces($xml);
        $this->maxUpload = $xml->xpath('sword:maxUploadSize')[0];
        $this->uploadChecksum = $xml->xpath('lom:uploadChecksumType')[0];
        $this->siteName = $xml->xpath('.//atom:title');
        $this->colIri = $xml->xpath('.//app:collection/@href')[0];
    }

    public function createDeposit(Deposit $deposit) {
        $this->serviceDocument($deposit->getJournal());
        $xml = $this->templating->render('AppBundle:SwordClient:deposit.xml.twig', array(
            'title' => 'Deposit from OJS',
            'deposit' => $deposit,
            'baseUri' => $this->baseUri,
        ));
        try {
        $client = new Client();
        $request = $client->createRequest('POST', $this->colIri);
        $request->setBody(Stream::factory($xml));
        $response = $client->send($request);
        } catch(RequestException $e) {
            $this->logger->critical($e->getMessage());
            if($e->hasResponse()) {
                $this->logger->critical($e->getResponse()->getBody());
            }
            throw $e;
        }
        $deposit->setDepositReceipt($response->getHeader('Location'));
        
        $responseXml = new SimpleXMLElement($response->getBody());
        $this->namespaces->registerNamespaces($responseXml);
        return true;
    }

    public function statement(Deposit $deposit) {
        
    }

}

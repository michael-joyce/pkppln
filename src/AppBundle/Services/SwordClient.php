<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Journal;
use AppBundle\Utility\Namespaces;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use SimpleXMLElement;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SwordClient {

    private $sdIri;
    private $serverUuid;
    
    private $maxUpload;
    private $uploadChecksum;
    private $siteName;
    private $colIri;

    /**
     * @var Namespaces
     */
    private $namespaces;

    /**
     * @var TwigEngine
     */
    private $templating;

    private $router;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct($sdIri, $serverUuid, $templating, $router) {
        $this->sdIri = $sdIri;
        $this->serverUuid = $serverUuid;
        $this->logger = null;
        $this->namespaces = new Namespaces();
        $this->templating = $templating;
        $this->router = $router;
    }

    public function setLogger(Logger $logger) {
        $this->logger = $logger;
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
        $response = $client->get($this->sdIri, [ 'headers' => $headers ]);
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
            'deposit_url' => $this->router->generate('fetch', array(
                'depositId' => $deposit->getDepositUuid(),
                'fileId' => $deposit->getFileUuid()
            ), UrlGeneratorInterface::ABSOLUTE_URL),
            'deposit_size' => filesize($deposit->getPackagePath()),
            'deposit_checksum_type' => $this->uploadChecksum,
            'deposit_checksum_value' => hash_file($this->uploadChecksum, $deposit->getPackagePath()),
        ));
        $this->log('Generating XML for ' . $deposit->getPackagePath());
        $this->log($xml);
    }

    public function statement(Deposit $deposit) {
        
    }

}

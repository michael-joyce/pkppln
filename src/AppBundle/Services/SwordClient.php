<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Journal;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;

class SwordClient {

    private $sdIri;
    private $serverUuid;
    
    private $maxUpload;
    private $uploadChecksum;
    private $siteName;
    private $colIri;
    private $providerName;
    private $plugins;
    

    /**
     * @var Logger
     */
    private $logger;

    public function __construct($sdIri, $serverUuid) {
        $this->sdIri = $sdIri;
        $this->serverUuid = $serverUuid;
        $this->logger = null;
    }

    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

    private function log($message, $context = array(), $level = 'info') {
            $this->logger->log($level, $message, $context);
    }

    /**
     * 
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
        $xml = new \SimpleXMLElement($response->getBody());
    }

    public function createDeposit(Deposit $deposit) {
        
    }

    public function statement(Deposit $deposit) {
        
    }

}

<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Journal;
use AppBundle\Utility\Namespaces;
use GuzzleHttp\Client;
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

    /**
     * IRI for the service document.
     *
     * @var type 
     */
    private $sdIri;
    
    /**
     * The UUID for the LOCKSSOMatic server.
     *
     * @var string
     */
    private $serverUuid;
    
    /**
     * Maximum upload file size, as reported by the service document.
     *
     * @var type 
     */
    private $maxUpload;
    
    /**
     * Checksum of the deposit package.
     *
     * @var string
     */
    private $uploadChecksum;
    
    /**
     * Name of the site, as reported by the service document.
     *
     * @var string 
     */
    private $siteName;
    
    /**
     * The collectin IRI, as reported by the service document.
     *
     * @var string 
     */
    private $colIri;
    
    /**
     * Mapping of prefix => URIs for XML namespaces.
     * 
     * @var Namespaces
     */
    private $namespaces;

    /**
     * @var TwigEngine
     */
    private $templating;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * All PKP PLN journals are given the same title in LOCKSS/LOCKSSOMatic to
     * enable use of the LOCKSS subscription manager.
     *
     * @var type 
     */
    private $plnJournalTitle;

    /**
     * Construct a sword client.
     * 
     * @param string $sdIri
     * @param string $serverUuid
     */
    public function __construct($sdIri, $serverUuid) {
        $this->sdIri = $sdIri;
        $this->serverUuid = $serverUuid;
        $this->logger = null;
        $this->namespaces = new Namespaces();
    }
    
    /**
     * Set the PLN Journal Title.
     * 
     * @param string $plnJournalTitle
     */
    public function setPlnJournalTitle($plnJournalTitle) {
        $this->plnJournalTitle = $plnJournalTitle;
    }

    /**
     * Set the logger
     * 
     * @param Logger $logger
     */
    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

    /**
     * Set the templating engine.
     * 
     * @param TwigEngine $templating
     */
    public function setTemplating(TwigEngine $templating) {
        $this->templating = $templating;
    }

    /**
     * Set the router for the PLN.
     * 
     * @param Router $router
     */
    public function setRouter(Router $router) {
        $this->router = $router;
    }

    /**
     * Convenience method to log a message.
     * 
     * @param string $message
     * @param array $context
     * @param string $level
     */
    private function log($message, $context = array(), $level = 'info') {
        $this->logger->log($level, $message, $context);
    }

    /**
     * Fetch the service document by HTTP.
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
        try {
            $response = $client->get($this->sdIri, [ 'headers' => $headers]);
        } catch (RequestException $e) {
            $this->logger->critical($e->getMessage());
            if ($e->hasResponse()) {
                $xml = $e->getResponse()->xml();
                $xml->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
                $this->logger->critical((string) $xml->xpath('//atom:summary')[0]);
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

    /**
     * Send a deposit to LOM via HTTP.
     * 
     * @param Deposit $deposit
     * @return boolean true on success.
     */
    public function createDeposit(Deposit $deposit) {
        $this->serviceDocument($deposit->getJournal());
        $xml = $this->templating->render('AppBundle:SwordClient:deposit.xml.twig', array(
            'title' => 'Deposit from OJS',
            'deposit' => $deposit,
            'baseUri' => $this->router->generate('home', array(), UrlGeneratorInterface::ABSOLUTE_URL),
            'plnJournalTitle' => $this->plnJournalTitle,
        ));
        try {
            $client = new Client();
            $request = $client->createRequest('POST', $this->colIri);
            $request->setBody(Stream::factory($xml));
            $response = $client->send($request);
        } catch (RequestException $e) {
            $this->logger->critical($e->getMessage());
            if ($e->hasResponse()) {
                $xml = $e->getResponse()->xml();
                $xml->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
                $xml->registerXPathNamespace('sword', 'http://purl.org/net/sword/');
                $this->logger->critical("Summary: " . (string) $xml->xpath('//atom:summary')[0]);
                $this->logger->warning("Detail: " . (string) $xml->xpath('//sword:verboseDescription')[0]);
            }
            return false;
        }
        $deposit->setDepositReceipt($response->getHeader('Location'));

        $responseXml = new SimpleXMLElement($response->getBody());
        $this->namespaces->registerNamespaces($responseXml);
        return true;
    }

    /**
     * Fetch the SWORD statement.
     * 
     * @todo complete this stub.
     * 
     * @param Deposit $deposit
     */
    public function statement(Deposit $deposit) {
        
    }

}

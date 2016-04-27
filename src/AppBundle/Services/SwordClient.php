<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Journal;
use AppBundle\Utility\Namespaces;
use DateTime;
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
	 * @var Client
	 */
	private $client;

    /**
     * All PKP PLN journals are given the same title in LOCKSS/LOCKSSOMatic to
     * enable use of the LOCKSS subscription manager.
     *
     * @var type 
     */
    private $plnJournalTitle;
	
	/**
	 * @var FilePaths
	 */
	private $filePaths;
	
	/**
	 * If true, save the deposit XML in a file in the same directory as the 
	 * serialized deposit bag.
	 *
	 * @var boolean
	 */
	private $saveDepositXml;

    /**
     * Construct a sword client.
     * 
     * @param string $sdIri
     * @param string $serverUuid
	 * @param boolean $saveDepositXml
     */
    public function __construct($sdIri, $serverUuid, $saveDepositXml) {
        $this->sdIri = $sdIri;
        $this->serverUuid = $serverUuid;
        $this->logger = null;
        $this->namespaces = new Namespaces();
		$this->saveDepositXml = $saveDepositXml;
    }
    
	public function setClient(Client $client) {
		$this->client = $client;
	}
	
	/**
	 * @return Client
	 */
	public function getClient() {
		if(! $this->client) {
			$this->client = new Client();
		}
		return $this->client;
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
	 * Set the FilePaths service.
	 * 
	 * @param FilePaths $filePaths
	 */
	public function setFilePaths(FilePaths $filePaths) {
		$this->filePaths = $filePaths;
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
        $client = $this->getClient();
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
        $this->maxUpload = (string)($xml->xpath('sword:maxUploadSize')[0]);
        $this->uploadChecksum = (string)($xml->xpath('lom:uploadChecksumType')[0]);
        $this->siteName = (string)($xml->xpath('.//atom:title')[0]);
        $this->colIri = (string)($xml->xpath('.//app:collection/@href')[0]);
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
            'title' => 'Deposit from OJS part ' . $deposit->getAuContainer()->getId(),
			'publisher' => 'Public Knowledge Project Staging Server',
            'deposit' => $deposit,
            'baseUri' => $this->router->generate('home', array(), UrlGeneratorInterface::ABSOLUTE_URL),
            'plnJournalTitle' => $this->plnJournalTitle,
        ));
		if($this->saveDepositXml) {
			$atomPath = $this->filePaths->getStagingDir($deposit->getJournal()) . '/' . $deposit->getDepositUuid() . '.xml';
			file_put_contents($atomPath, $xml);
		}
        try {
            $client = $this->getClient();
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
        $deposit->setDepositDate(new DateTime());

        // TODO should I do something wtih responseXML here?
        $responseXml = new SimpleXMLElement($response->getBody());
        $this->namespaces->registerNamespaces($responseXml);
        return true;
    }
    
    public function receipt(Deposit $deposit) {
        $client = $this->getClient();
        $receiptRequest = $client->createRequest('GET', $deposit->getDepositReceipt());
        $receiptResponse = $client->send($receiptRequest);
        $receiptXml = new SimpleXMLElement($receiptResponse->getBody());
        $this->namespaces->registerNamespaces($receiptXml);
        return $receiptXml;
    }

    /**
     * Fetch the SWORD statement.
     * 
     * @todo complete this stub.
     * 
     * @param Deposit $deposit
     */
    public function statement(Deposit $deposit) {
        $receipt = $this->receipt($deposit);        
        $statementUrl = $receipt->xpath('atom:link[@rel="http://purl.org/net/sword/terms/statement"]/@href')[0];
        
        $client = $this->getClient();
        $statementRequest = $client->createRequest('GET', $statementUrl);
        $statementResponse = $client->send($statementRequest);
        $statementXml = new \SimpleXMLElement($statementResponse->getBody());
        $this->namespaces->registerNamespaces($statementXml);
        
        return $statementXml;
    }
	
	public function getSiteName() {
		return $this->siteName;
	}
	
	public function getColIri() {
		return $this->colIri;
	}
	
	public function getMaxUpload() {
		return $this->maxUpload;
	}
	
	public function getUploadChecksum() {
		return $this->uploadChecksum;
	}
}

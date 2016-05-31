<?php

namespace AppBundle\Services;

use AppBundle\Entity\Journal;
use AppBundle\Utility\PingResult;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\XmlParseException;
use Monolog\Logger;

/**
 * Send a PING request to a journal, and return the result.
 */
class Ping {

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;
	
	/**
	 * @var Client
	 */
	private $client;

    /**
     * Set the ORM thing.
     * 
     * @param Registry $registry
     */
    public function setManager(Registry $registry) {
        $this->em = $registry->getManager();
    }

    /**
     * Set the service logger.
     * 
     * @param Logger $logger
     */
    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }
	
    /**
     * Set the HTTP client
     * 
     * @param Client $client
     */
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
	 * Ping a journal, check on it's health, etc.
	 * 
	 * @param Journal $journal
	 * @return PingResult
     * @throws Exception
	 */
	public function ping(Journal $journal) {
		$this->logger->notice("Pinging {$journal}");
		$url = $journal->getGatewayUrl();
		$client = $this->getClient();
		try {
			$response = $client->get($url, array(
				'allow_redirects' => false,
				'headers' => array(
					'User-Agent' => 'PkpPlnBot 1.0; http://pkp.sfu.ca',
					'Accept' => 'application/xml,text/xml,*/*;q=0.1'
				),
			));
			$pingResponse = new PingResult($response);
			if($pingResponse->getHttpStatus() === 200) {
				$journal->setContacted(new DateTime());
				$journal->setTitle($pingResponse->getJournalTitle('(unknown title)'));
				$journal->setOjsVersion($pingResponse->getOjsRelease());
                $journal->setTermsAccepted($pingResponse->areTermsAccepted() === 'yes');
			} else {
				$journal->setStatus('ping-error');
			}
			$this->em->flush($journal);
			return $pingResponse;
		} catch (RequestException $e) {
			$journal->setStatus('ping-error');
			$this->em->flush($journal);
            if ($e->hasResponse()) {
				return new PingResult($e->getResponse());
            }
			throw $e;
        } catch (XmlParseException $e) {
			$journal->setStatus('ping-error');
			$this->em->flush($journal);
			return new PingResult($e->getResponse());
        } catch (Exception $e) {
			$journal->setStatus('ping-error');
			$this->em->flush($journal);
			throw $e;
		}
	}
}
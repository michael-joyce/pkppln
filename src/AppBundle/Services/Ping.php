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
	 * Ping a journal, check on it's health, etc.
	 * 
	 * @param Journal $journal
	 * @return PingResult
     * @throws Exception
	 */
	public function ping(Journal $journal) {
		$url = $journal->getGatewayUrl();
		$client = new Client();
		try {
			$response = $client->get($url, array(
				'headers' => array(
					'User-Agent' => 'PkpPlnBot 1.0; http://pkp.sfu.ca',
					'Accept' => 'application/xml,text/xml,*/*;q=0.1'
				),
			));
			$pingResponse = new PingResult($response);
            $journal->setContacted(new DateTime());
            $journal->setTitle($pingResponse->getJournalTitle());
            $journal->setOjsVersion($pingResponse->getOjsRelease());
            $this->em->flush($journal);
			return $pingResponse;
		} catch (RequestException $e) {
            if ($e->hasResponse()) {
				return new PingResult($e->getResponse());
            }
			throw $e;
        } catch (XmlParseException $e) {
			return new PingResult($e->getResponse());
        } catch (Exception $e) {
			throw $e;
		}
	}
}
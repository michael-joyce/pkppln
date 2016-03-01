<?php

namespace AppBundle\Services;

use AppBundle\Entity\Journal;
use AppBundle\Utility\PingResult;
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
     * @var Logger
     */
    private $logger;

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
					'User-Agent' => 'PkpPlnBot 1.0; http://pkp.sfu.ca/pkp-lockss',
					'Accept' => 'application/xml,text/xml,*/*;q=0.1'
				),
			));
			$pingResponse = new PingResult($response);
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
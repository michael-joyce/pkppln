<?php

namespace AppBundle\Services;

use Exception;
use AppBundle\Entity\Journal;
use AppBundle\Utility\PingResult;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;

class Ping {

    /**
     * @var Logger
     */
    private $logger;

    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }

	/**
	 * Ping a journal, check on it's health, etc.
	 * 
	 * @param Journal $journal
	 * @return PingResult
	 */
	public function ping(Journal $journal) {
		$url = $journal->getGatewayUrl();
		$client = new Client();
		try {
			$response = $client->get($url);
			if($response->getStatusCode() !== 200) {
				$this->logger->warning("Ping resoponded with HTTP {$response->getStatusCode()} for {$journal->getGatewayUrl()}");
			}		
			$ping = new PingResult($response->xml());
			return $ping;
		} catch (RequestException $e) {
            $this->logger->error("Cannot ping {$journal->getUrl()}: {$e->getMessage()}");
            if ($e->hasResponse()) {
                $this->logger->error($e->getResponse()->getStatusCode() . ' ' . $this->logger->error($e->getResponse()->getReasonPhrase()));
            }
			throw new Exception($e->getMessage());
        } catch (XmlParseException $e) {
            $this->logger->error("Cannot parse journal ping response {$journal->getUrl()}: {$e->getMessage()}");
			throw new Exception($e->getMessage());
        } catch (Exception $e) {
			$this->logger->error($e->getMessage());
			if(($e instanceof RequestException) && ($e->hasResponse())) {
				$this->logger->error($e->getResponse()->getBody());
			}
			throw new Exception($e->getMessage());
		}
	}
	
}
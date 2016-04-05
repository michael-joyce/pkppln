<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\Response;

/**
 * Harvest a deposit from a journal. Attempts to check file sizes via HTTP HEAD
 * before downloading, and checks that there will be sufficient disk space.
 */
class HarvestCommand extends AbstractProcessingCmd {

    /**
     * File sizes reported via HTTP HEAD must this close to to the file size
     * as reported in the deposit. Threshold = 0.02 is 2%.
     */
    const FILE_SIZE_THRESHOLD = 0.02;
    
    /**
     * @var Client
     */
    private $client;
    
    public function __construct($name = null) {
        parent::__construct($name);
        $this->client = new Client(
            array('default' => array(
                'headers' => array(
                    'User-Agent' => 'PkpPlnBot 1.0; http://pkp.sfu.ca',
                    'Accept' => 'application/xml,text/xml,*/*;q=0.1'
            )))
        );
    }
    
    /**
     * {@inheritDoc}
     */
    protected function configure() {
        $this->setName('pln:harvest');
        $this->setDescription('Harvest OJS deposits.');
        parent::configure();
    }

    /**
     * Write a deposit's data to the filesystem at $path. Returns true on
     * success and false on failure.
     *
     * @param string $path
     * @param Response $response
     * @return boolean
     */
    protected function writeDeposit($path, Response $response) {
        $this->logger->info("Writing deposit to {$path}");
        try {
            $fh = fopen($path, 'wb');
            $body = $response->getBody();
            // 64k chunks.
            while($bytes = $body->read(64*1024)) {
                fwrite($fh, $bytes);
            }
            fclose($fh);
        } catch(Exception $ex) {
            $this->logger->error("Cannot write data to {$path}.");
            throw $ex;
        }
        return true;
    }

    /**
     * Fetch a deposit URL with Guzzle. Returns the data on success or false
     * on failure.
     *
     * @param string $url
     * @return Response|false
     */
    protected function fetchDeposit($url, $expected) {
        try {
            $response = $this->client->get($url);
            $this->logger->info("Harvest - {$url} - HTTP {$response->getStatusCode()} - {$response->getHeader('Content-Length')}");
			if($response->getStatusCode() !== 200) {
                $this->logger->error("Harvest - {$url} - HTTP {$response->getHttpStatus()} - {$url} - {$response->getError()}");
			}
        } catch (Exception $e) {
            $this->logger->error($e);
            if ($e->hasResponse()) {
                $this->logger->error($e->getResponse()->getStatusCode() . ' ' . $this->logger->error($e->getResponse()->getReasonPhrase()));
            } else {
                $this->logger->error("Harvest - {$url} - $e->getMessage()");
            }
            throw $e;
        }
        return $response;
    }

    /**
     * Send an HTTP HEAD request to get the deposit's host to get an estimate
     * of the download size.
     * 
     * @param type $deposit
     * @throws Exception
     */
    protected function checkSize(Deposit $deposit) {
        try {
            $head = $this->client->head($deposit->getUrl());
            if($head->getStatusCode() !== 200) {
                throw new Exception("HTTP HEAD request cannot check file size: HTTP {$head->getStatusCode()} - {$head->getReasonPhrase()} - {$deposit->getUrl()}");
            }
            $size = $head->getHeader('Content-Length');
            if($size === null || $size === '') {
                throw new Exception("HTTP HEAD response does not include file size - {$deposit->getUrl()}");
            }
            $expectedSize = $deposit->getSize() * 1000;
            if(abs($expectedSize - $size) / $size > self::FILE_SIZE_THRESHOLD) {
                $deposit->addErrorLog("Expected file size {$expectedSize} is not close to reported size {$size}");
                $this->logger->warning("Harvest - {$url} - Expected file size {$expectedSize} is not close to reported size {$size}");
            }
        } catch(RequestException $e) {
			$response = $e->getResponse();
			if($response !== null) {
	            $this->logger->critical($e->getResponse()->getStatusCode() . ' ' . $this->logger->error($e->getResponse()->getReasonPhrase()));
			} else {
	            $this->logger->critical($e->getMessage());
			}
            throw $e;
        }

    }

    /**
     * Get an estimate of the file size for the deposits being processed. Throws
     * an exception if the harvest would exhaust available disk space.
     *
     * @param Deposit[] $deposits
     */
    protected function preprocessDeposits($deposits = array()) {
        $harvestSize = 0;
        foreach($deposits as $deposit) {
            $harvestSize += $deposit->getSize();
        }
        // deposits report their sizes in 1000-byte units.
        $harvestSize *= 1000;
        $this->logger->notice("Harvest expected to consume {$harvestSize} bytes.");
        $harvestPath = $this->filePaths->getHarvestDir();

        $remaining = (disk_free_space($harvestPath) - $harvestSize) / disk_total_space($harvestPath);
        if($remaining < 0.10) {
            // less than 10% remaining
            $p = round($remaining * 100, 1);
            $this->logger->critical("Harvest - Harvest would leave less than {$p}% disk space remaining.");
            throw new Exception("Harvest would leave {$p}% disk space remaining.");
        }
    }

    /**
     * Process one deposit. Fetch the data and write it to the file system.
     * Updates the deposit status.
     *
     * @param Deposit $deposit
     * @return type
     */
    protected function processDeposit(Deposit $deposit) {
		$this->logger->notice("harvest - {$deposit->getDepositUuid()}");
        $this->checkSize($deposit);
        $response = $this->fetchDeposit($deposit->getUrl(), $deposit->getSize());
        $deposit->setFileType($response->getHeader('Content-Type'));
        $filePath = $this->filePaths->getHarvestFile($deposit);
        return $this->writeDeposit($filePath, $response);
    }

    /**
     * {@inheritDoc}
     */
    public function nextState() {
        return "harvested";
    }
    
    public function errorState() {
        return "harvest-error";
    }

    /**
     * {@inheritDoc}
     */
    public function processingState() {
        return "depositedByJournal";
    }

    /**
     * {@inheritDoc}
     */
    public function failureLogMessage() {
        return "Deposit harvest failed.";
    }

    /**
     * {@inheritDoc}
     */
    public function successLogMessage() {
        return "Deposit harvest succeeded.";
    }
}

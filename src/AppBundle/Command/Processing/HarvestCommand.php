<?php

namespace AppBundle\Command\Processing;

use Exception;
use AppBundle\Entity\Deposit;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Harvest a deposit from a journal.
 *
 * @todo Check file sizes before downloading with a HTTP HEAD request.
 */
class HarvestCommand extends AbstractProcessingCmd {

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
     * @param string $data
     * @return boolean
     */
    protected function writeDeposit($path, $data) {
        $this->logger->info("Writing deposit to {$path}");
        try {
            $this->fs->dumpFile($path, $data);
        } catch(IOException $ex) {
            $this->logger->error("Cannot write data to {$path}.");
            $this->logger->error($ex->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Fetch a deposit URL with Guzzle. Returns the data on success or false
     * on failure.
     *
     * @param string $url
     * @return false on failure, or a Guzzle Response on success.
     */
    protected function fetchDeposit($url, $expected) {
        $client = new Client();
        try {
            $head = $client->head($url);
            $size = $head->getHeader('Content-Length');
            $expectedSize = $expected * 1000;
            if($head->getStatusCode() === 200 || $size === null || $size === '') {
                if(abs($expectedSize - $size) / $size > 0.02) {
                    throw new Exception("Expected file size {$expectedSize} is not close to reported size {$size}");
                }
            } else {
                $this->logger->warning("Cannot check deposit {$url} file size without downloading.");
            }

            $response = $client->get($url);
            $this->logger->info("Harvest {$url} - {$response->getStatusCode()} - {$response->getHeader('Content-Length')}");
        } catch (RequestException $e) {
            $this->logger->error($e);
            if ($e->hasResponse()) {
                $this->logger->error($e->getResponse()->getStatusCode() . ' ' . $this->logger->error($e->getResponse()->getReasonPhrase()));
            }
            return false;
        }
        return $response;
    }

    protected function checkSize($deposit) {
        $client = new Client();
        try {
            $head = $client->head($deposit->getUrl());
            if($head->getStatusCode() !== 200) {
                throw new Exception("HTTP HEAD request cannot check file size: HTTP {$head->getStatusCode()} - {$head->getReasonPhrase()} - {$deposit->getUrl()}");
            }
            $size = $head->getHeader('Content-Length');
            if($size === null || $size === '') {
                throw new Exception("HTTP HEAD response does not include file size - {$deposit->getUrl()}");
            }
            $expectedSize = $deposit->getSize() * 1000;
            if(abs($expectedSize - $size) / $size > 0.02) {
                throw new Exception("Expected file size {$expectedSize} is not close to reported size {$size} - {$deposit->getUrl()}");
            }
        } catch(RequestException $e) {
            $this->logger->error($e->getResponse()->getStatusCode() . ' ' . $this->logger->error($e->getResponse()->getReasonPhrase()));
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
            $this->checkSize($deposit);
        }
        // deposits report their sizes in 1000-byte units.
        $harvestSize *= 1000;
        $this->logger->notice("Harvest expected to consume {$harvestSize} bytes.");
        $harvestPath = $this->getHarvestDir();

        $remaining = (disk_free_space($harvestPath) - $harvestSize) / disk_total_space($harvestPath);
        if($remaining < 0.10) {
            // less than 10% remaining
            $p = round($remaining * 100, 1);
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
        $response = $this->fetchDeposit($deposit->getUrl(), $deposit->getSize());
        if ($response === false) {            
            return false;
        }
        $data = $response->getBody();
        $deposit->setFileType($response->getHeader('Content-Type'));

        $journal = $deposit->getJournal();
        $dir = $this->getHarvestDir($journal);
        if (!$this->checkPerms($dir)) {
            return false;
        }
        $filePath = $dir . '/' . $deposit->getFileName();
        if (!$this->writeDeposit($filePath, $data)) {
            return false;
        }
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function nextState() {
        return "harvested";
    }

    /**
     * {@inheritDoc}
     */
    public function processingState() {
        return "deposited";
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

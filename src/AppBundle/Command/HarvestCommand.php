<?php

namespace AppBundle\Command;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\DepositRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class HarvestCommand extends AbstractProcessingCmd {

    /**
     * Configure the command.
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
    protected function fetchDeposit($url) {
        $client = new Client();
        try {
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

    /**
     * Process one deposit. Fetch the data and write it to the file system.
     * Updates the deposit status.
     *
     * @param Deposit $deposit
     * @return type
     */
    protected function processDeposit(Deposit $deposit) {
        $response = $this->fetchDeposit($deposit->getUrl());
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

    public function nextState() {
        return "harvested";
    }

    public function processingState() {
        return "deposited";
    }

    public function failureLogMessage() {
        return "Deposit harvest failed.";
    }

    public function successLogMessage() {
        return "Deposit harvest succeeded.";
    }

}

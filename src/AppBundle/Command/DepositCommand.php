<?php

namespace AppBundle\Command;

use AppBundle\Entity\Deposit;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Filesystem\Exception\IOException;

class DepositCommand extends AbstractProcessingCmd {

    /**
     * Configure the command.
     */
    protected function configure() {
        $this->setName('pln:deposit');
        $this->setDescription('Send deposits to LockssOMatic.');
        parent::configure();
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
        return "deposited";
    }

    public function processingState() {
        return "reserialized";
    }

    public function failureLogMessage() {
        return "Deposit to Lockssomatic failed.";
    }

    public function successLogMessage() {
        return "Deposit to Lockssomatic succeeded.";
    }

}

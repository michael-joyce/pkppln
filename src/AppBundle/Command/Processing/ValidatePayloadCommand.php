<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;

class ValidatePayloadCommand extends AbstractProcessingCmd {

    /**
     * Configure the command.
     */
    protected function configure() {
        $this->setName('pln:validate-payload');
        $this->setDescription('Validate PLN deposit packages.');
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
        $journal = $deposit->getJournal();

        $dir = $this->getHarvestDir($journal);
        $depositPath = $dir . '/' . $deposit->getFileName();

        if( ! $this->fs->exists($depositPath)) {
            $this->logger->error("Deposit file {$depositPath} does not exist");            
            return false;
        }

        $checksumValue = null;
        switch(strtoupper($deposit->getChecksumType())) {
            case 'SHA-1':
            case 'SHA1':
                $checksumValue = sha1_file($depositPath);
                break;
            case 'MD5':
                $checksumValue = md5_file($depositPath);
                break;
            default:
                $this->logger->error("Deposit checksum type {$deposit->getChecksumType()} unknown.");
                return false;
        }
        if($checksumValue !== $deposit->getChecksumValue()) {
            $this->logger->error("Deposit file {$depositPath} checksum does not match.");
            return false;
        }

        $this->logger->info("Deposit {$depositPath} validated.");
        return true;
    }

    public function nextState() {
        return "payload-validated";
    }

    public function processingState() {
        return "harvested";
    }

    public function failureLogMessage() {
        return "Payload checksum validation failed.";
    }

    public function successLogMessage() {
        return "Payload checksum validation succeeded.";
    }

}

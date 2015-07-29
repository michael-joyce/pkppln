<?php

namespace AppBundle\Command;

require 'vendor/scholarslab/bagit/lib/bagit.php';

use AppBundle\Entity\Deposit;
use BagIt;
use Exception;
use ZipArchive;

class ValidateBagCommand extends AbstractProcessingCmd {

    /**
     * Configure the command.
     */
    protected function configure() {
        $this->setName('pln:validate-bag');
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

        $harvestedPath = $this->getHarvestDir($journal) . '/' . $deposit->getFileName();
        $extractedPath = $this->getProcessingDir($journal) . '/' . $deposit->getFileUuid();

        $this->logger->info("Processing {$harvestedPath}");

        if (!$this->fs->exists($harvestedPath)) {
            $this->logger->error("Deposit file {$harvestedPath} does not exist");
            return false;
        }

        $this->checkPerms($extractedPath);
        $zipFile = new ZipArchive();
        if($zipFile->open($harvestedPath) === false) {
            $this->logger->error("Cannot open {$harvestedPath}: " . $zipFile->getStatusString());
            return false;
        }

        $this->logger->info("Extracting to {$extractedPath}");
        if($zipFile->extractTo($extractedPath) === false) {
            $this->logger->error("Cannot extract to {$this->depositDir} "  . $zipFile->getStatusString());
            return false;
        }

        $this->logger->info("Validating {$extractedPath}/bag");
        $bag = new BagIt($extractedPath . '/bag');
        $bag->validate();
        if(count($bag->getBagErrors()) > 0) {
            foreach($bag->getBagErrors() as $error) {
                $this->logger->error("Bagit validation error for {$error[0]} - {$error[1]}");
            }
            return false;
        }
        return true;
    }

    public function nextState() {
        return "bag-validated";
    }

    public function processingState() {
        return "payload-validated";
    }

}

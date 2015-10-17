<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use BagIt;
use Exception;
use ZipArchive;

/**
 * Validate a bag, according to the bagit spec.
 */
class ValidateBagCommand extends AbstractProcessingCmd {

    /**
     * {@inheritDoc}
     */
    protected function configure() {
        $this->setName('pln:validate-bag');
        $this->setDescription('Validate PLN deposit packages.');
        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    protected function processDeposit(Deposit $deposit) {
        $journal = $deposit->getJournal();

        $harvestedPath = $this->getHarvestDir($journal) . '/' . $deposit->getFileName();
        $extractedPath = $this->getBagPath($deposit);

        $this->logger->info("Processing {$harvestedPath}");

        if (!$this->fs->exists($harvestedPath)) {
            $this->logger->error("Deposit file {$harvestedPath} does not exist");
            return false;
        }

        $zipFile = new ZipArchive();
        if($zipFile->open($harvestedPath) === false) {
            $this->logger->error("Cannot open {$harvestedPath}: " . $zipFile->getStatusString());
            return false;
        }

        $this->checkPerms($extractedPath);
        $this->logger->info("Extracting to {$extractedPath}");

        $temp = tempnam(sys_get_temp_dir(), '');
        if(file_exists($temp)) { unlink($temp);}
        mkdir($temp);

        if($zipFile->extractTo($temp) === false) {
            $this->logger->error("Cannot extract to {$temp} "  . $zipFile->getStatusString());
            return false;
        }
        rename($temp . '/' . $deposit->getDepositUuid(), $extractedPath);
        
        $this->logger->info("Validating {$extractedPath}");
        $bag = new BagIt($extractedPath);
        $bag->validate();
        if(count($bag->getBagErrors()) > 0) {
            foreach($bag->getBagErrors() as $error) {
                $this->logger->error("Bagit validation error for {$error[0]} - {$error[1]}");
            }
            return false;
        }
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function nextState() {
        return "bag-validated";
    }

    /**
     * {@inheritDoc}
     */
    public function processingState() {
        return "payload-validated";
    }

    /**
     * {@inheritDoc}
     */
    public function failureLogMessage() {
        return "Bag checksum validation failed.";
    }

    /**
     * {@inheritDoc}
     */
    public function successLogMessage() {
        return "Bag checksum validation succeeded.";
    }

}

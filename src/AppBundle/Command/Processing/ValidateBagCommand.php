<?php

namespace AppBundle\Command\Processing;

// sigh. Something isn't autoloading here. 
require_once('vendor/scholarslab/bagit/lib/bagit.php');

use AppBundle\Entity\Deposit;
use BagIt;
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
        $harvestedPath = $this->filePaths->getHarvestFile($deposit);
        $extractedPath = $this->filePaths->getProcessingBagPath($deposit);
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

        $this->logger->info("Extracting to {$extractedPath}");

        if(file_exists($extractedPath)) {
            $this->logger->warning("{$extractedPath} is not empty. Removing it.");
            $this->fs->remove($extractedPath);
        }
		// dirname() is neccessary here - extractTo will create one layer too many 
		// directories otherwise.
        if($zipFile->extractTo(dirname($extractedPath)) === false) {
            $this->logger->error("Cannot extract to {$extractedPath} "  . $zipFile->getStatusString());
            return false;
        }
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

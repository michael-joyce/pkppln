<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use BagIt;

/**
 * Take a processed bag and reserialize it.
 */
class ReserializeBagCommand extends AbstractProcessingCmd {

    /**
     * {@inheritDoc}
     */
    protected function configure() {
        $this->setName('pln:reserialize');
        $this->setDescription('Reserialize the deposit bag.');
        parent::configure();
    }

    /**
     * {@inheritDoc}
     */
    protected function processDeposit(Deposit $deposit) {
        $extractedPath = $this->getBagPath($deposit);
        $this->logger->info("Reserializing {$extractedPath}");

        $temp = tempnam(sys_get_temp_dir(), 'deposit_processing_log');
        if (file_exists($temp)) {
            unlink($temp);
        }
        file_put_contents($temp, $deposit->getProcessingLog());
        $bag = new BagIt($extractedPath);
        $bag->addFile($temp, 'data/processing-log.txt');
        $dir = $this->getStagingDir($deposit->getJournal());
        if (!$this->checkPerms($dir)) {
            return false;
        }
        $bag->setBagInfoData('External-Identifier', $deposit->getFileUuid());        
        $bag->setBagInfoData('PKP-PLN-Deposit-UUID', $deposit->getDepositUuid());
        $bag->setBagInfoData('PKP-PLN-File-UUID', $deposit->getFileUuid());
        $bag->setBagInfoData('PKP-PLN-Deposit-Received', $deposit->getReceived()->format('c'));
        $bag->setBagInfoData('PKP-PLN-Deposit-Volume', $deposit->getVolume());
        $bag->setBagInfoData('PKP-PLN-Deposit-Issue', $deposit->getIssue());
        $bag->setBagInfoData('PKP-PLN-Deposit-PubDate', $deposit->getPubDate()->format('c'));

        $journal = $deposit->getJournal();
        $bag->setBagInfoData('PKP-PLN-Journal-UUID', $journal->getUuid());
        $bag->setBagInfoData('PKP-PLN-Journal-Title', $journal->getTitle());
        $bag->setBagInfoData('PKP-PLN-Journal-ISSN', $journal->getIssn());
        $bag->setBagInfoData('PKP-PLN-Journal-URL', $journal->getUrl());
        $bag->setBagInfoData('PKP-PLN-Publisher-Name', $journal->getPublisherName());
        $bag->setBagInfoData('PKP-PLN-Publisher-URL', $journal->getPublisherUrl());

        $bag->update();        
        $path = $this->getStagingDir($deposit->getJournal()) . '/' . $deposit->getFileUuid() . '.zip';
        if(file_exists($path)) {
            $this->logger->warning("{$path} already exists. Removing it.");
            unlink($path);
        }
        $bag->package($path, 'zip');
        $deposit->setPackagePath($path);
        $deposit->setPackageSize(filesize($path));
        $deposit->setPackageChecksumType('sha1');
        $deposit->setPackageChecksumValue(hash_file('sha1', $path));
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function failureLogMessage() {
        return "Bag Reserialize failed.";
    }

    /**
     * {@inheritDoc}
     */
    public function nextState() {
        return "reserialized";
    }

    /**
     * {@inheritDoc}
     */
    public function processingState() {
        return "xml-validated";
    }

    /**
     * {@inheritDoc}
     */
    public function successLogMessage() {
        return "Bag Reserialize succeeded.";
    }

}

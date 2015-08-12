<?php

namespace AppBundle\Command;

use AppBundle\Entity\Deposit;
use BagIt;

class ReserializeBagCommand extends AbstractProcessingCmd {

    protected function configure() {
        $this->setName('pln:reserialize');
        $this->setDescription('Reserialize the deposit bag.');
        parent::configure();
    }

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
        $bag->package($this->getStagingDir($deposit->getJournal()) . '/' . $deposit->getFileUuid(), 'zip');
        $deposit->setPackagePath($this->getStagingDir($deposit->getJournal()) . '/' . $deposit->getFileUuid() . '.zip');
        return true;
    }

    public function failureLogMessage() {
        return "Bag Reserialize failed.";
    }

    public function nextState() {
        return "reserialized";
    }

    public function processingState() {
        return "xml-validated";
    }

    public function successLogMessage() {
        return "Bag Reserialize succeeded.";
    }

}

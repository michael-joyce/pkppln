<?php

namespace AppBundle\Command\Processing;

require_once('vendor/scholarslab/bagit/lib/bagit.php');

use AppBundle\Entity\AuContainer;
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
     * Add the metadata from the database to the bag-info.txt file.
     * 
     * @param BagIt $bag
     * @param Deposit $deposit
     */
	protected function addMetadata(BagIt $bag, Deposit $deposit) {
        $bag->bagInfoData = array(); // @todo this is very very bad. Once BagItPHP is updated it should be $bag->clearAllBagInfo();
        $bag->setBagInfoData('External-Identifier', $deposit->getDepositUuid());        
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
		$bag->setBagInfoData('PKP-PLN-Journal-Email', $journal->getEmail());
        $bag->setBagInfoData('PKP-PLN-Publisher-Name', $journal->getPublisherName());
        $bag->setBagInfoData('PKP-PLN-Publisher-URL', $journal->getPublisherUrl());

		foreach($deposit->getLicense() as $key => $value) {
			$bag->setBagInfoData('PKP-PLN-' . $key, $value);
		}
	}

    /**
     * {@inheritDoc}
     */
    protected function processDeposit(Deposit $deposit) {
        $extractedPath = $this->filePaths->getProcessingBagPath($deposit);		
        $this->logger->info("Reserializing {$extractedPath}");

        $temp = tempnam(sys_get_temp_dir(), 'deposit_processing_log');
        if (file_exists($temp)) {
            unlink($temp);
        }
        file_put_contents($temp, $deposit->getProcessingLog());
		
        $bag = new BagIt($extractedPath);
        $bag->addFile($temp, 'data/processing-log.txt');		
		$this->addMetadata($bag, $deposit);
        $bag->update();
        unlink($temp);
		
        $path = $this->filePaths->getStagingBagPath($deposit);
		
        if(file_exists($path)) {
            $this->logger->warning("{$path} already exists. Removing it.");
            unlink($path);
        }
		
        $bag->package($path, 'zip');
        $deposit->setPackagePath($path);
        $deposit->setPackageSize(ceil(filesize($path) / 1000)); // bytes to kb.
        $deposit->setPackageChecksumType('sha1');
        $deposit->setPackageChecksumValue(hash_file('sha1', $path));
        
        $auContainer = $this->em->getRepository('AppBundle:AuContainer')->getOpenContainer();
        if($auContainer === null) {
            $auContainer = new AuContainer();
            $this->em->persist($auContainer);
        }
        $deposit->setAuContainer($auContainer);
        $auContainer->addDeposit($deposit);
        if($auContainer->getSize() > $this->container->getParameter('pln_maxAuSize')) {
            $auContainer->setOpen(false);
            $this->em->flush($auContainer);
        }
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
        return "virus-checked";
    }

    /**
     * {@inheritDoc}
     */
    public function successLogMessage() {
        return "Bag Reserialize succeeded.";
    }

    /**
     * {@inheritDoc}
     */
    public function errorState() {
        return "reserialize-error";
    }
}

<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use AppBundle\Services\SwordClient;

/**
 * Send a fully processed deposit to LOCKSSOMatic.
 * 
 * @see SwordClient
 */
class DepositCommand extends AbstractProcessingCmd {

    /**
     * {@inheritDoc}
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
        /**
         * @var SwordClient
         */
        $client = $this->container->get('sword_client');
        $client->setLogger($this->logger);
        return $client->createDeposit($deposit);            
    }

    /**
     * {@inheritDoc}
     */
    public function nextState() {
        return "deposited";
    }

    /**
     * {@inheritDoc}
     */
    public function processingState() {
        return "reserialized";
    }

    /**
     * {@inheritDoc}
     */
    public function failureLogMessage() {
        return "Deposit to Lockssomatic failed.";
    }

    /**
     * {@inheritDoc}
     */
    public function successLogMessage() {
        return "Deposit to Lockssomatic succeeded.";
    }
}

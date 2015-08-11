<?php

namespace AppBundle\Command;

use AppBundle\Entity\Deposit;
use AppBundle\Services\SwordClient;
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
        /**
         * @var SwordClient
         */
        $client = $this->container->get('sword_client');
        $client->setLogger($this->logger);
        $client->serviceDocument($deposit->getJournal());
        return false;
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

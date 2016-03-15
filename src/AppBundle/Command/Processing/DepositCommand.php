<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use AppBundle\Services\SwordClient;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send a fully processed deposit to LOCKSSOMatic.
 * 
 * @see SwordClient
 */
class DepositCommand extends AbstractProcessingCmd {

    /**
     * @var SwordClient
     */
    private $client;
    
    public function __construct($name = null) {
        parent::__construct($name);
    }
    
    /**
     * {@inheritDoc}
     */
    protected function configure() {
        $this->setName('pln:deposit');
        $this->setDescription('Send deposits to LockssOMatic.');
        parent::configure();
    }
    
    public function setContainer(ContainerInterface $container = null) {
        parent::setContainer($container);
        $this->client = $container->get('sword_client');
        $this->client->setLogger($this->logger);
    }

    /**
     * Process one deposit. Fetch the data and write it to the file system.
     * Updates the deposit status.
     *
     * @param Deposit $deposit
     * @return type
     */
    protected function processDeposit(Deposit $deposit) {
        $this->logger->notice("Sending deposit {$deposit->getDepositUuid()}");
        return $this->client->createDeposit($deposit);            
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

    public function errorState() {
        return "deposit-error";
    }
}

<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use AppBundle\Services\FilePaths;
use AppBundle\Services\SwordClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Send a fully processed deposit to LOCKSSOMatic.
 * 
 * @see SwordClient
 */
class StatusCommand extends AbstractProcessingCmd {

    /**
     * @var SwordClient
     */
    private $client;
    
    /**
     * @var boolean
     */
    private $cleanup;
    
    public function __construct($name = null) {
        parent::__construct($name);
    }
    
    /**
     * {@inheritDoc}
     */
    protected function configure() {
        $this->setName('pln:status');
        $this->setDescription('Check the status of deposits in LOCKSSOMatic.');
        parent::configure();
    }
    
    public function setContainer(ContainerInterface $container = null) {
        parent::setContainer($container);
        $this->cleanup = !$this->container->getParameter('remove_complete_deposits');
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
        $this->logger->notice("Checking deposit {$deposit->getDepositUuid()}");
        $statement = $this->client->statement($deposit);
        $status = (string)$statement->xpath('//atom:category[@scheme="http://purl.org/net/sword/terms/state"]/@term')[0];
        $deposit->setPlnState($status);
        if($status === 'agreement' && $this->cleanup) {
            $this->logger->notice("Deposit complete. Removing processing files.");
            $this->fs->remove(array(
                $this->filePaths->getHarvestFile($deposit),
                $this->filePaths->getProcessingBagPath($deposit),
                $this->filePaths->getStagingBagPath($deposit)
            ));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function nextState() {
        return "complete";
    }

    /**
     * {@inheritDoc}
     */
    public function processingState() {
        return "deposited";
    }

    /**
     * {@inheritDoc}
     */
    public function failureLogMessage() {
        return "Deposit status failed.";
    }

    /**
     * {@inheritDoc}
     */
    public function successLogMessage() {
        return "Deposit status succeeded.";
    }

    public function errorState() {
        return "status-error";
    }
}

<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use AppBundle\Services\FilePaths;
use AppBundle\Services\SwordClient;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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

    private function delTree($path) {
        $directoryIterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $fileIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($fileIterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($path);
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
        $status = (string) $statement->xpath('//atom:category[@scheme="http://purl.org/net/sword/terms/state"]/@term')[0];
        $deposit->setPlnState($status);
        if ($status === 'agreement' && $this->cleanup) {
            $this->logger->notice("Deposit complete. Removing processing files for deposit {$deposit->getId()}.");
            unlink($this->filePaths->getHarvestFile($deposit));
            $this->deltree($this->filePaths->getProcessingBagPath($deposit));
            unlink($this->filePaths->getStagingBagPath($deposit));
            return true;
        }
        return false;
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

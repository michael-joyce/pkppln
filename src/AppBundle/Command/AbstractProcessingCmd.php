<?php

namespace AppBundle\Command;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\DepositRepository;
use AppBundle\Entity\Journal;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dump\Container;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractProcessingCmd extends ContainerAwareCommand {

    /**
     * @var Registry
     */
    protected $em;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var string
     */
    protected $depositDir;

    /**
     * Set the service container, and initialize the command.
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null) {
        parent::setContainer($container);
        $this->container = $container;
        $this->logger = $container->get('monolog.logger.processing');
        $this->em = $container->get('doctrine')->getManager();
        $this->fs = new Filesystem();
        $this->depositDir = $container->getParameter('pln_harvest_directory');
        if (!$this->fs->isAbsolutePath($this->depositDir)) {
            $this->depositDir = $this->container->get('kernel')->getRootDir() . '/' . $this->depositDir;
        }
    }

    public final function getHarvestDir(Journal $journal) {
        $path = $this->container->getParameter('pln_harvest_directory') . '/' . $journal->getUuid();
        if ($this->fs->isAbsolutePath($path)) {
            return $path;
        }
        $root = dirname($this->container->get('kernel')->getRootDir());
        return $root . '/' . $path;
    }

    public final function getProcessingDir(Journal $journal) {
        $path = $this->container->getParameter('pln_processing_directory') . '/' . $journal->getUuid();
        if ($this->fs->isAbsolutePath($path)) {
            return $path;
        }
        $root = dirname($this->container->get('kernel')->getRootDir());
        return $root . '/' . $path;
    }

    public final function getStagingDir(Journal $journal) {
        $path = $this->container->getParameter('pln_staging_directory') . '/' . $journal->getUuid();
        if ($this->fs->isAbsolutePath($path)) {
            return $path;
        }
        $root = dirname($this->container->get('kernel')->getRootDir());
        return $root . '/' . $path;
    }

    public final function getBagPath(Deposit $deposit) {
        return $this->getProcessingDir($deposit->getJournal()) 
                . '/' 
                . $deposit->getDepositUuid() 
                . '/'
                . $deposit->getFileUuid();
    }

    protected function configure() {
        $this->addOption(
                'dry-run', 'd', InputOption::VALUE_NONE, 'Do not update processing status'
        );
        $this->addOption(
                'force', 'f', InputOption::VALUE_NONE, 'Force the processing state to be updated'
        );
    }

    /**
     * Check the permissions of a path, make sure it exists and is writeable.
     * Returns true if the directory exists and is writeable.
     *
     * @param string $path
     * @return boolean
     */
    protected function checkPerms($path) {
        try {
            if (!$this->fs->exists($path)) {
                $this->logger->warn("Creating directory {$path}");
                $this->fs->mkdir($path);
            }
        } catch (IOExceptionInterface $e) {
            $this->logger->error("Error creating directory {$path}");
            $this->logger->error($e);
            return false;
        }
        return true;
    }

    /**
     * Process one deposit return true on success and false on failure.
     *
     * @param Deposit $deposit
     * @return boolean
     */
    abstract protected function processDeposit(Deposit $deposit);

    abstract function processingState();

    abstract function nextState();

    abstract function successLogMessage();

    abstract function failureLogMessage();

    /**
     * Code to run before executing the command.
     */
    protected function preExecute() {
        // do nothing, let subclasses override if needed.
    }

    /**
     * Execute the command. Get all the deposits needing to be harvested.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->preExecute();

        /** @var DepositRepository */
        $repo = $this->em->getRepository('AppBundle:Deposit');

        $this->checkPerms($this->depositDir);

        $deposits = $repo->findByState($this->processingState());
        $count = count($deposits);

        $this->logger->info("Processing {$count} deposits.");

        foreach ($deposits as $deposit) {
            /** @var $deposit Deposit */
            $result = $this->processDeposit($deposit);
            if ($input->getOption('dry-run')) {
                continue;
            }
            if ($result) {
                $deposit->setState($this->nextState());
                $deposit->setOutcome('success');
                $deposit->addToProcessingLog($this->successLogMessage());
                continue;
            }
            $deposit->addToProcessingLog($this->failureLogMessage());
            if($input->getOption('force')) {
                $deposit->setState($this->nextState());
                $deposit->setOutcome('forced');
                $deposit->addToProcessingLog("Ignoring error.");
                continue;
            }
            $deposit->setOutcome('failure');
        }
        $this->em->flush();
    }

}

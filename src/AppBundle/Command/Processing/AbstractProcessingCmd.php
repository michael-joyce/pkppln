<?php

namespace AppBundle\Command\Processing;

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

/**
 * Commands that process the deposits should extend this class, which
 * provides common functions for updating status, and will automatically
 * fetch the deposits needing to be processed.
 */
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

    /**
     * Get named file path from the parameters.yml file. If it is a relative
     * path, it will be made absolute relative to the kernel's root dir.
     *
     * @see AppKernel#getRootDir
     * @param string $parameterName
     * @param Journal $journal
     * @return string
     */
    protected function absolutePath($parameterName, Journal $journal = null) {
        $path = $this->container->getParameter($parameterName);
        if( ! substr($path, -1) !== '/') {
            $path .= '/';
        }
        if ( ! $this->fs->isAbsolutePath($path)) {
            $root = dirname($this->container->get('kernel')->getRootDir());
            $path =  $root . '/' . $path;
        }
        if($journal !== null) {
            return  $path . $journal->getUuid();
        }
        return $path;
    }

    /**
     * Get the harvest directory.
     *
     * @see AppKernel#getRootDir
     * @param Journal $journal
     * @return string
     */
    public final function getHarvestDir(Journal $journal = null) {
        return $this->absolutePath('pln_harvest_directory', $journal);
    }

    /**
     * Get the processing directory.
     *
     * @param Journal $journal
     * @return string
     */
    public final function getProcessingDir(Journal $journal) {
        return $this->absolutePath('pln_processing_directory', $journal);
    }

    /**
     * Get the staging directory for processed deposits.
     *
     * @param Journal $journal
     * @return string
     */
    public final function getStagingDir(Journal $journal) {
        return $this->absolutePath('pln_staging_directory', $journal);
    }

    /**
     * Get the path to a deposit bag being processed.
     *
     * @param Deposit $deposit
     * @return string
     */
    public final function getBagPath(Deposit $deposit) {
        return $this->getProcessingDir($deposit->getJournal())
                . '/'
                . $deposit->getDepositUuid()
                . '/'
                . $deposit->getFileUuid();
    }

    /**
     * Set the command-line options for the processing commands.
     */
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
     * Preprocess the list of deposits. 
     * 
     * @param Deposit[] $deposits
     */
    protected function preprocessDeposits($deposits = array()) {
        // do nothing by default.
    }

    /**
     * Process one deposit return true on success and false on failure.
     *
     * @param Deposit $deposit
     * @return boolean
     */
    abstract protected function processDeposit(Deposit $deposit);

    /**
     * Deposits in this state will be processed by the commands.
     */
    abstract function processingState();

    /**
     * Successfully processed deposits will be given this state.
     */
    abstract function nextState();

    /**
     * Successfully processed deposits will be given this log message.
     */
    abstract function successLogMessage();

    /**
     * Failed deposits will be given this log message.
     */
    abstract function failureLogMessage();

    /**
     * Code to run before executing the command.
     */
    protected function preExecute() {
        // do nothing, let subclasses override if needed.
    }

    /**
     * Execute the command. Get all the deposits needing to be harvested. Each
     * deposit will be passed to the commands processDeposit() function.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected final function execute(InputInterface $input, OutputInterface $output) {
        $this->preExecute();

        /** @var DepositRepository */
        $repo = $this->em->getRepository('AppBundle:Deposit');

        $this->checkPerms($this->depositDir);

        $deposits = $repo->findByState($this->processingState());
        $count = count($deposits);

        $this->logger->info("Processing {$count} deposits.");
        $this->preprocessDeposits($deposits);

        foreach ($deposits as $deposit) {
            /** @var $deposit Deposit */
            $result = $this->processDeposit($deposit);
            if ($input->getOption('dry-run')) {
                continue;
            }
            if ($result) {
                $deposit->setState($this->nextState());
                $deposit->addToProcessingLog($this->successLogMessage());
                continue;
            }
            $deposit->addToProcessingLog($this->failureLogMessage());
            if ($input->getOption('force')) {
                $deposit->setState($this->nextState());
                $deposit->addToProcessingLog("Ignoring error.");
                continue;
            }
        }
        $this->em->flush();
    }

}

<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\DepositRepository;
use AppBundle\Entity\Journal;
use AppBundle\Services\FilePaths;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Exception;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
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
	 * @var FilePaths
	 */
	protected $filePaths;

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
		$this->filePaths = $container->get('filepaths');
        $this->fs = new Filesystem();
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

        $deposits = $repo->findByState($this->processingState());
        $count = count($deposits);

        $this->logger->info("Processing {$count} deposits.");
        $this->preprocessDeposits($deposits);

        foreach ($deposits as $deposit) {
            /** @var $deposit Deposit */
			$errorMsg = null;
			$result = false;
			try {
				$result = $this->processDeposit($deposit);
			} catch(Exception $e) {
				$errorMsg = $e->getMessage();
				$this->logger->error($errorMsg);
			}
			
            if ($input->getOption('dry-run')) {
                continue;
            }
            if ($result) {
                $deposit->setState($this->nextState());
                $deposit->addToProcessingLog($this->successLogMessage());
				$this->em->flush($deposit);
                continue;
            }
            $deposit->addToProcessingLog($this->failureLogMessage());
			if($errorMsg !== null) {
				$deposit->addToProcessingLog($errorMsg);
			}
            if ($input->getOption('force')) {
                $deposit->setState($this->nextState());
                $deposit->addToProcessingLog("Ignoring error.");
            }
			$this->em->flush($deposit);
        }
    }

}

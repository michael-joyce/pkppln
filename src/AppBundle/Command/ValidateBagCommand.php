<?php

namespace AppBundle\Command;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\DepositRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dump\Container;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

require 'vendor/scholarslab/bagit/lib/bagit.php';
use \Bagit;

class ValidateBagCommand extends ContainerAwareCommand {

    /**
     * @var Registry
     */
    private $em;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * @var string
     */
    private $depositDir;

    /**
     * Configure the command.
     */
    protected function configure() {
        $this->setName('pln:validate-bag');
        $this->setDescription('Validate PLN deposit packages.');
    }

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
        $this->depositDir = $container->getParameter('pln_service_directory');
        if (!$this->fs->isAbsolutePath($this->depositDir)) {
            $this->depositDir = $this->container->get('kernel')->getRootDir() . '/' . $this->depositDir;
        }
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
     * Process one deposit. Fetch the data and write it to the file system.
     * Updates the deposit status.
     *
     * @param Deposit $deposit
     * @return type
     */
    protected function processDeposit(Deposit $deposit) {
        $journal = $deposit->getJournal();

        $journalDir = $this->depositDir . '/' . $journal->getUuid();
        $depositPath = $journalDir . '/' . $deposit->getFileName();

        if (!$this->fs->exists($depositPath)) {
            $this->logger->error("Deposit file {$depositPath} does not exist");
            $deposit->setOutcome("failure");
            return;
        }

        $this->logger->info("Processing {$depositPath}");

        $bag = new BagIt($depositPath);
        $bag->validate();
        if(count($bag->getBagErrors()) > 0) {
            foreach($bag->getBagErrors() as $error) {
                $this->logger->error("Bagit validation error for {$error[0]} - {$error[1]}");
            }
            $deposit->setOutcome("failure");
        }
        $deposit->setOutcome("success");
        $deposit->setState("bagValidated");
    }

    /**
     * Execute the command. Get all the deposits needing to be harvested.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        /** @var DepositRepository */
        $repo = $this->em->getRepository('AppBundle:Deposit');

        $this->checkPerms($this->depositDir);

        $deposits = $repo->findByState('payloadValidated');
        $count = count($deposits);

        $this->logger->info("Validating {$count} bags.");

        foreach ($deposits as $deposit) {
            $this->processDeposit($deposit);
        }
        $this->em->flush();
    }

}

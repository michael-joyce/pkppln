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

class ValidatePayloadCommand extends ContainerAwareCommand {

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
        $this->setName('pln:validate-payload');
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
        if( ! $this->fs->isAbsolutePath($this->depositDir)) {
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

        if( ! $this->fs->exists($depositPath)) {
            $this->logger->error("Deposit file {$depositPath} does not exist");
            $deposit->setOutcome("failure");
            return;
        }

        $checksumValue = null;
        switch(strtoupper($deposit->getChecksumType())) {
            case 'SHA-1':
            case 'SHA1':
                $checksumValue = sha1_file($depositPath);
                break;
            case 'MD5':
                $checksumValue = md5_file($depositPath);
                break;
            default:
                $this->logger->error("Deposit checksum type {$deposit->getChecksumType()} unknown.");
                $deposit->setOutcome("failure");
                return;
        }
        if($checksumValue !== $deposit->getChecksumValue()) {
            $this->logger->error("Deposit file {$depositPath} checksum does not match.");
            $deposit->setOutcome("failure");
            return;
        }

        $this->logger->info("Deposit {$depositPath} validated.");
        $deposit->setState("payloadValidated");
        $deposit->setOutcome("success");
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

        $deposits = $repo->findByState('harvested');
        $count = count($deposits);

        $this->logger->info("Validating {$count} deposit packages.");

        foreach ($deposits as $deposit) {
            $this->processDeposit($deposit);
        }
        $this->em->flush();
    }

}

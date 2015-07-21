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

class HarvestCommand extends ContainerAwareCommand {

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
        $this->setName('pln:harvest');
        $this->setDescription('Harvest OJS deposits.');
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
     * Write a deposit's data to the filesystem at $path. Returns true on
     * success and false on failure.
     *
     * @param string $path
     * @param string $data
     * @return boolean
     */
    protected function writeDeposit($path, $data) {
        try {
            $this->fs->dumpFile($path, $data);
        } catch(IOException $ex) {
            $this->logger->error("Cannot write data to {$path}.");
            $this->logger->error($ex->getMessage());
            return false;
        }
        return true;
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
     * Fetch a deposit URL with Guzzle. Returns the data on success or false
     * on failure.
     *
     * @param string $url
     * @return boolean
     */
    protected function fetchDeposit($url) {
        $client = new Client();
        try {
            $response = $client->get($url);
            $this->logger->info("Harvest {$url} - {$response->getStatusCode()} - {$response->getHeader('Content-Length')}");
        } catch (RequestException $e) {
            $this->logger->error($e);
            if ($e->hasResponse()) {
                $this->logger->error($e->getResponse()->getStatusCode() . ' ' . $this->logger->error($e->getResponse()->getReasonPhrase()));
            }
            return false;
        }
        return $response->getBody();
    }

    /**
     * Process one deposit. Fetch the data and write it to the file system.
     * Updates the deposit status.
     *
     * @param Deposit $deposit
     * @return type
     */
    protected function processDeposit(Deposit $deposit) {
        $data = $this->fetchDeposit($deposit->getUrl());
        if ($data === false) {
            $deposit->setOutcome('failure');
            return;
        }
        $journal = $deposit->getJournal();

        $journalDir = $this->depositDir . '/' . $journal->getUuid();
        if (!$this->checkPerms($journalDir)) {
            $deposit->setOutcome('failure');
            return;
        }
        $filePath = $journalDir . '/' . $deposit->getFileUuid();
        if (!$this->writeDeposit($filePath, $data)) {
            $deposit->setOutcome('failure');
            return;
        }
        $deposit->setState("harvested");
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

        $deposits = $repo->findByState('deposited');
        $count = count($deposits);

        $this->logger->info("Fetching {$count} deposits.");

        foreach ($deposits as $deposit) {
            $this->processDeposit($deposit);
        }
        $this->em->flush();
    }

}

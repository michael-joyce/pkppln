<?php

namespace AppBundle\Command\Shell;

use AppBundle\Entity\Deposit;
use AppBundle\Services\FilePaths;
use AppBundle\Services\SwordClient;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Monolog\Logger;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check the status of deposits in LOCKSSOMatic.
 * 
 * @see SwordClient
 */
class CleanupCommand extends ContainerAwareCommand
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Registry
     */
    protected $em;

    /**
     * @var FilePaths
     */
    protected $filePaths;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:rebuild');
        $this->setDescription('Restore deleted deposits in the data directory.');
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->logger = $container->get('logger');
        $this->em = $container->get('doctrine')->getManager();
        $this->filePaths = $container->get('filepaths');
    }

    /**
     * Process one deposit. Fetch the data and write it to the file system.
     * Updates the deposit status, and may remove the processing files if 
     * LOCKSSOatic reports agreement.
     *
     * @param Deposit $deposit
     *
     * @return type
     */
    protected function processDeposit(Deposit $deposit, $force = false)
    {
        if ($deposit->getPlnState() === 'agreement') {
            $this->logger->notice($deposit->getDepositUuid());
            $this->delFileTree($this->filePaths->getHarvestFile($deposit), $force);
            $this->delFileTree($this->filePaths->getProcessingBagPath($deposit), $force);
            // $this->delFileTree($this->filePaths->getStagingBagPath($deposit), $force);
        }
    }

    /**
     * Execute the command. Get all the deposits needing to be harvested. Each
     * deposit will be passed to the commands processDeposit() function.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    final protected function execute(InputInterface $input, OutputInterface $output)
    {
        $force = $input->getOption('force');
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $q = $this->em->createQuery('SELECT d FROM AppBundle\Entity\Deposit d where d.plnState = :state');
        $q->setParameter('state', 'agreement');
        $iterator = $q->iterate();

        $i = 0;
        foreach ($iterator as $row) {
            $deposit = $row[0];
            $this->processDeposit($deposit, $force);
            $i++;
            if(($i % 50) === 0) {
                $this->em->clear();
                gc_collect_cycles();
            }
        }
    }

}

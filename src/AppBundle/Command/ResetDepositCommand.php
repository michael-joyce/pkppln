<?php

namespace AppBundle\Command;

use AppBundle\Entity\DepositRepository;
use Monolog\Registry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dump\Container;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * Reset the processing status for one deposit.
 */
class ResetDepositCommand extends ContainerAwareCommand {

    /**
     * @var Registry
     */
    protected $em;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Set the service container, and initialize the command.
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null) {
        parent::setContainer($container);
        $this->logger = $container->get('monolog.logger.processing');
        $this->em = $container->get('doctrine')->getManager();
    }

    /**
     * {@inheritDoc}
     */
    public function configure() {
        $this->setName('pln:reset');
        $this->setDescription('Reset deposits.');
        $this->addArgument(
                'state', InputArgument::REQUIRED, 'New state for the deposit(s)'
        );
        $this->addArgument(
                'deposit', InputArgument::IS_ARRAY, 'Deposit UUID(s) to process'
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        /** @var DepositRepository $repo */
        $repo = $this->em->getRepository('AppBundle:Deposit');

        $state = $input->getArgument('state');
        $uuids = $input->getArgument('deposit');

        foreach($uuids as $uuid) {
            $deposit = $repo->findOneBy(array('deposit_uuid' => $uuid));
            $this->logger->info("Setting {$uuid} to {$state}");
            $deposit->setState($state);
        }
        $this->em->flush();
    }

}

<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * Run all the commands in order.
 */
class HealthCheckCommand extends ContainerAwareCommand {

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * {@inheritDoc}
     */
    protected function configure() {
        $this->setName('pln:health-check');
        $this->setDescription('Find journals that have gone silent.');
        parent::configure();
    }

    /**
     * Set the service container, and initialize the command.
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null) {
        parent::setContainer($container);
        $this->logger = $container->get('monolog.logger.processing');
    }

    /**
     * Execute the runall command, which executes all the commands.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $days = $this->getContainer()->getParameter('days_silent');
        $repo = $em->getRepository('AppBundle:Journal');
        $journals = $repo->findSilent($days);
        $count = count($journals);
        $this->logger->notice("Found {$count} silent journals.");
        foreach($journals as $journal) {
            $journal->setStatus('unhealthy');
            $journal->setNotified(new DateTime());
        }
        $em->flush();

        // figure out who to notify.
        // generate the email via twig.
        // send via swiftmail.

    }

}

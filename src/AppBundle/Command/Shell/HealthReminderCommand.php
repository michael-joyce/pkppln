<?php

namespace AppBundle\Command\Shell;

use DateTime;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * Send reminders about journals that haven't contacted the PLN in a while.
 */
class HealthReminderCommand extends ContainerAwareCommand {

    /**
     * @var TwigEngine
     */
    private $templating;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * {@inheritDoc}
     */
    protected function configure() {
        $this->setName('pln:health:reminder');
        $this->setDescription('Remind admins about silent journals.');
        $this->addOption(
                'dry-run', 'd', InputOption::VALUE_NONE, 'Do not update journal status'
        );
        parent::configure();
    }

    /**
     * Set the service container, and initialize the command.
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null) {
        parent::setContainer($container);
        $this->templating = $container->get('templating');
        $this->logger = $container->get('monolog.logger.processing');
    }
    
    /**
     * Send the notifications.
     * 
     * @param integer $days
     * @param User[] $users
     * @param Journal[] $journals
     */
    protected function sendReminders($days, $users, $journals) {
        $notification = $this->templating->render('AppBundle:HealthCheck:reminder.txt.twig', array(
            'journals' => $journals,
            'days' => $days,
        ));
        $mailer = $this->getContainer()->get('mailer');
        foreach ($users as $user) {
            $message = Swift_Message::newInstance(
                'Automated reminder from the PKP PLN', 
                $notification, 
                'text/plain', 
                'utf-8'
            );
            $message->setFrom('noreplies@pkp-pln.lib.sfu.ca');
            $message->setTo($user->getEmail(), $user->getFullname());
            $mailer->send($message);
        }
    }

    /**
     * Execute the runall command, which executes all the commands.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $days = $this->getContainer()->getParameter('days_reminder');
        $journals = $em->getRepository('AppBundle:Journal')->findOverdue($days);
        $count = count($journals);
        $this->logger->notice("Found {$count} overdue journals.");
        if (count($journals) === 0) {
            return;
        }

        $users = $em->getRepository('AppUserBundle:User')->findUserToNotify();
        if (count($users) === 0) {
            $this->logger->error('No users to notify.');
            return;
        }
        $this->sendReminders($days, $users, $journals);

        foreach ($journals as $journal) {
            $journal->setNotified(new DateTime());
        }

        if (!$input->getOption('dry-run')) {
            $em->flush();
        }
    }

}

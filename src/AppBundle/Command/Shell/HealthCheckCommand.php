<?php

namespace AppBundle\Command\Shell;

use AppBundle\Entity\Journal;
use AppBundle\Services\Ping;
use AppUserBundle\Entity\User;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\XmlParseException;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * Ping all the journals that haven't contacted the PLN in a while, and send
 * notifications to interested users.
 */
class HealthCheckCommand extends ContainerAwareCommand
{
    /**
     * @var TwigEngine
     */
    private $templating;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Ping
     */
    protected $ping;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:health:check');
        $this->setDescription('Find journals that have gone silent.');
        $this->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_NONE,
            'Do not update journal status'
        );
        parent::configure();
    }

    /**
     * Set the service container, and initialize the command.
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->templating = $container->get('templating');
        $this->logger = $container->get('monolog.logger.processing');
        $this->ping = $container->get('ping');
    }

    /**
     * Send the notifications.
     * 
     * @param int       $days
     * @param User[]    $users
     * @param Journal[] $journals
     */
    protected function sendNotifications($days, $users, $journals)
    {
        $notification = $this->templating->render('AppBundle:HealthCheck:notification.txt.twig', array(
            'journals' => $journals,
            'days' => $days,
        ));
        $mailer = $this->getContainer()->get('mailer');
        foreach ($users as $user) {
            $message = Swift_Message::newInstance(
                'Automated notification from the PKP PLN',
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
     * Request a ping from a journal.
     * 
     * @todo Use the Ping service.
     *
     * @param Journal $journal
     *
     * @return bool
     */
    protected function pingJournal(Journal $journal)
    {
        $client = new Client();
        try {
            $response = $client->get($journal->getGatewayUrl());
            if ($response->getStatusCode() !== 200) {
                return false;
            }
            $xml = $response->xml();
            $element = $xml->xpath('//terms')[0];
            if ($element && isset($element['termsAccepted']) && ((string) $element['termsAccepted']) === 'yes') {
                return true;
            }
        } catch (RequestException $e) {
            $this->logger->error("Cannot ping {$journal->getUrl()}: {$e->getMessage()}");
            if ($e->hasResponse()) {
                $this->logger->error($e->getResponse()->getStatusCode().' '.$this->logger->error($e->getResponse()->getReasonPhrase()));
            }
        } catch (XmlParseException $e) {
            $this->logger->error("Cannot parse journal ping response {$journal->getUrl()}: {$e->getMessage()}");
        }

        return false;
    }

    /**
     * Execute the runall command, which executes all the commands.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $days = $this->getContainer()->getParameter('days_silent');
        $journals = $em->getRepository('AppBundle:Journal')->findSilent($days);
        $count = count($journals);
        $this->logger->notice("Found {$count} silent journals.");
        if (count($journals) === 0) {
            return;
        }

        $users = $em->getRepository('AppUserBundle:User')->findUserToNotify();
        if (count($users) === 0) {
            $this->logger->error('No users to notify.');

            return;
        }
        $this->sendNotifications($days, $users, $journals);

        foreach ($journals as $journal) {
            if ($this->pingJournal($journal)) {
                $this->logger->notice("Ping Success {$journal->getUrl()})");
                $journal->setStatus('healthy');
                $journal->setContacted(new DateTime());
            } else {
                $journal->setStatus('unhealthy');
                $journal->setNotified(new DateTime());
            }
        }

        if (!$input->getOption('dry-run')) {
            $em->flush();
        }
    }
}

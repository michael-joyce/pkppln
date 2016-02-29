<?php

namespace AppBundle\Command;

use AppBundle\Entity\Journal;
use AppBundle\Services\Ping;
use AppUserBundle\Entity\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\XmlParseException;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * Run all the commands in order.
 */
class PingWhitelistCommand extends ContainerAwareCommand {

    /**
     * @var Logger
     */
    protected $logger;
	
	/**
	 * @var Ping
	 */
	protected $ping;

    /**
     * {@inheritDoc}
     */
    protected function configure() {
        $this->setName('pln:ping-whitelist');
        $this->setDescription('Find journals running a sufficiently new version of OJS and whitelist them.');
        $this->addArgument('minVersion', InputArgument::OPTIONAL, 'Minimum version required to whitelist. Defaults to 2.4.8.0', '2.4.8.0');
        $this->addOption(
                'dry-run', 'd', InputOption::VALUE_NONE, 'Do not update the whitelist - report only.'
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
		$this->ping = $container->get('ping');
    }
    
    /**
     * Send the notifications.
     * 
     * @param integer $days
     * @param User[] $users
     * @param Journal[] $journals
     */
    protected function sendNotifications($days, $users, $journals) {
        $notification = $this->templating->render('AppBundle:HealthCheck:notification.txt.twig', array(
            'journals' => $journals,
            'days' => $days,
            'base_url' => $this->getContainer()->getParameter('staging_server_uri'),
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
    
    protected function pingJournal(Journal $journal) {
        $client = new Client();
        try {
            $response = $client->get($journal->getGatewayUrl());
            if($response->getStatusCode() !== 200) {
                return false;
            }
            $xml = $response->xml();
            $element = $xml->xpath('//ojsInfo/release')[0];
            return (string)$element;
        } catch (RequestException $e) {
            $this->logger->error("Cannot ping {$journal->getUrl()}: {$e->getMessage()}");
            if ($e->hasResponse()) {
                $this->logger->error($e->getResponse()->getStatusCode() . ' ' . $this->logger->error($e->getResponse()->getReasonPhrase()));
            }
        } catch (XmlParseException $e) {
            $this->logger->error("Cannot parse journal ping response {$journal->getUrl()}: {$e->getMessage()}");
        }
        return false;
    }

    /**
     * Execute the runall command, which executes all the commands.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $journals = $em->getRepository('AppBundle:Journal')->findAll();

        foreach ($journals as $journal) {
            $version = $this->pingJournal($journal);
            if($version) {
                $this->logger->notice("Ping Success {$version} - {$journal->getUrl()}");
            } else {
                $this->logger->notice("Ping failed {$journal->getUrl()}");
            }
        }

        if (!$input->getOption('dry-run')) {
            //$em->flush();
        }
    }

}
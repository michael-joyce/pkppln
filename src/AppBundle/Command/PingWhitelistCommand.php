<?php

namespace AppBundle\Command;

use AppBundle\Entity\Journal;
use AppBundle\Services\Ping;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\XmlParseException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Tests\Logger;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Run all the commands in order.
 */
class PingWhitelistCommand extends ContainerAwareCommand {

	/**
	 * Default version to require
	 */
	const DEFAULT_VERSION = '2.4.8.0';
	
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
        $this->addArgument('minVersion', InputArgument::OPTIONAL, "Minimum version required to whitelist.", self::DEFAULT_VERSION);
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
    
    protected function pingJournal(Journal $journal) {
        $client = new Client();
        try {
            $response = $client->get($journal->getGatewayUrl());
            if($response->getStatusCode() !== 200) {
                return false;
            }
            $xml = $response->xml();
            $element = $xml->xpath('//ojsInfo/release');
            if( ! $element || count($element) === 0) {
                $this->logger->error("Cannot find release version in ping: {$journal->getUrl()}");
                return false;
            }
            return false;//(string)$element[0];
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
		$router = $this->getContainer()->get('router');
		
        $minVersion = $input->getArgument('minVersion');
        $count = count($journals);
        $i = 0;
        
        foreach ($journals as $journal) {
            $i++;
            $fmt = sprintf("%5d", $i);
            $version = $this->pingJournal($journal);
            if( ! $version) {
				$url = $router->generate('journal_show', array('id' => $journal->getId()), UrlGeneratorInterface::ABSOLUTE_URL);
				$output->writeln("{$fmt}/{$count} - ping failed - - {$journal->getUrl()} - {$url}");
                continue;
            }
            if(version_compare($version, $minVersion, '>=')) {
                $output->writeln("{$fmt}/{$count} - Whitelist - {$version} - {$journal->getUrl()}");
            } else {
                $output->writeln("{$fmt}/{$count} - Too Old - {$version} - {$journal->getUrl()}");
            }
        }

        if (!$input->getOption('dry-run')) {
            //$em->flush();
        }
    }

}

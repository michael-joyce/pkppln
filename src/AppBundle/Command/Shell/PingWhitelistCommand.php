<?php

namespace AppBundle\Command\Shell;

use AppBundle\Entity\Whitelist;
use AppBundle\Services\Ping;
use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Tests\Logger;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Ping all the journals in the database and whitelist those that respond and
 * that are running a sufficiently recent version of OJS.
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

    /**
     * Execute the runall command, which executes all the commands.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->getContainer()->get('doctrine')->getManager();
		$router = $this->getContainer()->get('router');
		$bwlist = $this->getContainer()->get('blackwhitelist');
		$ping = $this->getContainer()->get('ping');
		
        $journals = $em->getRepository('AppBundle:Journal')->findAll();
        $minVersion = $input->getArgument('minVersion');
        $count = count($journals);
        $i = 0;
        
        foreach ($journals as $journal) {
            $i++;
            $fmt = sprintf("%5d", $i);
			
            $url = $router->generate('journal_show', array('id' => $journal->getId()), UrlGeneratorInterface::ABSOLUTE_URL);
			$uuid = $journal->getUuid();
			if($bwlist->isWhitelisted($uuid)) {
				$output->writeln("{$fmt}/{$count} - skipped (whitelisted) - - {$journal->getUrl()}");
				continue;
			}
			if($bwlist->isBlacklisted($uuid)) {
				$output->writeln("{$fmt}/{$count} - skipped (blacklisted) - - {$journal->getUrl()}");
				continue;
			}

			try {
				$response = $ping->ping($journal);
			} catch (Exception $e) {
                $output->writeln("{$fmt}/{$count} - HTTP ERROR: {$e->getMessage()} - {$journal->getUrl()} - {$url}");
				continue;
			}
			if($response->getHttpStatus() !== 200) {
				$output->writeln("{$fmt}/{$count} - {$response->getHttpStatus()} - - {$journal->getUrl()} - {$url} -  {$response->getError()}");
				continue;
			}
            if( ! $response->getOjsRelease()) {
    			$output->writeln("{$fmt}/{$count} - {$response->getHttpStatus()} - no version number - {$journal->getUrl()} - {$url}");
                continue;
            }
			$output->writeln("{$fmt}/{$count} - {$response->getHttpStatus()} - {$response->getOjsRelease()} - {$journal->getUrl()} - {$url}");
			
            if(version_compare($response->getOjsRelease(), $minVersion, '<')) {
                continue;
            }
            if($input->getOption('dry-run')) {
                continue;
            }
            $whitelist = new Whitelist();
            $whitelist->setUuid($journal->getUuid());
            $whitelist->setComment("{$journal->getUrl()} added automatically by ping-whitelist command.");
            $em->persist($whitelist);
            $em->flush();
        }
    }

}

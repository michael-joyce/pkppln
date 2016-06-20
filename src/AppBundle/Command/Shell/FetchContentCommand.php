<?php

namespace AppBundle\Command\Shell;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Journal;
use AppBundle\Services\FilePaths;
use AppBundle\Services\SwordClient;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Collection;
use Exception;
use GuzzleHttp\Client as HttpClient;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

// fetch the content for one journal. Stores it to 
// path/to/data/restored/journalId/*.zip
class FetchContentCommand extends ContainerAwareCommand {
    
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Registry
     */
    protected $em;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var FilePaths
     */
    protected $filePaths;
    
    /**
     * @var SwordClient
     */
    private $swordClient;
    
    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Set the service container, and initialize the command.
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->logger = $container->get('monolog.logger.processing');
        $this->em = $container->get('doctrine')->getManager();
        $this->filePaths = $container->get('filepaths');
        $this->swordClient = $container->get('sword_client');
        $this->fs = new Filesystem();
    }

    public function configure() {
        $this->setName('pln:fetch');
        $this->setDescription('Download the archived content for one or more journals.');
        $this->addArgument('journals', InputArgument::IS_ARRAY, 'The database ID of one or more journals.');
    }
    
    public function setHttpClient(Client $httpClient) {
        $this->httpClient = $httpClient;
    }
    
    public function getHttpClient() {
        if( ! $this->httpClient) {
            $this->httpClient = new HttpClient();
        } 
        return $this->httpClient;
    }
    
    public function fetch(Deposit $deposit, $href) {
        $client = $this->getHttpClient();
        $filepath = $this->filePaths->getRestoreDir($deposit->getJournal()) . '/' . basename($href);
        $this->logger->notice("Saving {$deposit->getJournal()->getTitle()} vol. {$deposit->getVolume()} no. {$deposit->getIssue()} to {$filepath}");
        try {
            $client->get($href, array(
                'allow_redirects' => false,
                'decode_content' => false,
                'save_to' => $filepath,
            ));
            $hash = strtoupper(hash_file($deposit->getPackageChecksumType(), $filepath));
            if($hash !== $deposit->getPackageChecksumValue()) {
                $this->logger->warning("Package checksum failed. Expected {$deposit->getPackageChecksumValue()} but got {$hash}");
            }
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
        }
    }
    
    public function downloadJournal(Journal $journal) {
        foreach($journal->getDeposits() as $deposit) {
            $statement = $this->swordClient->statement($deposit);
            $originals = $statement->xpath('//sword:originalDeposit');

            foreach($originals as $element) {
                $this->fetch($deposit, $element['href']);
            }
        }
    }
    
    /**
     * 
     * @param array $journalIds
     * @return Collection|Journal[]
     */
    public function getJournals($journalIds) {
        return $this->em->getRepository('AppBundle:Journal')->findBy(array('id' => $journalIds));
    }
    
    public function execute(InputInterface $input, OutputInterface $output) {
        $journalIds = $input->getArgument('journals');
        $journals = $this->getJournals($journalIds);
        foreach($journals as $journal) {
            $this->downloadJournal($journal);
        }
        //     save to path/journalId/filename.zip
    }
    
}

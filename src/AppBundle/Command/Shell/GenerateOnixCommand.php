<?php

namespace AppBundle\Command\Shell;

use AppBundle\Entity\Journal;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Collection;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generate an ONIX-PH feed for all the deposits in the PLN.
 * 
 * @see http://www.editeur.org/127/ONIX-PH/
 */
class GenerateOnixCommand extends ContainerAwareCommand
{
    /**
     * @var TwigEngine
     */
    private $templating;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Registry
     */
    protected $em;

    /**
     * Set the service container, and initialize the command.
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->templating = $container->get('templating');
        $this->logger = $container->get('logger');
        $this->em = $container->get('doctrine')->getManager();
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('pln:onix');
        $this->setDescription('Generate ONIX-PH feed.');
        $this->addArgument('file', InputArgument::IS_ARRAY, 'File(s) to write the feed to.');
    }

    /**
     * Get the journals to process.
     * 
     * @return Collection|Journal[]
     */
    protected function getJournals() {
        $journals = $this->em->getRepository('AppBundle:Journal')->findAll();
        return $journals;
    }
    
    /**
     * Generate a CSV file at $filePath.
     * 
     * @param type $filePath
     */
    protected function generateCsv($filePath) {
        $handle = fopen($filePath, 'w');
        $journals = $this->getJournals();
        fputcsv($handle, array('Generated', date('Y-m-d')));
        fputcsv($handle, array(
            'ISSN', 
            'Title', 
            'Publisher', 
            'Url', 
            'Vol', 
            'No', 
            'Published', 
            'Deposited'
        ));
        foreach($journals as $journal) {
            $deposits = $journal->getSentDeposits();
            if($deposits->count() === 0) {
                continue;
            }
            foreach($deposits as $deposit) {
              if($deposit->getDepositDate() === null) {
                continue;
              }
              fputcsv($handle, array(
                    $journal->getIssn(),
                    $journal->getTitle(),
                    $journal->getPublisherName(),
                    $journal->getUrl(),
                    $deposit->getVolume(),
                    $deposit->getIssue(),
                    $deposit->getPubDate()->format('Y-m-d'),
                    $deposit->getDepositDate()->format('Y-m-d'),
              ));            
           }
        }
    }
    
    /**
     * Generate an XML file at $filePath.
     * 
     * @param string $filePath
     */
    protected function generateXml($filePath) {
        $journals = $this->getJournals();
        $onix = $this->templating->render('AppBundle:Onix:onix.xml.twig', array(
            'journals' => $journals,
        ));
        $fh = fopen($filePath, 'w');
        fwrite($fh, $onix);
        fclose($fh);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
      ini_set('memory_limit', '512M');
        $files = $input->getArgument('file');
        if (!$files || count($files) === 0) {
            $fp = $this->getContainer()->get('filepaths');
            $files[] = $fp->getOnixPath('xml');
            $files[] = $fp->getOnixPath('csv');
        }
        
        foreach($files as $file) {
            $this->logger->info("Writing {$file}");
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            switch($ext) {
                case 'xml':
                    $this->generateXml($file);
                    break;
                case 'csv':
                    $this->generateCsv($file);
                    break;
                default:
                    $this->logger->error("Cannot generate {$ext} ONIX format.");
                    break;
            }
        }
    }
}

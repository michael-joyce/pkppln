<?php


namespace AppBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Run all the commands in order.
 */
class GenerateOnixCommand extends ContainerAwareCommand {

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
    public function setContainer(ContainerInterface $container = null) {
        parent::setContainer($container);
        $this->templating = $container->get('templating');
        $this->logger = $container->get('logger');
        $this->em = $container->get('doctrine')->getManager();
    }

    public function configure() {
        $this->setName('pln:onix');
        $this->setDescription('Generate ONIX-PH feed.');
        $this->addArgument('file', InputArgument::OPTIONAL, 'File to write the feed to.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $file = $input->getArgument('file');
        if( ! $file) {
            $fp = $this->getContainer()->get('filepaths');
            $file = $fp->getOnixPath();
        }
        $journals = $this->em->getRepository('AppBundle:Journal')->findAll();
        $onix = $this->templating->render('AppBundle:Onix:onix.xml.twig', array(
            'journals' => $journals,
        ));
        $fh = fopen($file, 'w');
        fwrite($fh, $onix);
        fclose($fh);
    }

}

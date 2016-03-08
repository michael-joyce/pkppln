<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Command\Shell;

use DOMDocument;
use DOMNamedNodeMap;
use DOMXPath;
use Exception;
use Monolog\Registry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * Description of ExtractDepositCommand
 *
 * @author mjoyce
 */
class ExtractDepositCommand extends ContainerAwareCommand {

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

    /**
     * {@inheritDoc}
     */
    public function configure() {
        $this->setName('pln:extract');
        $this->setDescription('Extract the content of an OJS deposit XML file.');
        $this->addArgument('file', InputArgument::REQUIRED, 'UUID of the deposit to extract.');
        $this->addArgument('path', InputArgument::OPTIONAL, 'Path to extract to. Defaults to current directory.', getcwd());
        $this->addOption('source-names', null, InputOption::VALUE_NONE, 'Use original source file names - use with extreme care.');
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $file = $input->getArgument('file');
        $path = $input->getArgument('path');
        $fs = new Filesystem();
        $useSrcNames = $input->getOption('source-names');
        
        if(substr($path, -1, 1) !== '/') {
            $path .= '/';
        }
        if( ! $fs->exists($path)) {
            $fs->mkdir($path);
        }
        
        $dom = new DOMDocument();
        $valid = $dom->load($file, LIBXML_COMPACT | LIBXML_PARSEHUGE);
        if (!$valid) {
            throw new Exception("{$file} is not a valid XML file.");
        }        
        $xp = new DOMXPath($dom);
        
        foreach ($xp->query('//embed') as $embedded) {
            /** @var DOMNamedNodeMap */
            $attrs = $embedded->attributes;
            if( ! $attrs) {
                $output->writeln("Embedded element has no attributes. Skipping.");
                continue;
            }
            $filename = $attrs->getNamedItem('filename')->nodeValue;
            if( ! $filename) {
                $output->writeln("Embedded element has no file name. Skipping.");
                continue;
            }
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            if($ext) {
                $ext = '.' . $ext;
            }
            
            $tmpPath = '';
            if($useSrcNames) {
                $tmpPath = $path . $filename;
                $ext = '';
            } else {
                $tmpPath = tempnam($path, 'pln-');
            }
            $tmpName = basename($tmpPath);
            $output->writeln("Extracting {$filename} as {$path}{$tmpName}{$ext}.");
            
            $chunkSize = 1024*1024; // 1mb blocks;.
            $fh = fopen($tmpPath, 'wb');
            for($offset = 0; $offset < strlen($embedded->nodeValue); $offset += $chunkSize) {
                $output->write('.');
                $encoded =  substr($embedded->nodeValue, $offset, $chunkSize);
                fwrite($fh, base64_decode($encoded));
            }
            $output->writeln('');
            fclose($fh);
            if($ext && ! $useSrcNames) {
                $fs->rename($tmpPath, $tmpPath . $ext);
            }
        }
    }

}

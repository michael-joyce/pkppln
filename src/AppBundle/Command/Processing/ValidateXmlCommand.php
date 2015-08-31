<?php

namespace AppBundle\Command\Processing;

//require 'vendor/scholarslab/bagit/lib/bagit.php';


use AppBundle\Entity\Deposit;
use AppBundle\Services\DtdValidator;
use BagIt;
use DOMDocument;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ValidateXmlCommand extends AbstractProcessingCmd {

    protected $scanner;
    protected $scannerPath;

    const PKP_PUBLIC_ID = '-//PKP//OJS Articles and Issues XML//EN';

    public function setContainer(ContainerInterface $container = null) {
        parent::setContainer($container);
        $this->scannerPath = $container->getParameter('clamdscan_path');
    }

    /**
     * Configure the command.
     */
    protected function configure() {
        $this->setName('pln:validate-xml');
        $this->setDescription('Validate OJS XML export files.');
        parent::configure();
    }

    private function logErrors(DtdValidator $validator) {
        foreach ($validator->getErrors() as $error) {
            $this->logger->error(implode(':', array($error['file'], $error['line'], $error['message'])));
        }
    }

    /**
     * @param Deposit $deposit
     * @return type
     */
    protected function processDeposit(Deposit $deposit) {
        $extractedPath = $this->getBagPath($deposit);

        $this->logger->info("Validating {$extractedPath} XML files.");
        $bag = new BagIt($extractedPath);
        $valid = true;
        $report = '';

        foreach ($bag->getBagContents() as $filename) {
            if (substr($filename, -4) !== '.xml') {
                continue;
            }
            $basename = basename($filename);
            $dom = new DOMDocument();
            $dom->load($filename);
            /** @var DtdValidator */
            $validator = $this->container->get('dtdvalidator');
            $validator->validate($dom);
            if ($validator->hasErrors()) {
                $valid = false;
                $this->logErrors($validator);
                $report .= "{$basename} validation failed.\n";
            } else {
                $report .= "{$basename} validation succeeded.\n";
            }
        }
        $deposit->addToProcessingLog($report);
        return $valid;
    }

    public function nextState() {
        return "xml-validated";
    }

    public function processingState() {
        return "virus-checked";
    }

    public function failureLogMessage() {
        return "XML Validation failed.";
    }

    public function successLogMessage() {
        return "XML validation succeeded.";
    }

}

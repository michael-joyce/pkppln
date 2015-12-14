<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use AppBundle\Services\DtdValidator;
use BagIt;
use DOMDocument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validate the OJS XML export.
 */
class ValidateXmlCommand extends AbstractProcessingCmd {

    const PKP_PUBLIC_ID = '-//PKP//OJS Articles and Issues XML//EN';

    /**
     * {@inheritDoc}
     */
    protected function configure() {
        $this->setName('pln:validate-xml');
        $this->setDescription('Validate OJS XML export files.');
        parent::configure();
    }

    /**
     * Log errors generated during the validation.
     */
    private function logErrors(DtdValidator $validator) {
        foreach ($validator->getErrors() as $error) {
            $this->logger->error(implode(':', array($error['file'], $error['line'], $error['message'])));
        }
    }

    /**
     * {@inheritDoc}
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
                foreach ($validator->getErrors() as $error) {
                    $report .= "On line {$error['line']}: {$error['message']}\n";
                }
            } else {
                $report .= "{$basename} validation succeeded.\n";
            }
        }
        $deposit->addToProcessingLog($report);
        return $valid;
    }

    /**
     * {@inheritDoc}
     */
    public function nextState() {
        return "xml-validated";
    }

    /**
     * {@inheritDoc}
     */
    public function processingState() {
        return "virus-checked";
    }

    /**
     * {@inheritDoc}
     */
    public function failureLogMessage() {
        return "XML Validation failed.";
    }

    /**
     * {@inheritDoc}
     */
    public function successLogMessage() {
        return "XML validation succeeded.";
    }

}

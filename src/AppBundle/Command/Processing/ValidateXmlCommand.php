<?php

namespace AppBundle\Command\Processing;

require_once('vendor/scholarslab/bagit/lib/bagit.php');

use Exception;
use AppBundle\Entity\Deposit;
use AppBundle\Services\DtdValidator;
use BagIt;
use DOMDocument;

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
            $this->logger->warning(implode(':', array($error['file'], $error['line'], $error['message'])));
        }
    }
    
    /**
     * @return DOMDocument
     * @param string $filename
     */
    private function loadXml(Deposit $deposit, $filename, &$report) {
        $dom = new DOMDocument();
        try {
            $dom->load($filename, LIBXML_COMPACT | LIBXML_PARSEHUGE);
        } catch (Exception $ex) {
            if(strpos($ex->getMessage(), 'Input is not proper UTF-8') === false) {
                $deposit->addErrorLog('XML file ' . basename($filename) . ' is not parseable: ' . $ex->getMessage());
                $report .= $ex->getMessage();
                $report .= "\nCannot validate XML.\n";
                return null;
            }
            $filteredFilename = "{$filename}-filtered.xml";
            $in = fopen($filename, 'rb');
            $out = fopen($filteredFilename, 'wb');
            $blockSize = 64 * 1024; // 64k blocks
            $changes = 0;
            while($buffer = fread($in, $blockSize)) {
                $filtered = iconv('UTF-8', 'UTF-8//IGNORE', $buffer);
                $changes += strlen($buffer) - strlen($filtered);
                fwrite($out, $filtered);
            }
            $report .= basename($filename) . " contains {$changes} invalid UTF-8 characters, which have been removed with " 
                    . ICONV_IMPL . ' version ' . ICONV_VERSION 
                    . " in PHP " . PHP_VERSION . "\n";
            
            $report .= basename($filteredFilename) . " will be validated.\n";
            $dom->load($filteredFilename, LIBXML_COMPACT | LIBXML_PARSEHUGE);
        }
        return $dom;
    }

    /**
     * {@inheritDoc}
     */
    protected function processDeposit(Deposit $deposit) {
        $extractedPath = $this->filePaths->getProcessingBagPath($deposit);

        $this->logger->info("Validating {$extractedPath} XML files.");
        $bag = new BagIt($extractedPath);
        $valid = true;
        $report = '';

        foreach ($bag->getBagContents() as $filename) {
            if (substr($filename, -4) !== '.xml') {
                continue;
            }
            $basename = basename($filename);
            $dom = $this->loadXml($deposit, $filename, $report);
            if($dom === null) {
                $valid = false;
                continue;
            }
            /** @var DtdValidator */
            $validator = $this->container->get('dtdvalidator');
            $validator->validate($dom);
            if ($validator->hasErrors()) {
                $deposit->addErrorLog("{$basename} - XML Validation failed.");
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
        return "bag-validated";
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

    public function errorState() {
        return "xml-error";
    }
}

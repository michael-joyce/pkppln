<?php

namespace AppBundle\Command\Processing;

require_once('vendor/scholarslab/bagit/lib/bagit.php');

use AppBundle\Entity\Deposit;
use BagIt;
use CL\Tissue\Adapter\ClamAv\ClamAvAdapter;
use CL\Tissue\Model\ScanResult;
use DOMDocument;
use DOMNamedNodeMap;
use DOMXPath;
use Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Run a clamAV virus check on a deposit. Checks all the files in a deposit
 * as well as all the embedded files, which are extracted and processed on 
 * their own.
 */
class VirusCheckCommand extends AbstractProcessingCmd {

    /**
     * @var ClamAvAdapter
     */
    protected $scanner;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null) {
        parent::setContainer($container);
        $scannerPath = $container->getParameter('clamdscan_path');
        $this->scanner = new ClamAvAdapter($scannerPath);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure() {
        $this->setName('pln:virus-scan');
        $this->setDescription('Scan deposit packages for viruses.');
        parent::configure();
    }

    /**
     * Log a virus detection.
     *
     * @param ScanResult $result
     */
    private function logInfections($result) {
        foreach ($result->getDetections() as $d) {
            $this->logger->error("VIRUS DETECTED: {$d->getPath()} - {$d->getDescription()}");
        }
    }

    /**
     * Scan a file path for viruses. Logs any that are found.
     * 
     * @param type $path
     * @return true if the scan was clean.
     */
    private function scan($path) {
        $result = $this->scanner->scan([$path]);
        if ($result->hasVirus()) {
            $this->logInfections($result);
            return false;
        }
        return true;
    }

    /**
     * Load the XML from a file and return a DOM. Errors are appended to 
     * the $report string.
     * 
     * @return DOMDocument
     * @param Deposit $deposit
     * @param string $filename
     * @param string $report
     * 
     */
    private function loadXml(Deposit $deposit, $filename, &$report) {
        $dom = new DOMDocument();
        try {
            $dom->load($filename, LIBXML_COMPACT | LIBXML_PARSEHUGE);
        } catch (Exception $ex) {
            if(strpos($ex->getMessage(), 'Input is not proper UTF-8') === false) {
                $deposit->addErrorLog('XML file ' . basename($filename) . ' is not parseable, and cannot be scanned for viruses: ' . $ex->getMessage());
                $report .= $ex->getMessage();
                $report .= "\nCannot scan for viruses.\n";
                return null;
            }
            $filteredFilename = "{$filename}-filtered.xml";
            $report .= basename($filename) . " contains invalid UTF-8 characters and will not be scanned for viruses.\n";
            $report .= basename($filteredFilename) . " will be scanned for viruses instead.\n";
            $dom->load($filteredFilename, LIBXML_COMPACT | LIBXML_PARSEHUGE);
        }
        return $dom;
    }

    /**
     * OJS export XML files may contain embedded media (images, PDFs, other
     * files) which need to be extracted and scanned. Scan results will be
     * appended to $report.
     *
     * @param string $path
     * @param string $report
     * @return boolean
     */
    protected function scanEmbeddedData(Deposit $deposit, $path, &$report) {
        $fs = new Filesystem();

        $dom = $this->loadXml($deposit, $path, $report);
        if($dom === null) {
            return false;
        }
        $xp = new DOMXPath($dom);
        
        $clean = true;
        foreach ($xp->query('//embed') as $embedded) {
            /** @var DOMNamedNodeMap */
            $attrs = $embedded->attributes;
            if (!$attrs) {
                $this->logger->error("No attributes found on embed element.");
            }
            $filename = $attrs->getNamedItem('filename')->nodeValue;
            $this->logger->info("Scanning $filename");
            $tmpPath = tempnam(sys_get_temp_dir(), 'pln-vs-');
            $fh = fopen($tmpPath, 'wb');            
            if(! $fh) {
                throw new Exception("Cannot open {$tmpPath} for write.");
            }
            $chunkSize = 1024 * 1024; // 1MB chunks.
			$length = $xp->evaluate('string-length(./text())', $embedded);			
            $offset = 1; // xpath string offsets start at 1, not zero.
            // Stream the embedded content out of the file. It could be any 
            // size, and may not fit in memory.
            while($offset < $length) {
				$end = $offset+$chunkSize;
				$chunk = $xp->evaluate("substring(./text(), {$offset}, {$chunkSize})", $embedded);				
                fwrite($fh, base64_decode($chunk));
                $offset = $end;
			}
            if (!$this->scan($tmpPath)) {
                $clean = false;
                $report .= "{$filename} - virus detected\n";
            } else {
                $report .= "{$filename} - clean\n";
            }
			$fs->remove($tmpPath);
        }
        return $clean;
    }

    /**
     * {@inheritDoc}
     */
    protected function processDeposit(Deposit $deposit) {
        $extractedPath = $this->filePaths->getProcessingBagPath($deposit);
        $clean = true;
        $report = "";

        $this->logger->info("Checking {$extractedPath} for viruses.");
        $result = $this->scanner->scan([$extractedPath]);
        
        $report .= "Scanned bag files for viruses.\n";
        if ($result->hasVirus()) {
            $this->logInfections($result);
            $report .= "Virus infections found in bag files.\n";
            $clean = false;
        }
        $bag = new BagIt($extractedPath);
        foreach ($bag->getBagContents() as $filename) {
            if (substr($filename, -4) !== '.xml') {
                $this->logger->info("{$filename} is not xml. skipping. ");
                continue;
            }
            $this->logger->info("Scanning {$filename} for embedded viruses.");
            if (!$this->scanEmbeddedData($deposit, $filename, $report)) {
                $clean = false;
            }
        }
        $deposit->addToProcessingLog($report);
        return $clean;
    }

    /**
     * {@inheritDoc}
     */
    public function nextState() {
        return "virus-checked";
    }

    /**
     * {@inheritDoc}
     */
    public function processingState() {
        return "xml-validated";
    }

    /**
     * {@inheritDoc}
     */
    public function failureLogMessage() {
        return "Virus check failed.";
    }

    /**
     * {@inheritDoc}
     */
    public function successLogMessage() {
        return "Virus check passed. No infections found.";
    }

    /**
     * {@inheritDoc}
     */
    public function errorState() {
        return "virus-error";
    }
}

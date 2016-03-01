<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use BagIt;
use CL\Tissue\Adapter\ClamAv\ClamAvAdapter;
use CL\Tissue\Model\ScanResult;
use DOMDocument;
use DOMNamedNodeMap;
use DOMXPath;
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
     * OJS export XML files may contain embedded media (images, PDFs, other
     * files) which need to be extracted and scanned. Scan results will be
     * appended to $report.
     *
     * @param string $path
     * @param string $report
     * @return boolean
     */
    protected function scanEmbeddedData($path, &$report) {
        $dom = new DOMDocument();
        $dom->load($path, LIBXML_COMPACT | LIBXML_PARSEHUGE);
        $xp = new DOMXPath($dom);
        $clean = true;
        foreach ($xp->query('//embed') as $em) {
            /** @var DOMNamedNodeMap */
            $attrs = $em->attributes;
            if (!$attrs) {
                $this->logger->error("No attributes found on embed element.");
            }
            $filename = $attrs->getNamedItem('filename')->nodeValue;
            $this->logger->info("Scanning $filename");
            $path = tempnam(sys_get_temp_dir(), 'pln-vs-');
			$fs = new Filesystem();
			$fs->dumpFile($path, base64_decode($em->nodeValue));
            if (!$this->scan($path)) {
                $clean = false;
                $report .= "{$filename} - virus detected\n";
            } else {
                $report .= "{$filename} - clean\n";
            }
			$fs->remove($path);
        }
        return $clean;
    }

    /**
     * {@inheritDoc}
     */
    protected function processDeposit(Deposit $deposit) {
        $extractedPath = $this->getBagPath($deposit);
        $clean = true;
        $report = "";

        $this->logger->info("Checking {$extractedPath} for viruses.");
        // first scan the whole bag.
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
            if (!$this->scanEmbeddedData($filename, $report)) {
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
        return "bag-validated";
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

}

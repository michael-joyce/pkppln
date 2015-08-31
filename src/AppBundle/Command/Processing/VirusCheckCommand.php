<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use BagIt;
use CL\Tissue\Adapter\ClamAv\ClamAvAdapter;
use DOMDocument;
use DOMNamedNodeMap;
use DOMXPath;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class VirusCheckCommand extends AbstractProcessingCmd {

    protected $scanner;
    protected $scannerPath;
    protected $scanned;
    protected $report;

    public function setContainer(ContainerInterface $container = null) {
        parent::setContainer($container);
        $this->scannerPath = $container->getParameter('clamdscan_path');
        $this->scanner = new ClamAvAdapter($this->scannerPath);
    }

    /**
     * Configure the command.
     */
    protected function configure() {
        $this->setName('pln:virus-scan');
        $this->setDescription('Scan deposit packages for viruses.');
        parent::configure();
    }

    private function logInfections($result) {
        foreach ($result->getDetections() as $d) {
            $this->logger->error("VIRUS DETECTED: {$d->getPath()} - {$d->getDescription()}");
        }
    }

    /**
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

    protected function scanEmbeddedData($path, &$report) {
        $dom = new DOMDocument();
        $dom->load($path);
        $xp = new DOMXPath($dom);
        $clean = true;
        foreach ($xp->query('//embed') as $em) {
            /** @var DOMNamedNodeMap */
            $attrs = $em->attributes;
            if (!$attrs) {
                $this->logger->error("No attributes found on embed element.");
            }
            $filename = $attrs->getNamedItem('filename')->nodeValue;
            $fs = new Filesystem();
            $tmpdir = sys_get_temp_dir();
            $path = "{$tmpdir}/{$filename}";
            $this->logger->info("Scanning {$path}");
            $fs->dumpFile($path, base64_decode($em->nodeValue));
            if (!$this->scan($path)) {
                $clean = false;
                $report .= "{$filename} - virus detected\n";
            } else {
                $report .= "{$filename} - clean\n";
            }
        }
        return $clean;
    }

    /**
     * Process one deposit. Fetch the data and write it to the file system.
     * Updates the deposit status.
     *
     * @param Deposit $deposit
     * @return type
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
                $this->logger->notice("{$filename} is not xml. skipping. ");
                continue;
            }
            $this->logger->notice("Scanning {$filename} for embedded viruses.");
            if (!$this->scanEmbeddedData($filename, $report)) {
                $clean = false;
            }
        }
        $deposit->addToProcessingLog($report);
        return $clean;
    }

    public function nextState() {
        return "virus-checked";
    }

    public function processingState() {
        return "bag-validated";
    }

    public function failureLogMessage() {
        return "Virus check failed.";
    }

    public function successLogMessage() {
        return "Virus check passed. No infections found.";
    }

}

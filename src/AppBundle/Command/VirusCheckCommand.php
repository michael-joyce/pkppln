<?php

namespace AppBundle\Command;

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

    protected function scanEmbeddedData($path) {
        $dom = new DOMDocument();
        $dom->load($path);
        $xp = new DOMXPath($dom);
        $clean = true;
        foreach ($xp->query('//embed') as $em) {
            /** @var DOMNamedNodeMap */
            $attrs = $em->attributes;
            if (!$attrs) {
                return; // should this be an error?
            }
            $filename = $attrs->getNamedItem('filename')->nodeValue;
            $fs = new Filesystem();
            $tmpdir = sys_get_temp_dir();
            $path = "{$tmpdir}/{$filename}";
            $this->logger->info("Scanning {$path}");
            $fs->dumpFile($path, base64_decode($em->nodeValue));
            if (!$this->scan($path)) {
                $clean = false;
                $this->report[basename($path)] = 'virus detected';
            } else {
                $this->report[basename($path)] = 'clean';
            }
        }
        return $clean;
    }

    public function generateReport($startTimestamp, $endTimestamp) {
        $report = "# Virus scan started at {$startTimestamp} by ClamAV.";
        foreach($this->report as $filename => $status) {
            $report .= "{$filename}\t{$status}\n";
        }
        $report .= "# Virus scan finished at {$endTimestamp}";
        return $report;
    }

    /**
     * Process one deposit. Fetch the data and write it to the file system.
     * Updates the deposit status.
     *
     * @param Deposit $deposit
     * @return type
     */
    protected function processDeposit(Deposit $deposit) {
        $journal = $deposit->getJournal();
        $extractedPath = $this->getProcessingDir($journal) . '/' . $deposit->getFileUuid();
        $clean = true;
        $this->report = array();
        $startTimestamp = date('c');

        $this->logger->info("Checking {$extractedPath} for viruses.");
        // first scan the whole bag.
        $result = $this->scanner->scan([$extractedPath]);

        if ($result->hasVirus()) {
            $this->logInfections($result);
            $clean = false;
        }
        $bag = new BagIt($extractedPath . '/bag');
        foreach ($bag->getBagContents() as $filename) {
            if (substr($filename, -4) !== '.xml') {
                continue;
            }
            if (!$this->scanEmbeddedData($filename)) {
                $clean = false;
            }
        }
        $finishTimestamp = date('c');
        $report = $this->generateReport($startTimestamp, $endTimestamp);
        $bag->createFile($extractedPath, $dest);
        return $clean;
    }

    public function nextState() {
        return "virus-checked";
    }

    public function processingState() {
        return "bag-validated";
    }

}

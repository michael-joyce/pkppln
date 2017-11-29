<?php

namespace AppBundle\Command\Processing;

use AppBundle\Utility\AbstractCommandTestCase;
use Exception;
use ZipArchive;

class VirusCheckCommandTest extends AbstractCommandTestCase {

    public function setUp() {
        parent::setUp();
        $this->markTestSkipped('This test requires fixing.');
    }

    public function getCommand() {
        return new ScanVirusesCommand();
    }

    public function getCommandName() {
        return 'pln:scan-viruses';
    }

    public function dataFiles() {
        return array(
            'bag-harvested.zip' => 'received/C0A65967-32BD-4EE8-96DE-C469743E563A/D38E7ECB-7D7E-408D-94B0-B00D434FDBD2.zip',
            '.processing' => 'processing',
            '.received' => 'received',
            '.staged' => 'staged',
        );
    }

    public function testScan() {
        // extract the bag.
        $deposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
        $deposit->setState('xml-validated');
        $this->em->flush();
        $this->em->clear();

        $harvestedPath = 'test/data/received/C0A65967-32BD-4EE8-96DE-C469743E563A/D38E7ECB-7D7E-408D-94B0-B00D434FDBD2.zip';
        $extractedPath = 'test/data/processing/C0A65967-32BD-4EE8-96DE-C469743E563A/D38E7ECB-7D7E-408D-94B0-B00D434FDBD2';
        $zipFile = new ZipArchive();
        $zipFile->open($harvestedPath);
        if ($zipFile->extractTo(dirname($extractedPath)) === false) {
            throw new Exception("Cannot extract to {$extractedPath} " . $zipFile->getStatusString());
        }
        $this->commandTester->execute(array(
            'command' => $this->getCommandName(),
        ));
        $this->em->clear();
        $processedDeposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
        $this->assertEquals('virus-checked', $processedDeposit->getState());
    }

}

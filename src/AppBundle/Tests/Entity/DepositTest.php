<?php

namespace AppBundle\Tests\Entity;

use AppBundle\Entity\Deposit;
use AppBundle\Utility\AbstractTestCase;

class DepositTest extends AbstractTestCase {

    /**
     * @var Deposit
     */
    protected $deposit;

    protected function setUp() {
        parent::setUp();
        $this->deposit = $this->references->getReference('deposit');
    }

    /**
     * All uuids should be uppercase.
     */
    public function testUuids() {
        $this->assertEquals('96C0E180-17E6-4B12-A1D2-C9FF3131820A', $this->deposit->getFileUuid());
        $this->assertEquals('D38E7ECB-7D7E-408D-94B0-B00D434FDBD2', $this->deposit->getDepositUuid());
    }

    public function testGetFileNameZip() {
        $this->assertEquals('96C0E180-17E6-4B12-A1D2-C9FF3131820A.zip', $this->deposit->getFileName());
    }
    
    public function testGetFileNameGzip() {
        $this->deposit->setFileType('application/x-gzip');
        $this->assertEquals('96C0E180-17E6-4B12-A1D2-C9FF3131820A.tgz', $this->deposit->getFileName());
    }

    public function testAddToProcessingLog() {
        $this->deposit->addToProcessingLog('TESTPASSED');
        $this->assertStringEndsWith("TESTPASSED\n\n", $this->deposit->getProcessingLog());
    }

}

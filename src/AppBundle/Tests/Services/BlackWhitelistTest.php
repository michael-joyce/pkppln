<?php

namespace AppBundle\Tests\Services;

use AppBundle\Services\BlackWhitelist;
use AppBundle\Utility\AbstractTestCase;

class BlackWhitelistTest extends AbstractTestCase {
    
    /**
     * @var BlackWhitelist
     */
    private $listing;
    
    protected function setUp() {
        parent::setUp();
        $this->listing = $this->getContainer()->get('blackwhitelist');
    }
    
    public function testIsWhitelistedTrue() {
        $this->assertTrue($this->listing->isWhitelisted('6646afaa-beba-40c8-a286-c64a3e90d0f6'));
        $this->assertTrue($this->listing->isWhitelisted('6646AFAA-BEBA-40C8-A286-C64A3E90D0F6'));
    }
    
    public function testIsWhitelistedFalse() {
        $this->assertFalse($this->listing->isWhitelisted('E10E1C62-80AB-4D62-8336-C13C7BE73ED8'));
    }
    
    public function testIsBlacklistedTrue() {
        $this->assertTrue($this->listing->isBlacklisted('e10e1c62-80ab-4d62-8336-c13c7be73ed8'));
        $this->assertTrue($this->listing->isBlacklisted('E10E1C62-80AB-4D62-8336-C13C7BE73ED8'));
    }
    
    public function testIsBlacklistedFalse() {
        $this->assertFalse($this->listing->isBlacklisted('6646AFAA-BEBA-40C8-A286-C64A3E90D0F6'));
    }
    
}

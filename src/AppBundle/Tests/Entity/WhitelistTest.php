<?php

namespace AppBundle\Tests\Entity;

use AppBundle\Entity\Blacklist;
use AppBundle\Utility\AbstractTestCase;

class WhitelistTest extends AbstractTestCase {

    /**
     * @var Blacklist
     */
    protected $whitelist;

    protected function setUp() {
        parent::setUp();
        $this->whitelist = $this->references->getReference('wl');
    }

    /**
     * Uuid should be uppercase.
     */
    public function testUuid() {
        $this->assertEquals('6646AFAA-BEBA-40C8-A286-C64A3E90D0F6', $this->whitelist->getUuid());
    }

}
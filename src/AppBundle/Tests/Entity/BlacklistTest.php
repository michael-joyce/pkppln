<?php

namespace AppBundle\Tests\Entity;

use AppBundle\Entity\Blacklist;
use AppBundle\Utility\AbstractTestCase;

class BlacklistTest extends AbstractTestCase {

    /**
     * @var Blacklist
     */
    protected $blacklist;

    protected function setUp() {
        parent::setUp();
        $this->blacklist = $this->references->getReference('bl');
    }

    /**
     * Uuid should be uppercase.
     */
    public function testUuid() {
        $this->assertEquals('E10E1C62-80AB-4D62-8336-C13C7BE73ED8', $this->blacklist->getUuid());
    }

}
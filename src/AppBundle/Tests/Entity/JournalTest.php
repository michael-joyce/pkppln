<?php

namespace AppBundle\Tests\Entity;

use AppBundle\Entity\Journal;
use AppBundle\Utility\AbstractTestCase;

class JournalTest extends AbstractTestCase {
    
    /**
     * @var Journal
     */
    protected $journal;

    protected function setUp() {
        parent::setUp();
        $this->journal = $this->references->getReference('journal');
    }
    
    public function testUuid() {
        $this->assertEquals('C0A65967-32BD-4EE8-96DE-C469743E563A', $this->journal->getUuid());
    }
    
    

}
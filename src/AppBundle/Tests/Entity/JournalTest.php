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


}
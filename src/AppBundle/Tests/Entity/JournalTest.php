<?php

namespace AppBundle\Tests\Entity;

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
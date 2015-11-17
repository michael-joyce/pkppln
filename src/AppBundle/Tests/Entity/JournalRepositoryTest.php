<?php

namespace AppBundle\Tests\Entity;

use AppBundle\Utility\AbstractTestCase;

class JournalRepositoryTest extends AbstractTestCase {
    protected function setUp() {
        parent::setUp();
    }

    public function testSearch() {
        $repo = $this->em->getRepository('AppBundle:Journal');
        $this->assertEquals(1, count($repo->search('Testing')));
    }

    public function testSearchNoResults() {
        $repo = $this->em->getRepository('AppBundle:Journal');
        $this->assertEquals(0, count($repo->search('Frobinicating')));
    }

    public function testFindByStatus() {
        $repo = $this->em->getRepository('AppBundle:Journal');
        $this->assertEquals(1, count($repo->findByStatus('healthy')));
    }

    public function testFindByStatusNoResults() {
        $repo = $this->em->getRepository('AppBundle:Journal');
        $this->assertEquals(0, count($repo->findByStatus('triggered')));
    }
}
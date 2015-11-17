<?php

namespace AppBundle\Tests\Entity;

use AppBundle\Utility\AbstractTestCase;

class DepositRepositoryTest extends AbstractTestCase {

    protected function setUp() {
        parent::setUp();
    }

    public function testFindByState() {
        $repo = $this->em->getRepository('AppBundle:Deposit');
        $this->assertEquals(1, count($repo->findByState('deposited')));
    }

    public function testFindByStateNone() {
        $repo = $this->em->getRepository('AppBundle:Deposit');
        $this->assertEquals(0, count($repo->findByState('harvested')));
    }
}
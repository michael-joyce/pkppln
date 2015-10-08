<?php

namespace AppBundle\Tests\Entity;

use AppBundle\Entity\TermOfUseRepository;
use AppBundle\Utility\AbstractTestCase;
use Doctrine\Common\Persistence\ObjectManager;

class TermOfUseRepositoryTest extends AbstractTestCase {
    
    /**
     * @var TermOfUseRepository
     */
    protected $repo;
    
    public function setUp() {
        parent::setUp();
        $this->repo = $this->em->getRepository('AppBundle:TermOfUse');
    }
    
    protected function assertTermsSorted() {
        $terms = $this->repo->getTerms();
        for($i = 0; $i < count($terms); $i++) {
            $this->assertEquals($i, $terms[$i]->getWeight());
        }
    }
    
    public function testDefaultTermsSorted() {
        $this->assertTermsSorted();
    }
    
    public function testReorderTerms() {
        $terms = $this->repo->getTerms();
        $terms[0]->setWeight(2);
        $this->em->flush();
        $terms[2]->setWeight(0);
        $this->em->flush();
        $this->em->clear();
        $this->assertTermsSorted();
    }
}

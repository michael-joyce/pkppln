<?php

namespace AppBundle\Tests\EventListener;

use AppBundle\Entity\TermOfUse;
use AppBundle\Entity\TermOfUseRepository;
use AppBundle\Utility\AbstractTestCase;
use Doctrine\Common\Persistence\ObjectRepository;

class TermsOfUseListenerTest extends AbstractTestCase {

    /**
     * @var TermOfUseRepository
     */
    private $termRepo;

    /**
     * @var ObjectRepository
     */
    private $historyRepo;

    public function setUp() {
        parent::setUp();
        $this->termRepo = $this->em->getRepository('AppBundle:TermOfUse');
        $this->historyRepo = $this->em->getRepository('AppBundle:TermOfUseHistory');
    }

    public function testCreateTerm() {
        $term = new TermOfUse();
        $term->setContent('created term.');
        $term->setKeyCode('test.x');
        $term->setLangCode('en-US');
        $term->setWeight(8);
        $this->em->persist($term);
        $this->em->flush();
        $this->em->clear();

        $history = $this->historyRepo->getTermHistory($term->getId());
        $this->assertEquals(1, count($history));

        $item = $history[0];
        $this->assertEquals('create', $item->getAction());
        $this->assertEquals(array(
            'id' => array(null, 4),
            'weight' => array(null, 8),
            'keyCode' => array(null, 'test.x'),
            'langCode' => array(null, 'en-US'),
            'content' => array(null, 'created term.')
        ), $item->getChangeSet());
    }

    public function testDeleteTerm() {
        $term = $this->termRepo->find(1);
        $this->em->remove($term);
        $this->em->flush();
        $this->em->clear();
        $history = $this->historyRepo->getTermHistory(1);
        
        $this->assertEquals(2, count($history));

        $item = $history[1];
        $this->assertEquals('delete', $item->getAction());
        $this->assertEquals(array(
            'id' => array(1, null),
            'weight' => array(0, null),
            'keyCode' => array('test.a', null),
            'langCode' => array('en-US', null),
            'content' => array('first term.', null)
        ), $item->getChangeSet());
    }

    public function testUpdateTerm() {
        $term = $this->termRepo->find(1);
        $term->setContent("updated.");
        $this->em->flush();
        $this->em->clear();
        $history = $this->historyRepo->getTermHistory(1);

        $this->assertEquals(2, count($history));

        $item = $history[1];
        $this->assertEquals('update', $item->getAction());
    }
}
<?php

namespace AppBundle\Utility;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Liip\FunctionalTestBundle\Test\WebTestCase as BaseTestCase;

/**
 * Thin wrapper around Liip\FunctionalTestBundle\Test\WebTestCase to preload
 * fixtures into the database.
 */
abstract class AbstractTestCase extends BaseTestCase {

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * As the fixtures load data, they save references. Use $this->references
     * to get them.
     * 
     * @var ReferenceRepository
     */
    protected $references;

	public function fixtures() {
		return array();
	}
	
    /**
     * {@inheritDocs}
     */
    protected function setUp() {
        $fixtures = $this->fixtures();
		if(count($fixtures) > 0) {
			$this->references = $this->loadFixtures($fixtures)->getReferenceRepository();
		}
		$this->em = $this->getContainer()->get('doctrine')->getManager();
    }
}
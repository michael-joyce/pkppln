<?php

namespace AppBundle\Utility;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Common\Persistence\ObjectManager;
use Liip\FunctionalTestBundle\Test\WebTestCase as BaseTestCase;

/**
 * Thin wrapper around Liip\FunctionalTestBundle\Test\WebTestCase to preload
 * fixtures into the database.
 */
class AbstractTestCase extends BaseTestCase {

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

    /**
     * {@inheritDocs}
     */
    protected function setUp() {
        $fixtures = array(
            'AppBundle\DataFixtures\ORM\test\LoadBlacklist',
            'AppBundle\DataFixtures\ORM\test\LoadDeposits',
            'AppBundle\DataFixtures\ORM\test\LoadJournals',
            'AppBundle\DataFixtures\ORM\test\LoadTermsOfUse',
            'AppBundle\DataFixtures\ORM\test\LoadWhitelist',
            'AppUserBundle\DataFixtures\ORM\test\LoadUsers',
        );
        $this->references = $this->loadFixtures($fixtures)->getReferenceRepository();
        $this->em = $this->getContainer()->get('doctrine')->getManager();
    }
}
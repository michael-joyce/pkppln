<?php

namespace AppBundle\Utility;

use Closure;
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
	private static $kernelModifier = null;

	public function setKernelModifier(Closure $kernelModifier) {
		self::$kernelModifier = $kernelModifier;
		$this->ensureKernelShutdown();
	}

	protected static function createClient(array $options = [], array $server = []) {
		static::bootKernel($options);
		if (self::$kernelModifier !== null) {
			self::$kernelModifier->__invoke();
			self::$kernelModifier = null;
		}
		$client = static::$kernel->getContainer()->get('test.client');
		$client->setServerParameters($server);
		return $client;
	}

	public function fixtures() {
		return array();
	}

	/**
	 * {@inheritDocs}
	 */
	protected function setUp() {
		parent::setUp();
		$fixtures = $this->fixtures();
		if (count($fixtures) > 0) {
			$this->references = $this->loadFixtures($fixtures)->getReferenceRepository();
		}
		$this->em = $this->getContainer()->get('doctrine')->getManager();
	}

	public function tearDown() {
		parent::tearDown();
		$this->em->clear();
		$this->em->close();
	}
}

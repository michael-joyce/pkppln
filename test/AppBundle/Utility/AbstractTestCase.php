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
	 * Location of testing source data. It may be copied to the right place
	 * in a test set up function.
	 */
	const SRCDIR = "test/data-src";

	/**
	 * Expected location of testing data.
	 */
	const DSTDIR = "test/data";

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
	
	// http://stackoverflow.com/questions/29082802/symfony2-phpunit-functional-test-custom-user-authentication-fails-after-redirect
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

	/**
	 * Returns a list of data fixture classes for use in one test class. They 
	 * will be loaded into the database before each test function in the class.
	 * 
	 * @return array()
	 */
	public function fixtures() {
		return array();
	}

	/**
	 * Return a list of testing data files, including where they should be 
	 * copied to. 
	 * 
	 * @see DefaultControllerAnonTest for example usage.
	 * 
	 * @return array()
	 */
	public function dataFiles() {
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
		
		foreach($this->dataFiles() as $src => $dst) {
			$dir = self::DSTDIR . '/' . dirname($dst);
			if(! file_exists($dir)) {
				mkdir($dir, 0755, true);
			}
			copy(self::SRCDIR . '/' . $src, self::DSTDIR . '/' . $dst);
		}

	}

	public function tearDown() {
		parent::tearDown();
		$this->em->clear();
		$this->em->close();
		
		foreach($this->dataFiles() as $src => $dst) {
			if(file_exists(self::DSTDIR . '/' . $dst)) {
				unlink(self::DSTDIR . '/' . $dst);			
			}
		}
	}
}

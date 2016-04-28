<?php

namespace AppBundle\Controller;

use AppBundle\Utility\AbstractTestCase;
use Symfony\Component\HttpFoundation\Response;

class SwordControllerTest extends AbstractTestCase {

	protected $client;

	public function setUp() {
		parent::setUp();
		$this->client = static::createClient();
	}
	
	public function testServiceDocumentNoOBH() {
		$this->client->request('GET', '/api/sword/2.0/sd-iri');
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
	}

}

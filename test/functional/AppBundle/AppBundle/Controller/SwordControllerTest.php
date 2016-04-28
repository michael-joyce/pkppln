<?php

namespace AppBundle\Controller;

use AppBundle\Utility\AbstractTestCase;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\HttpFoundation\Response;

class SwordControllerTest extends AbstractTestCase {

	/**
	 * @var Client
	 */
	protected $client;

	public function setUp() {
		parent::setUp();
		$this->client = static::createClient();
	}
	
    public function fixtures() {
        return array(
            'AppBundle\DataFixtures\ORM\test\LoadJournals',
        );
    }
    
	public function testServiceDocument() {
		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
			'HTTP_On-Behalf-Of' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F',
			'HTTP_Journal-Url' => 'http://example.com'
		));
		$this->assertEquals(200, $this->client->getResponse()->getStatusCode());
	}
	
	public function testServiceDocumentNoOBH() {
		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
			'HTTP_Journal-Url' => 'http://example.com'
		));
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
	}
	
	public function testServiceDocumentNoJournalUrl() {
		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
			'HTTP_On-Behalf-Of' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F',
		));
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
	}

	public function testServiceDocumentBadHeaders() {
		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
			'HTTP_On-Behalf-Of' => '',
			'HTTP_Journal-Url' => ''
		));
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
	}
	
	
	
}

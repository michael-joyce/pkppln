<?php

namespace AppBundle\Controller;

use AppBundle\Utility\AbstractTestCase;
use Symfony\Component\HttpFoundation\Response;

class JournalControllerTest extends AbstractTestCase {

    protected $client;

    public function setUp() {
        parent::setUp();
        $this->client = static::createClient(array(), array(
                'PHP_AUTH_USER' => 'admin@example.com',
                'PHP_AUTH_PW' => 'supersecret',
        ));
    }

    public function fixtures() {
        return array(
            'AppBundle\DataFixtures\ORM\test\LoadJournals',
            'AppBundle\DataFixtures\ORM\test\LoadDeposits',
            'AppBundle\DataFixtures\ORM\Test\LoadAuContainers',
            'AppUserBundle\DataFixtures\ORM\test\LoadUsers',
        );
    }
    
    public function testIndex() {
        $this->client->request('GET', '/journal/');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $crawler = $this->client->getCrawler();
        
        $linkCrawler = $crawler->selectLink('I J Testing');
        $this->assertCount(1, $linkCrawler);
        $this->assertEquals('http://localhost/journal/1', $linkCrawler->link()->getUri());
    }
    
    public function testIndexStatus() {
        $this->markTestSkipped('Need more journals for this');
    }
    
    public function testShow() {
        $this->client->request('GET', '/journal/1');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertContains('C0A65967-32BD-4EE8-96DE-C469743E563A', $response->getContent());
    }
    
    public function testPing() {
        $this->markTestSkipped('unimplemented.');
    }
    
    public function testSearchPage() {
        $this->markTestSkipped('unimplemented.');
    }
    
    public function testSearchTitle() {
        $this->markTestSkipped('Need more journals for this');
    }
    
    public function testSearchUuid() {
        $this->markTestSkipped('Need more journals for this');
    }
    
    public function testSearchIssn() {
        $this->markTestSkipped('Need more journals for this');
    }
    
    public function testSearchUrl() {
        $this->markTestSkipped('Need more journals for this');
    }
    
    public function testSearchEmail() {
        $this->markTestSkipped('Need more journals for this');
    }
    
    public function testSearchPublisherName() {
        $this->markTestSkipped('Need more journals for this');
    }
}

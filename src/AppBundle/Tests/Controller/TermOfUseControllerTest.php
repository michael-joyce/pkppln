<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Utility\AbstractTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class TermOfUseControllerTest extends AbstractTestCase {
    
    private $client = null;
    
    protected function setUp() {
        parent::setUp();
        $this->client = static::createClient(array(), array(
            'PHP_AUTH_USER' => 'admin@example.com',
            'PHP_AUTH_PW' => 'supersecret',
        ));
    }
    
    // make sure the terms are sorted by weight.
    public function testIndex() {
        $crawler = $this->client->request('GET', '/termofuse/');
        
        $this->assertEquals(1, $crawler->filter('html:contains("test.a")')->count());
        $this->assertEquals(1, $crawler->filter('html:contains("test.b")')->count());
        $this->assertEquals(1, $crawler->filter('html:contains("test.c")')->count());
        
        $text = $crawler->text();
        $this->assertGreaterThan(strpos($text, 'test.a'), strpos($text, 'test.b'));
        $this->assertGreaterThan(strpos($text, 'test.b'), strpos($text, 'test.c'));
    }
    
    public function testSort() {
        $crawler = $this->client->request('GET', '/termofuse/sort');
        $form = $crawler->selectButton('Save')->form();
        $form['order'] = '3,2,1';
        $sortedCrawler = $this->client->submit($form);
        
        $text = $sortedCrawler->text();
        $this->assertGreaterThan(strpos($text, 'test.c'), strpos($text, 'test.b'));
        $this->assertGreaterThan(strpos($text, 'test.b'), strpos($text, 'test.a'));
    }
    
}
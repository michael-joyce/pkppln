<?php

namespace AppBundle\Controller\SwordController;

use AppBundle\Utility\AbstractTestCase;
use AppBundle\Utility\Namespaces;
use SimpleXMLElement;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\HttpFoundation\Response;

class StatementTest extends AbstractTestCase {

	/**
	 * @var Client
	 */
	protected $client;

	/**
	 * @var Namespaces
	 */
	protected $ns;

	public function setUp() {
		parent::setUp();
		$this->client = static::createClient();
		$this->ns = new Namespaces();
	}
	
    public function fixtures() {
        return array(
            'AppBundle\DataFixtures\ORM\test\LoadJournals',
			'AppBundle\DataFixtures\ORM\test\LoadDeposits',
			'AppBundle\DataFixtures\ORM\test\LoadTermsOfUse',
			'AppBundle\DataFixtures\ORM\test\LoadWhitelist',
        );
    }
    
	/**
	 * @return SimpleXMLElement
	 * @param Client $client
	 */
	protected function getXml(Client $client) {
		$xml = new SimpleXMLElement($this->client->getResponse()->getContent());
		$this->ns->registerNamespaces($xml);
		return $xml;
	}
	
    /**
     * Get a single XML value as a string.
     * 
     * @param SimpleXMLElement $xml
     * @param type $xpath
     * @return string
     * @throws Exception
     */
    public function getXmlValue(SimpleXMLElement $xml, $xpath) {
        $data = $xml->xpath($xpath);
        if (count($data) === 1) {
            return trim((string) $data[0]);
        }
        if (count($data) === 0) {
            return null;
        }
        throw new Exception("Too many elements for '{$xpath}'");
    }

	// journal not whitelisted
	public function testStatementNotWhitelisted() {
		$this->client->request('GET', '/api/sword/2.0/cont-iri/96B68B76-DC69-4E62-A9A3-AE76B702EB2B/BFDC45E7-58A8-4C46-B194-E20E040F0BD7/state');
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
		$this->assertContains('Not authorized to request statements.', $this->client->getResponse()->getContent());
	}
	
	// requested journal uuid does not match deposit uuid.
	public function testStatementMismatch() {
		$this->client->request('GET', '/api/sword/2.0/cont-iri/A556CBF2-B674-444F-87B7-23DEE36F013D/578205CB-0947-4CD3-A384-CDF186F5E86B/state');
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
		$this->assertContains('Not authorized to request statements.', $this->client->getResponse()->getContent());
	}
	
	// journal uuid unknown.
	public function testStatementJournalNonFound() {
		$this->client->request('GET', '/api/sword/2.0/cont-iri/96B68B76-DC69-4E62-A9A3-AE76B702EB2B/BFDC45E7-58A8-4C46-B194-E20E040F0BD7/state');
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
		$this->assertContains('Not authorized to request statements.', $this->client->getResponse()->getContent());
	}
	
	// deposit uuid unknown.
	public function testStatementDepositNonFound() {
		$this->client->request('GET', '/api/sword/2.0/cont-iri/c0a65967-32bd-4ee8-96de-c469743e563a/BFDC45E7-58A8-4C46-B194-E20E040F0BD7/state');
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
		$this->assertContains('Deposit UUID not found.', $this->client->getResponse()->getContent());		
	}
	
	public function testStatement(){
		$this->client->request('GET', '/api/sword/2.0/cont-iri/c0a65967-32bd-4ee8-96de-c469743e563a/578205CB-0947-4CD3-A384-CDF186F5E86B/state');
		$this->assertEquals(200, $this->client->getResponse()->getStatusCode());
		$xml = $this->getXml($this->client);
		$this->assertEquals('http://journal.example2.com/path/to/deposit', $this->getXmlValue($xml, '//atom:content/text()'));
	}
}

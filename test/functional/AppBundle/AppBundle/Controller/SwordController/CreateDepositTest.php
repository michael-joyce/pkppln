<?php

namespace AppBundle\Controller\SwordController;

use AppBundle\Utility\AbstractTestCase;
use AppBundle\Utility\Namespaces;
use GuzzleHttp\Stream\Stream;
use SimpleXMLElement;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\HttpFoundation\Response;

class CreateDepositTest extends AbstractTestCase {

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

	public function testCreateDepositWhitelisted() {
		$depositCount = count($this->em->getRepository('AppBundle:Deposit')->findAll());
		$this->client->request(
            'POST', 
            '/api/sword/2.0/col-iri/c0a65967-32bd-4ee8-96de-c469743e563a',
            array(),
            array(),
            array(),
            $this->getDepositXmlNotWhitelisted()
		);
		$response = $this->client->getResponse();
		$this->assertEquals(201, $response->getStatusCode());
		$this->assertEquals('http://localhost/api/sword/2.0/cont-iri/C0A65967-32BD-4EE8-96DE-C469743E563A/5F5C84B1-80BF-4071-8D3F-057AA3184FC9/state', $response->headers->get('Location'));
		$this->assertEquals($depositCount + 1, count($this->em->getRepository('AppBundle:Deposit')->findAll()));
		$xml = $this->getXml($this->client);
		$this->assertEquals('depositedByJournal', $this->getXmlValue($xml, '//atom:category[@label="Processing State"]/@term'));
	}
	
	public function testCreateDepositNotWhitelisted() {
		$depositCount = count($this->em->getRepository('AppBundle:Deposit')->findAll());
		$this->client->request(
            'POST', 
            '/api/sword/2.0/col-iri/5F5C84B1-80BF-4071-8D3F-057AA3184FC9',
            array(),
            array(),
            array(),
            $this->getDepositXmlNotWhitelisted()
		);
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
		$this->assertContains('Not authorized to create deposits.', $this->client->getResponse()->getContent());
		$this->assertEquals($depositCount, count($this->em->getRepository('AppBundle:Deposit')->findAll()));
	}
	
	private function getDepositXmlNotWhitelisted() {
		$str = <<<'ENDXML'
<entry 
    xmlns="http://www.w3.org/2005/Atom" 
    xmlns:dcterms="http://purl.org/dc/terms/"
    xmlns:pkp="http://pkp.sfu.ca/SWORD">
    <email>foo@example.com</email>
    <title>Test Data Journal of Testing</title>
    <pkp:journal_url>http://tdjt.example.com</pkp:journal_url>
    <pkp:publisherName>Publisher of Stuff</pkp:publisherName>
    <pkp:publisherUrl>http://publisher.example.com</pkp:publisherUrl>
    <pkp:issn>1234-1234</pkp:issn>
    <id>urn:uuid:5F5C84B1-80BF-4071-8D3F-057AA3184FC9</id>
    <updated>2016-04-22T12:35:48Z</updated>
    <pkp:content size="123" volume="2" issue="4" pubdate="2016-04-22" 
		checksumType="SHA-1"
        checksumValue="d46c034ef54c36237b89d456968965432830a603">http://example.com/deposit/foo.zip</pkp:content>
    <pkp:license>
        <pkp:publishingMode>Open</pkp:publishingMode>
        <pkp:openAccessPolicy>OA GOOD</pkp:openAccessPolicy>
        <pkp:licenseUrl>http://example.com/license</pkp:licenseUrl>
        <pkp:copyrightNotice>Copyright ME</pkp:copyrightNotice>
        <pkp:copyrightBasis>ME</pkp:copyrightBasis>
        <pkp:copyrightHolder>MYSELF</pkp:copyrightHolder>
    </pkp:license>
</entry>
ENDXML;
		return $str;
	}
}


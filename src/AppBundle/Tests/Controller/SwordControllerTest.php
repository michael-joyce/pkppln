<?php

namespace AppBundle\Tests;

use AppBundle\Entity\Deposit;
use AppBundle\Utility\AbstractTestCase;
use AppBundle\Utility\Namespaces;
use SimpleXMLElement;

class SwordControllerTest extends AbstractTestCase
{

    /**
     * @var Namespaces
     */
    private $namespaces;

    public function __construct()
    {
        parent::__construct();
        $this->namespaces = new Namespaces();
    }

    public function setUp()
    {
        parent::setUp();
    }

    private function getXml($string)
    {
        $xml = new SimpleXMLElement($string);
        $this->namespaces->registerNamespaces($xml);
        return $xml;
    }

    private function assertXpath(SimpleXMLElement $xml, $expected, $xpath, $method = 'assertEquals')
    {
        $value = (string)($xml->xpath($xpath)[0]);
        $normal = preg_replace('/^\s*|\s*$/', '', $value);
        $this->$method($expected, $normal);
    }

    public function testServiceDocumentMissingOnBehalfOf()
    {
        $journalUrl = 'http://test.example.com/path/to/journal';

        $client = static::createClient();
        $crawler = $client->request(
            'GET',
            '/api/sword/2.0/sd-iri',
            array(),
            array(),
            array(
                'HTTP_Journal-Url' => $journalUrl
            )
        );
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testServiceDocumentJournalUrl()
    {
        $uuid = '31ef1e4b-023f-4be3-9024-4c232fc2f271';

        $client = static::createClient();
        $crawler = $client->request(
            'GET',
            '/api/sword/2.0/sd-iri',
            array(),
            array(),
            array(
                'HTTP_On-Behalf-Of' => $uuid,
            )
        );
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testServiceDocument()
    {
        $uuid = '31ef1e4b-023f-4be3-9024-4c232fc2f271';
        $journalUrl = 'http://test.example.com/path/to/journal';

        $client = static::createClient();
        $crawler = $client->request(
            'GET',
            '/api/sword/2.0/sd-iri',
            array(),
            array(),
            array(
                'HTTP_On-Behalf-Of' => $uuid,
                'HTTP_Journal-Url' => $journalUrl
            )
        );
        //$this->assertEquals('', $client->getResponse()->getContent());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $xml = $this->getXml($client->getResponse()->getContent());
        $this->assertXpath($xml, 2, '/app:service/sword:version');
        $this->assertXpath($xml, '1000', '/app:service/sword:maxUploadSize');
        $this->assertXpath($xml, 'SHA-1', '/app:service/pkp:uploadChecksumType');
        $this->assertXpath($xml, 'first term.', '/app:service/pkp:terms_of_use/pkp:test.a');
    }

    private function getDepositXml() {
        $xml = <<<"ENDXML"
<?xml version="1.0" encoding="utf-8"?>
<entry xmlns="http://www.w3.org/2005/Atom"
        xmlns:dcterms="http://purl.org/dc/terms/"
        xmlns:pkp="http://pkp.sfu.ca/SWORD">
    <email>email@example.com</email>
    <title>Intl J Test</title>
    <pkp:journal_url>http://test.example.com/path/to/journal</pkp:journal_url>
    <pkp:publisherName>TestPress</pkp:publisherName>
    <pkp:publisherUrl>http://test.example.com/</pkp:publisherUrl>
    <pkp:issn>1111-1111</pkp:issn>
    <!-- deposit uuid -->
    <id>urn:uuid:C672022E-A787-4D09-9511-60A049768A04</id>
    <updated>1969-12-31T16:00:00Z</updated>
    <pkp:content size="619" volume="1" issue="1" pubdate="2014-07-13" checksumType="SHA-1" checksumValue="c3ac694a86e33f126a53023ce7e4b81173e4c4b3">
        http://test.example.com/path/to/journal/pln/deposits/C672022E-A787-4D09-9511-60A049768A04
    </pkp:content>
</entry>
ENDXML;
        return $xml;
    }

    public function testCreateDepositBlacklisted() {
        $uuid = '35a2dc1b-9ba6-4098-bec1-5628af76981e';
        $journalUrl = 'http://test.example.com/path/to/journal';

        $xmlStr = $this->getDepositXml();
        $client = static::createClient();
        $crawler = $client->request(
            'POST',
            '/api/sword/2.0/col-iri/' . $uuid,
            array(),
            array(),
            array(
                'Content-Type' => 'application/xml',
                'HTTP_On-Behalf-Of' => $uuid,
                'HTTP_Journal-Url' => $journalUrl,
            ),
            $xmlStr
        );
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testCreateDepositMissingOnBehalfOf() {
        $uuid = '35a2dc1b-9ba6-4098-bec1-5628af76981e';
        $journalUrl = 'http://test.example.com/path/to/journal';

        $xmlStr = $this->getDepositXml();
        $client = static::createClient();
        $crawler = $client->request(
            'POST',
            '/api/sword/2.0/col-iri/' . $uuid,
            array(),
            array(),
            array(
                'Content-Type' => 'application/xml',
                'HTTP_Journal-Url' => $journalUrl,
            ),
            $xmlStr
        );
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testCreateDepositMissingJournalUrl() {
        $uuid = '35a2dc1b-9ba6-4098-bec1-5628af76981e';
        $journalUrl = 'http://test.example.com/path/to/journal';

        $xmlStr = $this->getDepositXml();
        $client = static::createClient();
        $crawler = $client->request(
            'POST',
            '/api/sword/2.0/col-iri/' . $uuid,
            array(),
            array(),
            array(
                'Content-Type' => 'application/xml',
                'HTTP_On-Behalf-Of' => $uuid,
            ),
            $xmlStr
        );
        $response = $client->getResponse();
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testCreateDepositWhitelisted()
    {
        $uuid = '6646afaa-beba-40c8-a286-c64a3e90d0f6';
        $journalUrl = 'http://test.example.com/path/to/journal';
        
        $xmlStr = $this->getDepositXml();
        $client = static::createClient();
        $crawler = $client->request(
            'POST',
            '/api/sword/2.0/col-iri/' . $uuid,
            array(),
            array(),
            array(
                'Content-Type' => 'application/xml',
                'HTTP_On-Behalf-Of' => $uuid,
                'HTTP_Journal-Url' => $journalUrl,
            ),
            $xmlStr
        );
        $response = $client->getResponse();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals(
            'http://localhost/api/sword/2.0/cont-iri/6646AFAA-BEBA-40C8-A286-C64A3E90D0F6/C672022E-A787-4D09-9511-60A049768A04/state',
            $response->headers->get('Location')
        );

        $xml = $this->getXml($response->getContent());
        $this->assertXpath(
            $xml,
            'application/zip',
            '//atom:content/@type'
        );

        $this->assertXpath(
            $xml,
            '619',
            '//atom:content/@size'
        );

        $this->assertXpath(
            $xml,
            '1',
            '//atom:content/@volume'
        );

        $this->assertXpath(
            $xml,
            '1',
            '//atom:content/@issue'
        );

        $this->assertXpath(
            $xml,
            'http://test.example.com/path/to/journal/pln/deposits/C672022E-A787-4D09-9511-60A049768A04',
            '//atom:content/text()'
        );
    }
        
}

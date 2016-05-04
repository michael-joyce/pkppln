<?php

namespace AppBundle\Controller\SwordController;

use Symfony\Component\HttpFoundation\Response;

class EditDepositTest extends AbstractSwordTestCase {

    public function testEditDepositNotWhitelisted() {}    
    
    public function testEditDepositJournalMissing() {}
    
    public function testEditDepositDepositMissing() {}
    
    public function testEditDepositJournalMismatch() {}
    
    public function testEditDepositSuccess() {
		$depositCount = count($this->em->getRepository('AppBundle:Deposit')->findAll());
		$this->client->request(
            'PUT', 
            '/api/sword/2.0/cont-iri/c0a65967-32bd-4ee8-96de-c469743e563a/d38e7ecb-7d7e-408d-94b0-b00d434fdbd2/edit',
            array(),
            array(),
            array(),
            $this->getEditXml()
		);
        $this->em->clear();
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertEquals($depositCount, count($this->em->getRepository('AppBundle:Deposit')->findAll()));
    }

    private function getEditXml() {
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
        checksumValue="55ca6286e3e4f4fba5d0448333fa99fc5a404a73">http://example.com/deposit/foo.zip
    </pkp:content>
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

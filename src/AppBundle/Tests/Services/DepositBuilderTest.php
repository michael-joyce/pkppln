<?php

namespace AppBundle\Tests\Services;

use AppBundle\Entity\Journal;
use AppBundle\Services\DepositBuilder;
use AppBundle\Utility\AbstractTestCase;
use AppBundle\Utility\Namespaces;
use DateTime;
use Doctrine\Common\Util\Debug;
use SimpleXMLElement;

class DepositBuilderTest extends AbstractTestCase {
    
    /**
     * @var DepositBuilder
     */
    private $builder;
    
    /**
     * @var Namespaces
     */
    private $namespaces;

    public function __construct() {
        parent::__construct();
        $this->namespaces = new Namespaces();
    }
    
    protected function setUp() {
        parent::setUp();
        $this->builder = $this->getContainer()->get('depositbuilder');
    }
    
    public function testBuildDepositRecieptUrl() {
        $deposit = $this->references->getReference('deposit');
        $this->assertEquals('http://localhost/api/sword/2.0/cont-iri/C0A65967-32BD-4EE8-96DE-C469743E563A/D38E7ECB-7D7E-408D-94B0-B00D434FDBD2/state', $this->builder->buildDepositReceiptUrl($deposit));
    }
    
    public function testFromXml() {
        /** @var Journal $journal */
        $journal = $this->references->getReference('journal');    
        $deposit = $this->builder->fromXml($journal, $this->getContentXml(), 'test');
        $this->assertEquals('SHA-1', $deposit->getChecksumType());
        $this->assertEquals('593D10668849', $deposit->getChecksumValue());
        $this->assertEquals('9BF70AAA-6547-4ECE-A379-365E60BB1BB2', $deposit->getDepositUuid());        
        $this->assertNotEmpty($deposit->getFileUuid());        
        $this->assertEquals('', $deposit->getFileType());
        $this->assertEquals(1, $deposit->getIssue());
        $this->assertEquals(5, $deposit->getVolume());
        $this->assertEquals('2015-10-10T13:18:46-07:00', $deposit->getPubDate()->format('c'));
        $this->assertEquals($journal, $deposit->getJournal());
        $this->assertEquals(1234, $deposit->getSize());
        $this->assertEquals('http://example.com', $deposit->getUrl());
        $this->assertEquals('http://localhost/api/sword/2.0/cont-iri/C0A65967-32BD-4EE8-96DE-C469743E563A/9BF70AAA-6547-4ECE-A379-365E60BB1BB2/state', $deposit->getDepositReceipt());
        $this->assertNotEmpty($deposit->getProcessingLog());
    }
    
    /**
     * @return SimpleXMLElement
     */
    protected function getContentXml() {
        $xml = new SimpleXMLElement('<entry />');
        $this->namespaces->registerNamespaces($xml);
        $xml->addAttribute('xmlns', Namespaces::ATOM);
        
        $xml->addChild('title', 'Test title', Namespaces::ATOM);
        $xml->addChild('id', 'urn:uuid:9bf70aaa-6547-4ece-a379-365e60bb1bb2', Namespaces::ATOM);
        $xml->addChild('updated', date(DateTime::ATOM, 1444508326), Namespaces::ATOM);
        
        $author = $xml->addChild('author', null, Namespaces::ATOM);
        $author->addChild('name', 'Me, A Bunny', Namespaces::ATOM);

        $summary = $xml->addChild('summary', 'No content', Namespaces::ATOM);
        $summary->addAttribute('type', 'text');
        
        $content = $xml->addChild('content', 'http://example.com', Namespaces::PKP);
        $content->addAttribute('size', '1234');
        $content->addAttribute('checksumType', 'SHA-1');
        $content->addAttribute('checksumValue', '593d10668849');
        $content->addAttribute('issue', '1');
        $content->addAttribute('volume', '5');
        $content->addAttribute('pubdate', date(DateTime::ATOM, 1444508326));
        return $xml;
    }
    
}

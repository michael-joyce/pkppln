<?php

namespace AppBundle\Tests\Services;

use AppBundle\Services\JournalBuilder;
use AppBundle\Utility\AbstractTestCase;
use AppBundle\Utility\Namespaces;
use DateTime;
use SimpleXMLElement;

class JournalBuilderTest extends AbstractTestCase {
    /**
     * @var JournalBuilder
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
        $this->builder = $this->getContainer()->get('journalbuilder');
    }
    
    public function testFromXml() {
        $journal = $this->builder->fromXml($this->getContentXml(), 'b84acdb2-2508-4998-9a8f-d20aeb75ea50');
        $this->assertEquals('B84ACDB2-2508-4998-9A8F-D20AEB75EA50', $journal->getUuid());
        $this->assertEquals('Test title', $journal->getTitle());
        $this->assertEquals('http://example.com', $journal->getUrl());
        $this->assertEquals('1234-1432', $journal->getIssn());
        $this->assertEquals('Test Publisher', $journal->getPublisherName());
        $this->assertEquals('http://pub.example.com', $journal->getPublisherUrl());
    }

    /**
     * @return SimpleXMLElement
     */
    protected function getContentXml() {
        $xml = new SimpleXMLElement('<entry />');
        $this->namespaces->registerNamespaces($xml);
        $xml->addAttribute('xmlns', Namespaces::ATOM);
        
        $xml->addChild('title', 'Test title', Namespaces::ATOM);
        $xml->addChild('issn', '1234-1432', Namespaces::PKP);
        $xml->addChild('email', 'test@example.com', Namespaces::ATOM);
        $xml->addChild('journal_url', 'http://example.com', Namespaces::PKP);
        $xml->addChild('publisherName', 'Test Publisher', Namespaces::PKP);
        $xml->addChild('publisherUrl', 'http://pub.example.com', Namespaces::PKP);
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
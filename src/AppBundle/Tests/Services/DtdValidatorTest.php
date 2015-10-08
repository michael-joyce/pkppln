<?php

namespace AppBundle\Tests\Services;

use AppBundle\Services\DtdValidator;
use AppBundle\Utility\AbstractTestCase;
use DOMDocument;

class DtdValidatorTest extends AbstractTestCase {
    
    /**
     * @var DtdValidator
     */
    private $validator;
    
    protected function setUp() {
        parent::setUp();
        $this->validator = $this->getContainer()->get('dtdvalidator');
    }
    
    public function testValidateNoErrors() {
        $dom = $this->getValidXml();
        $this->validator->validate($dom, true);
        $this->assertFalse($this->validator->hasErrors());
        $this->assertEquals(0, $this->validator->countErrors());
        $this->assertEquals(array(), $this->validator->getErrors());
    }
    
    public function testValidateWithErrors() {
        $dom = $this->getInvalidXml();
        $this->validator->validate($dom, true);
        $this->assertTrue($this->validator->hasErrors());
        $this->assertEquals(1, $this->validator->countErrors());

        $error = $this->validator->getErrors()[0];
        
        $this->assertStringStartsWith("root and DTD name do not match", $error['message']);
        $this->assertEquals(5, $error['line']);
    }
    
    private function getValidXml() {
        $str = <<<"ENDXML"
<?xml version="1.0" standalone="yes"?>
<!DOCTYPE foo [
  <!ELEMENT foo (#PCDATA)>
]>
<foo>Hello world.</foo>
ENDXML;
        
        return $this->getDom($str);
    }

    private function getInvalidXml() {
        $str = <<<"ENDXML"
<?xml version="1.0" standalone="yes"?>
<!DOCTYPE foo [
  <!ELEMENT foo (#PCDATA)>
]>
<fo>Hello world.</fo>
ENDXML;
        
        return $this->getDom($str);
    }
    
    /**
     * 
     * @return DOMDocument
     */
    private function getDom($str) {
        $dom = new DOMDocument();
        $dom->loadXML($str);
        return $dom;
    }
}

<?php

namespace AppBundle\Services;

use AppBundle\Utility\AbstractTestCase;
use AppBundle\Utility\PingResult;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Subscriber\Mock;

class PingTest extends AbstractTestCase {
	
	/**
	 * @var Ping
	 */
	protected $ping;

	/**
	 * @var PingResult
	 */
	protected $response;

	/**
	 * @var History
	 */
	protected $history;
	
	public function setUp() {
		parent::setUp();
		$journal = $this->references->getReference('journal');
		
		$this->ping = $this->getContainer()->get('ping');
		$client = new Client();
		
		$this->history = new History();
		$client->getEmitter()->attach($this->history);
		
		$mock = new Mock([
			new Response(200),
			$this->getResponseString()
		]);
		$client->getEmitter()->attach($mock);
		
		$this->ping->setClient($client);
		$this->response = $this->ping->ping($journal);
	}

	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadJournals',
		);
	}
	
	public function testInstance() {
		$this->assertInstanceOf('AppBundle\Services\Ping', $this->ping);
	}
	
	public function testPing() {
		$this->assertInstanceOf('AppBundle\Utility\PingResult', $this->response);
	}
	
	public function testPingStatus() {
		$this->assertEquals(200, $this->response->getHttpStatus());
	}
	
	public function testRequestHeaders() {
		$request = $this->history->getLastRequest();
		$this->assertEquals('GET', $request->getMethod());
		$this->assertEquals('PkpPlnBot 1.0; http://pkp.sfu.ca', $request->getHeader('User-Agent'));
	}
	
	private function getResponseString() {
		$str = <<<ENDSTR
HTTP/1.1 200 OK
Date: Fri, 22 Apr 2016 23:25:45 GMT
Content-Type: text/xml; charset=utf-8

<plnplugin>
    <ojsInfo>
        <release>2.4.8.0</release>
    </ojsInfo>
    <pluginInfo>
        <release>1.2.0.0</release>
        <releaseDate>2015-07-13</releaseDate>
        <current>1</current>
        <prerequisites>
            <phpVersion>5.5.31</phpVersion>
            <curlVersion>7.43.0</curlVersion>
            <zipInstalled>yes</zipInstalled>
            <tarInstalled>yes</tarInstalled>
            <acron>yes</acron>
            <tasks>no</tasks>
        </prerequisites>
        <terms termsAccepted="yes">
            <term key="pkp:plugins.generic.pln.terms_of_use.jm_has_authority"
                updated="2016-03-07 17:52:35+00:00" accepted="2016-03-30T17:32:24+00:00"> I have the
                authority to include this journal's content in a secure preservation network and, if
                and when necessary, to make the content available in the PKP PLN. </term>
            <term key="pkp:plugins.generic.pln.terms_of_use.pkp_can_use_address"
                updated="2016-03-07 17:52:35+00:00" accepted="2016-03-30T17:32:24+00:00"> I agree to
                allow the PKP-PLN to include this journal's title and ISSN, and the email address of
                the Primary Contact, with the preserved journal content. </term>
        </terms>
    </pluginInfo>
    <journalInfo>
        <title>Intl J Test</title>
        <articles count="72">
            <article pubDate="2015-07-14 19:57:31">Transnational Publics: Asylum and the Arts in the City of Glasgow</article>
            <article pubDate="2015-07-14 19:57:31">Storytelling and the Lives of Asylum Seekers</article>
        </articles>
    </journalInfo>
</plnplugin>			
ENDSTR;
		return $str;
	}
	
}
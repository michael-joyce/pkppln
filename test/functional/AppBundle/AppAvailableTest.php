<?php

namespace Tests\AppBundle;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AppAvailableTest extends WebTestCase {
	
	/**
	 * @dataProvider urlProvider
	 * @param string $url
	 */
	public function testPageIsSuccessful($url) {
		$client = self::createClient();
		$client->request('GET', $url);
		$this->assertTrue($client->getResponse()->isSuccessful());
	}
	
	public function urlProvider() {
		return array(
			array('/'),
		);
	}
}
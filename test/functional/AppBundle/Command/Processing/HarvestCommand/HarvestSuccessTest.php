<?php

namespace AppBundle\Command\Processing\HarvestCommand;

use AppBundle\Command\Processing\AbstractCommandTestCase;
use AppBundle\Command\Processing\HarvestCommand;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Subscriber\Mock;

class HarvestSuccessTest extends AbstractCommandTestCase {
	
	/**
	 * @var History
	 */
	protected $history;
	
	protected $command;
	
	public function setUp() {		
		$this->command = new HarvestCommand();
		
		$client = new Client();		
		$this->history = new History();
		$client->getEmitter()->attach($this->history);
		$mock = new Mock([
			new Response(200, array('Content-Length' => '123456'), null),
			new Response(200, array('Content-Type' => 'application/zip'), $this->getResponseBody()),
		]);
		$client->getEmitter()->attach($mock);
		$this->command->setClient($client);
		
		parent::setUp();
	}
	
	public function testHarvest() {
		$this->commandTester->execute(array(
			'command' => $this->getCommandName(),
		));
		$deposits =$this->em->getRepository('AppBundle:Deposit')->findBy(array(
			'state' => 'harvest-error'
		));
		
		$this->assertCount(2, $this->history);
		$requests = $this->history->getRequests();
		$this->assertEquals('HEAD', $requests[0]->getMethod());
		$this->assertEquals('GET', $requests[1]->getMethod());
	}

	public function getCommand() {
		return $this->command; // use the injected Guzzle client.
	}

	public function getCommandName() {
		return 'pln:harvest';
	}
	
	protected function getResponseBody() {
		$str = <<<ENDSTR
				absclksd
ENDSTR;
		$stream = Stream::factory($str);
		return $stream;
	}
}
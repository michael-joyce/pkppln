<?php

namespace AppUserBundle\Command;

use AppBundle\Command\Processing\AbstractCommandTestCase;

class CreateUserCommandTest extends AbstractCommandTestCase {

	public function getCommand() {
		return new CreateUserCommand();
	}

	public function getCommandName() {
		return 'fos:user:create';
	}

	public function testCreateUser() {
		
	}
	
}
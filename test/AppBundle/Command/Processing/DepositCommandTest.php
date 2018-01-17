<?php

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use AppBundle\Utility\AbstractCommandTestCase;

class DepositCommandTest extends AbstractCommandTestCase {

    public function setUp() {
        parent::setUp();
    }

    public function getCommand() {
        return new DepositCommand();
    }

    public function getCommandName() {
        return 'pln:deposit';
    }

    public function testHeldDeposit() {
        $deposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
        $deposit->setState('reserialized');
        $deposit->setJournalVersion('3.0.0');
        $this->em->flush();
        
        $this->commandTester->execute(array(
            'command' => $this->getCommandName(),
        ));
        $this->em->clear();
        $processedDeposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
        $this->assertEquals('hold', $processedDeposit->getState());
    }

    public function testSentDeposit() {
        $deposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
        $deposit->setState('reserialized');
        $deposit->setJournalVersion('2.0');
        $this->em->flush();
        
        $this->commandTester->execute(array(
            'command' => $this->getCommandName(),
        ));
        $this->em->clear();
        $processedDeposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
        $this->assertEquals('deposit-error', $processedDeposit->getState());
    }
}

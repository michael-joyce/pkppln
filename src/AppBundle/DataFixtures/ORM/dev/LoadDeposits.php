<?php

namespace AppBundle\DataFixtures\ORM\dev;

use AppBundle\Entity\Deposit;
use AppBundle\Utility\AbstractDataFixture;
use DateTime;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadDeposits extends AbstractDataFixture implements OrderedFixtureInterface {

    public function getOrder() {
        return 2; // must be after LoadJournals.
    }

    protected function doLoad(ObjectManager $manager) {
        $deposit = new Deposit();
        $deposit->setAction('add');
        $deposit->setChecksumType('SHA1');
        $deposit->setChecksumValue('03cfd743661f07975fa2f1220c5194cbaff48451');
        $deposit->setDepositUuid('d38e7ecb-7d7e-408d-94b0-b00d434fdbd2');
        $deposit->setDepositReceipt('http://example.com/path/to/reciept');
        $deposit->setFileType('application/zip');
        $deposit->setFileUuid('96c0e180-17e6-4b12-a1d2-c9ff3131820a');
        $deposit->setIssue(2);
        $deposit->setJournal($this->getReference('journal'));
        $deposit->setPubDate(new DateTime());
        $deposit->setSize(100);
        $deposit->setState('deposited');
        $deposit->setUrl('http://journal.example.com/path/to/deposit');
        $deposit->setVolume(1);
        $manager->persist($deposit);
        $manager->flush();
        $this->setReference('deposit', $deposit);
    }

    protected function getEnvironments() {
        return array('dev');
    }

}
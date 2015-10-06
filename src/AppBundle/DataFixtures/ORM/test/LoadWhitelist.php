<?php

namespace AppBundle\DataFixtures\ORM\test;

use AppBundle\Entity\Whitelist;
use AppBundle\Utility\AbstractDataFixture;
use Doctrine\Common\Persistence\ObjectManager;

class LoadWhitelist extends AbstractDataFixture {

    protected function doLoad(ObjectManager $manager) {
        $entry = new Whitelist();
        $entry->setComment('Test entry');
        $entry->setUuid('6646afaa-beba-40c8-a286-c64a3e90d0f6');
        $manager->persist($entry);
        $manager->flush();
        $this->setReference('wl', $entry);
    }

    protected function getEnvironments() {
        return array('test');
    }

}

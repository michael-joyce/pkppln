<?php

namespace AppUserBundle\DataFixtures\ORM\dev;

use AppBundle\Utility\AbstractDataFixture;
use AppUserBundle\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUsers extends AbstractDataFixture {

    /**
     * {@inheritDoc}
     */
    public function doLoad(ObjectManager $manager) {
        $user = new User();
        $user->setEmail("admin@example.com");
        $user->setFullname("Admin user");
        $user->setUsername("admin@example.com");
        $user->setPlainPassword("supersecret");
        $user->setRoles(array('ROLE_ADMIN'));
        $user->setEnabled(true);
        $manager->persist($user);
        $manager->flush();
    }

    protected function getEnvironments() {
        return array('dev');
    }

}

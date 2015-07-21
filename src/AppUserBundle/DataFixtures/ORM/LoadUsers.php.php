<?php

namespace AppUserBundle\DataFixtures\ORM;

use AppUserBundle\Entity\User;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUsers implements FixtureInterface {

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager) {
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

}

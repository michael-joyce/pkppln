<?php

namespace AppUserBundle\DataFixtures\ORM\test;

use AppBundle\Utility\AbstractDataFixture;
use AppUserBundle\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;

class LoadUsers extends AbstractDataFixture {

    /**
     * {@inheritDoc}
     */
    public function doLoad(ObjectManager $manager) {
        $admin = new User();
        $admin->setEmail("admin@example.com");
        $admin->setFullname("Admin user");
        $admin->setUsername("admin@example.com");
        $admin->setPlainPassword("supersecret");
        $admin->setRoles(array('ROLE_ADMIN'));
        $admin->setEnabled(true);
        $manager->persist($admin);

        $user = new User();
        $user->setEmail("user@example.com");
        $user->setFullname("Unprivileged user");
        $user->setUsername("user@example.com");
        $user->setPlainPassword("supersecret");
        $user->setEnabled(true);
        $manager->persist($user);
        $manager->flush();
    }

    protected function getEnvironments() {
        return array('test');
    }

}

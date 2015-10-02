<?php

namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;

class BlackWhitelist {

    /**
     * @var ObjectManager
     */
    private $em;

    public function __construct(Registry $doctrine) {
        $this->em = $doctrine->getManager();
    }

    public function isWhitelisted($uuid) {
        $repo = $this->em->getRepository('AppBundle:Whitelist');
        return $repo->findOneBy(array('uuid' => $uuid)) !== null;
    }

    public function isBlacklisted($uuid) {
        $repo = $this->em->getRepository('AppBundle:Blacklist');
        return $repo->findOneBy(array('uuid' => $uuid)) !== null;
    }

}
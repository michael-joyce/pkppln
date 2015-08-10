<?php

namespace AppBundle\Services;

use AppBundle\Entity\Journal;
use Doctrine\Bundle\DoctrineBundle\Registry;

class BlackWhitelist {

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
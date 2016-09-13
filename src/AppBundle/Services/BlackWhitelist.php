<?php

/*
 * Copyright (C) 2015-2016 Michael Joyce <ubermichael@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AppBundle\Services;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;

/**
 * Service to check a UUID for whitelist/blacklist status.
 */
class BlackWhitelist
{
    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * Construct the service.
     *
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->em = $doctrine->getManager();
    }

    /**
     * Return true if the uuid is whitelisted.
     *
     * @param string $uuid
     *
     * @return bool
     */
    public function isWhitelisted($uuid)
    {
        $repo = $this->em->getRepository('AppBundle:Whitelist');

        return $repo->findOneBy(array(
            'uuid' => strtoupper($uuid), )) !== null;
    }

    /**
     * Return true if the uuid is blacklisted.
     *
     * @param string $uuid
     *
     * @return bool
     */
    public function isBlacklisted($uuid)
    {
        $repo = $this->em->getRepository('AppBundle:Blacklist');

        return $repo->findOneBy(array(
            'uuid' => strtoupper($uuid), )) !== null;
    }
}

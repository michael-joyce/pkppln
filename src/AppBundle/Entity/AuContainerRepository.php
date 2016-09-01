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

namespace AppBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityRepository;

/**
 * AuContainerRepository makes it easy to find AuContainers.
 */
class AuContainerRepository extends EntityRepository
{
    /**
     * Find the open container with the lowest database ID. There should only
     * ever be one open container, but finding the one with lowest database ID
     * guarantees it.
     * 
     * @return Collection|AuContainer[]
     */
    public function getOpenContainer()
    {
        return $this->findOneBy(
            array('open' => true),
            array('id' => 'ASC')
        );
    }
}

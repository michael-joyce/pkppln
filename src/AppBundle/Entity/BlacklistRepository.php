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

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Func;

/**
 * BlacklistRepository makes it easy to search for blacklist entities.
 */
class BlacklistRepository extends EntityRepository
{
    /**
     * Search for blacklist entries by uuid or comment.
     *
     * @param string $q
     *
     * @return Collection|Blacklist[]
     */
    public function search($q)
    {
        $qb = $this->createQueryBuilder('b');
        $qb->where(
            $qb->expr()->like(
                new Func(
                    'CONCAT',
                    array(
                        'b.uuid',
                        'b.comment',
                    )
                ),
                "'%$q%'"
            )
        );
        $query = $qb->getQuery();
        $listed = $query->getResult();

        return $listed;
    }
}

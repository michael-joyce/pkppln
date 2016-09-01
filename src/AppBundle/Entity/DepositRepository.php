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

/**
 * DepositRepository provides some useful functions for querying deposits.
 */
class DepositRepository extends EntityRepository
{
    /**
     * Find deposits by state.
     * 
     * @param string $state
     * 
     * @return Collection|Deposit[]
     */
    public function findByState($state)
    {
        return $this->findBy(array(
            'state' => $state,
        ));
    }

    /**
     * Summarize deposits by counting them by state.
     * 
     * @return array
     */
    public function stateSummary()
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('e.state, count(e) as ct')
                ->groupBy('e.state')
                ->orderBy('e.state');

        return $qb->getQuery()->getResult();
    }

    /**
     * Search for deposits by UUID or part of a UUID.
     * 
     * @param string $q
     * 
     * @return Collection|Deposit[]
     */
    public function search($q)
    {
        $qb = $this->createQueryBuilder('d');
        $qb->where('d.depositUuid LIKE :q');
        $qb->setParameter('q', '%'.strtoupper($q).'%');

        return $qb->getQuery()->getResult();
    }

    /**
     * Return some recent deposits. 
     * 
     * @todo this should be called findRecent.
     * 
     * @param type $limit
     *
     * @return Collection|Deposit[]
     */
    public function findNew($limit = 5)
    {
        $qb = $this->createQueryBuilder('d');
        $qb->orderBy('d.id', 'DESC');
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}

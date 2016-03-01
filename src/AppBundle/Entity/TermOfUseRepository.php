<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * TermOfUseRepository
 */
class TermOfUseRepository extends EntityRepository
{
    /**
     * Get the terms, ordered by weight.
     *
     * @return TermOfUse[]
     */
    public function getTerms() {
        $qb = $this->createQueryBuilder('t')
                ->orderBy('t.weight', 'ASC')
                ->getQuery();
        return $qb->getResult();
    }
}

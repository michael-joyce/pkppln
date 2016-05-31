<?php

namespace AppBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Func;

/**
 * BlacklistRepository.
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

<?php

namespace AppBundle\Entity;

use DateInterval;
use DateTime;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Func;

/**
 * JournalRepository
 *
 * This class adds a simple journal search.
 */
class JournalRepository extends EntityRepository {

    /**
     * Search for a journal.
     *
     * @param string $q
     * @return Journal[]
     */
    public function search($q) {
        $qb = $this->createQueryBuilder('j');
        $qb->where(
                $qb->expr()->like(
                        new Func(
                                'CONCAT', 
                                array('j.title', 'j.issn', 'j.url', 'j.email', 'j.publisherName')),
                        "'%$q%'"
                )
        );
        $query = $qb->getQuery();
        $journals = $query->getResult();
        return $journals;
    }

    /**
     * Find journals by status.
     *
     * @param string $status
     * @return Journal[]
     */
    public function findByStatus($status) {
        return $this->findBy(array(
            'status' => $status,
        ));
    }

    public function statusSummary() {
        $qb = $this->createQueryBuilder('e');
        $qb->select('e.status, count(e) as ct')
                ->groupBy('e.status')
                ->orderBy('e.status');
        return $qb->getQuery()->getResult();
    }

    public function findSilent($days) {
        $dt = new DateTime("-{$days} day");

        $qb = $this->createQueryBuilder('e');
        $qb->where("e.status = 'healthy'");
        $qb->where('e.contacted < :dt');
        $qb->setParameter('dt', $dt);
        return $qb->getQuery()->getResult();
    }
}

<?php

namespace AppBundle\Entity;

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
                    array(
                        'j.title', 
						'j.uuid',
                        'j.issn', 
                        'j.url', 
                        'j.email', 
						'j.publisherName'
					)
                ),
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

    /**
     * Summarize the journal statuses, counting them by status.
     * 
     * @return array
     */
    public function statusSummary() {
        $qb = $this->createQueryBuilder('e');
        $qb->select('e.status, count(e) as ct')
            ->groupBy('e.status')
            ->orderBy('e.status');
        return $qb->getQuery()->getResult();
    }

    /**
     * Find journals that haven't contacted the PLN in $days.
     * 
     * @param integer $days
     * @return Journal[]
     */
    public function findSilent($days) {
        $dt = new DateTime("-{$days} day");

        $qb = $this->createQueryBuilder('e');
        $qb->andWhere('e.contacted < :dt');
        $qb->setParameter('dt', $dt);
        return $qb->getQuery()->getResult();
    }

    /**
     * Find journals that have gone silent and that notifications have been sent
     * for, but they have not been updated yet.
     * 
     * @param int $days
     * @return Journal[]
     */
    public function findOverdue($days) {
        $dt = new DateTime("-{$days} day");
        $qb = $this->createQueryBuilder('e');
        $qb->where("e.status = 'unhealthy'");
        $qb->andWhere('e.notified < :dt');
        $qb->setParameter('dt', $dt);
        return $qb->getQUery()->getResult();
    }
    
	/**
	 * @todo This method should be called findRecent(). It does not find
	 * journals with status=new.
	 * 
	 * @param type $limit
	 * @return type
	 */
    public function findNew($limit = 5) {
        $qb = $this->createQueryBuilder('e');
        $qb->orderBy('e.id', 'DESC');
        $qb->setMaxResults($limit);
        return $qb->getQuery()->getResult();
    }
}

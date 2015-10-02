<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\TermOfUse;
use AppBundle\Entity\TermOfUseHistory;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Monolog\Logger;
use Symfony\Component\Security\Core\SecurityContext;

class TermsOfUseListener {
    
    /**
     * @var Logger
     */
    private $logger;
    
    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }
    
    protected function saveHistory(LifecycleEventArgs $args, $action) {
        $entity = $args->getEntity();
        if( ! $entity instanceof TermOfUse) {
            return;
        }
        $this->logger->error("{$action}");
        
        $em = $args->getEntityManager();        
        $unitOfWork = $em->getUnitOfWork();
        $changeSet = $unitOfWork->getEntityChangeSet($entity);
        $history = new TermOfUseHistory();
        $history->setTerm($entity);
        $history->setAction($action);
        $history->setChangeSet($changeSet);
        $history->setUser('');
        $em->persist($history);
        $em->flush($history);
    }
    
    public function postPersist(LifecycleEventArgs $args) {
        $this->saveHistory($args, 'create');
    }
    
    public function postUpdate(LifecycleEventArgs $args) {
        $this->saveHistory($args, 'update');
    }
    
    public function postRemove(LifecycleEventArgs $args) {
        $this->saveHistory($args, 'delete');
    }
    
}
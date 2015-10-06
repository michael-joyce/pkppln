<?php

namespace AppBundle\EventListener;

use AppBundle\Entity\TermOfUse;
use AppBundle\Entity\TermOfUseHistory;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\UnitOfWork;
use Monolog\Logger;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class TermsOfUseListener {

    /**
     * @var Logger
     */
    private $logger;
    
    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    public function setLogger(Logger $logger) {
        $this->logger = $logger;
    }
    
    public function setTokenStorage(TokenStorage $tokenStorage) {
        $this->tokenStorage = $tokenStorage;
    }
    
    protected function getChangeSet(UnitOfWork $unitOfWork, TermOfUse $entity, $action) {
        switch ($action) {
            case 'create':
                return array(
                    'id' => array(null, $entity->getId()),
                    'weight' => array(null, $entity->getWeight()),
                    'keyCode' => array(null, $entity->getKeyCode()),
                    'langCode' => array(null, $entity->getLangCode()),
                    'content' => array(null, $entity->getContent()),
                );
            case 'update':
                return $unitOfWork->getEntityChangeSet($entity);
            case 'delete':
                return array(
                    'id' => array($entity->getId(), null),
                    'weight' => array($entity->getWeight(), null),
                    'keyCode' => array($entity->getKeyCode(), null),
                    'langCode' => array($entity->getLangCode(), null),
                    'content' => array($entity->getContent(), null),
                );
        }
    }

    protected function saveHistory(LifecycleEventArgs $args, $action) {
        $entity = $args->getEntity();
        if (!$entity instanceof TermOfUse) {
            return;
        }

        $em = $args->getEntityManager();
        $unitOfWork = $em->getUnitOfWork();
        $changeSet = $this->getChangeSet($unitOfWork, $entity, $action);

        $history = new TermOfUseHistory();
        $history->setTermId($entity->getId());
        $history->setAction($action);
        $history->setChangeSet($changeSet);
        $token = $this->tokenStorage->getToken();
        if($token) {
            $history->setUser($token->getUsername());
        } else {
            $history->setUser('console');
        }
        $em->persist($history);
        $em->flush($history); // these are post-whatever events, after a flush.
    }

    public function postPersist(LifecycleEventArgs $args) {
        $this->saveHistory($args, 'create');
    }

    public function postUpdate(LifecycleEventArgs $args) {
        $this->saveHistory($args, 'update');
    }

    public function preRemove(LifecycleEventArgs $args) {
        $this->saveHistory($args, 'delete');
    }

}

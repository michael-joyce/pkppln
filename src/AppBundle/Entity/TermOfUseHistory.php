<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * TermOfUseHistory
 * 
 * A new TermOfUseHistory object is created every time a Term of Use is created,
 * updated, or deleted.
 *
 * @ORM\Table()
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="TermOfUseHistoryRepository")
 */
class TermOfUseHistory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @var integer
     * @ORM\Column(type="integer")
     */
    private $termId;
    
    /**
     * @var string
     * 
     * @ORM\Column(type="string")
     */
    private $action;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $user;

    /**
     * The date the term was created. Terms are never updated - new ones 
     * are created as needed.
     *
     * @var string
     * 
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * The change set.
     *
     * @var string
     * 
     * @ORM\Column(type="array")
     */    
    private $changeSet;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * Set action
     *
     * @param string $action
     * @return TermOfUseHistory
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string 
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set user
     *
     * @param string $user
     * @return TermOfUseHistory
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return string 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set created
     *
     * @param DateTime $created
     * @return TermOfUseHistory
     */
    public function setCreated($created)
    {
        if($this->created === null) {
            $this->created = $created;
        }

        return $this;
    }

    /**
     * Get created
     *
     * @return DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @ORM\PrePersist
     */
    public function persistCreated() {
        if($this->created === null) {
            $this->created = new DateTime();
        }
    }
    
    /**
     * Set changeSet
     *
     * @param array $changeSet
     * @return TermOfUseHistory
     */
    public function setChangeSet($changeSet)
    {
        $this->changeSet = $changeSet;

        return $this;
    }

    /**
     * Get changeSet
     *
     * @return array 
     */
    public function getChangeSet()
    {
        return $this->changeSet;
    }

    /**
     * Set term id
     *
     * @return TermOfUseHistory
     */
    public function setTermId($termId)
    {
        $this->termId = $termId;

        return $this;
    }

    /**
     * Get term id
     *
     * @return TermOfUse 
     */
    public function getTermId()
    {
        return $this->termId;
    }
}

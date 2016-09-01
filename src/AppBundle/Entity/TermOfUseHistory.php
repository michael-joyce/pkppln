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

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * TermOfUseHistory.
 * 
 * A new TermOfUseHistory object is created every time a Term of Use is created,
 * updated, or deleted. The history object is created by an event listener.
 *
 * @see AppBundle\EventListener\TermsOfUseListener
 * 
 * @ORM\Table()
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="TermOfUseHistoryRepository")
 */
class TermOfUseHistory
{
    /**
     * Database ID.
     * 
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * A term ID, similar to the OJS translation keys.
     * 
     * @var int
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
     * The user who added/edited/deleted the term of use.
     * 
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
     * The change set, as computed by Doctrine.
     *
     * @var string
     * 
     * @ORM\Column(type="array")
     */
    private $changeSet;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set action, one of create, update, delete.
     *
     * @param string $action
     *
     * @return TermOfUseHistory
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action.
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set user.
     *
     * @param string $user
     *
     * @return TermOfUseHistory
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set created.
     *
     * @param DateTime $created
     *
     * @return TermOfUseHistory
     */
    public function setCreated($created)
    {
        if ($this->created === null) {
            $this->created = $created;
        }

        return $this;
    }

    /**
     * Get created.
     *
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Automatically sets the created date.
     * 
     * @ORM\PrePersist
     */
    public function persistCreated()
    {
        if ($this->created === null) {
            $this->created = new DateTime();
        }
    }

    /**
     * Set changeSet.
     *
     * @param array $changeSet
     *
     * @return TermOfUseHistory
     */
    public function setChangeSet($changeSet)
    {
        $this->changeSet = $changeSet;

        return $this;
    }

    /**
     * Get changeSet.
     *
     * @return array
     */
    public function getChangeSet()
    {
        return $this->changeSet;
    }

    /**
     * Set term id.
     *
     * @return TermOfUseHistory
     */
    public function setTermId($termId)
    {
        $this->termId = $termId;

        return $this;
    }

    /**
     * Get term id.
     *
     * @return TermOfUse
     */
    public function getTermId()
    {
        return $this->termId;
    }
}

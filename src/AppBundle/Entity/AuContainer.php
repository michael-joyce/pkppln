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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * AuContainer organizes the deposits by size to abstract the responsibility 
 * away from LOCKSSOMatic.
 *
 * @ORM\Table()
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="AuContainerRepository")
 */
class AuContainer
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
     * List of deposits in one AU.
     *
     * @var Deposit[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="Deposit", mappedBy="auContainer")
     */
    private $deposits;

    /**
     * True if the container can accept more deposits.
     * 
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $open;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->deposits = new ArrayCollection();
        $this->open = true;
    }

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
     * Add deposits.
     *
     * @param Deposit $deposit
     *
     * @return AuContainer
     */
    public function addDeposit(Deposit $deposit)
    {
        $this->deposits[] = $deposit;

        return $this;
    }

    /**
     * Remove deposits.
     *
     * @param Deposit $deposits
     */
    public function removeDeposit(Deposit $deposits)
    {
        $this->deposits->removeElement($deposits);
    }

    /**
     * Get deposits.
     *
     * @return Collection
     */
    public function getDeposits()
    {
        return $this->deposits;
    }

    /**
     * Set open. An open container can be made closed, but a closed container
     * cannot be reopened.
     * 
     * @param type $open
     *
     * @return AuContainer
     */
    public function setOpen($open)
    {
        if ($this->open) {
            // Only change an open container to closed.
            $this->open = $open;
        }

        return $this;
    }

    /**
     * Get open.
     * 
     * @return bool
     */
    public function isOpen()
    {
        return $this->open;
    }

    /**
     * Get the size of the container in bytes.
     * 
     * @return int
     */
    public function getSize()
    {
        $size = 0;
        foreach ($this->deposits as $deposit) {
            $size += $deposit->getPackageSize();
        }

        return $size;
    }

    /**
     * Count the deposits in the container.
     * 
     * @return int
     */
    public function countDeposits()
    {
        return $this->deposits->count();
    }
}

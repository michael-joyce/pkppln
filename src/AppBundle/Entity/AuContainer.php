<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * AuContainer
 *
 * @ORM\Table()
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="AuContainerRepository")
 */
class AuContainer {

    /**
     * Database ID
     * 
     * @var integer
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
     * @var boolean
     * @ORM\Column(type="boolean")
     */
    private $open;

    /**
     * Constructor
     */
    public function __construct() {
        $this->deposits = new ArrayCollection();
        $this->open = true;
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Add deposits
     *
     * @param Deposit $deposits
     * @return AuContainer
     */
    public function addDeposit(Deposit $deposits) {
        $this->deposits[] = $deposits;

        return $this;
    }

    /**
     * Remove deposits
     *
     * @param Deposit $deposits
     */
    public function removeDeposit(Deposit $deposits) {
        $this->deposits->removeElement($deposits);
    }

    /**
     * Get deposits
     *
     * @return Collection 
     */
    public function getDeposits() {
        return $this->deposits;
    }
    
    public function setOpen($open) {
        if($this->open) {
            // Only change an open container to closed.
            $this->open = $open;
        }
        return $this;
    }
    
    public function isOpen() {
        return $this->open;
    }

    public function getSize() {
        $size = 0;
        foreach ($this->deposits as $deposit) {
            $size += $deposit->getPackageSize();
        }
        return $size;
    }

    public function countDeposits() {
        return $this->deposits->count();
    }

}

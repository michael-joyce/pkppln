<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MicroService
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="AppBundle\Entity\MicroServiceRepository")
 */
class MicroService
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
     * The deposit that was processed.
     *
     * @var AppBundle\Entity\Deposit
     * 
     * @ORM\ManyToOne(targetEntity="Deposit", inversedBy="services")
     * @ORM\JoinColumn(name="deposit_id", referencedColumnName="id")
     */
    private $deposit;

    /**
     * The service that processed the deposit.
     *
     * @var string
     * 
     * @ORM\Column(type="string")
     */
    private $microservice;

    /**
     * When the processing started.
     *
     * @var string
     * 
     * @ORM\Column(type="datetime")
     */
    private $started;

    /**
     * When the processing completed.
     *
     * @var string
     * 
     * @ORM\Column(type="datetime")
     */
    private $finished;

    /**
     * The outcome (success/failure) of processing.
     *
     * @var string
     * 
     * @ORM\Column(type="string")
     */
    private $outcome;

    /**
     * The complete error message from the microservice.
     *
     * @var string
     * 
     * @ORM\Column(type="text", nullable=true) 
     */
    private $error;
    
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
     * Set microservice
     *
     * @param string $microservice
     * @return MicroService
     */
    public function setMicroservice($microservice)
    {
        $this->microservice = $microservice;

        return $this;
    }

    /**
     * Get microservice
     *
     * @return string 
     */
    public function getMicroservice()
    {
        return $this->microservice;
    }

    /**
     * Set started
     *
     * @param \DateTime $started
     * @return MicroService
     */
    public function setStarted($started)
    {
        $this->started = $started;

        return $this;
    }

    /**
     * Get started
     *
     * @return \DateTime 
     */
    public function getStarted()
    {
        return $this->started;
    }

    /**
     * Set finished
     *
     * @param \DateTime $finished
     * @return MicroService
     */
    public function setFinished($finished)
    {
        $this->finished = $finished;

        return $this;
    }

    /**
     * Get finished
     *
     * @return \DateTime 
     */
    public function getFinished()
    {
        return $this->finished;
    }

    /**
     * Set outcome
     *
     * @param string $outcome
     * @return MicroService
     */
    public function setOutcome($outcome)
    {
        $this->outcome = $outcome;

        return $this;
    }

    /**
     * Get outcome
     *
     * @return string 
     */
    public function getOutcome()
    {
        return $this->outcome;
    }

    /**
     * Set error
     *
     * @param string $error
     * @return MicroService
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Get error
     *
     * @return string 
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set deposit
     *
     * @param \AppBundle\Entity\Deposit $deposit
     * @return MicroService
     */
    public function setDeposit(\AppBundle\Entity\Deposit $deposit = null)
    {
        $this->deposit = $deposit;

        return $this;
    }

    /**
     * Get deposit
     *
     * @return \AppBundle\Entity\Deposit 
     */
    public function getDeposit()
    {
        return $this->deposit;
    }
}

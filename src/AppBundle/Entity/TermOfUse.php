<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * TermOfUse
 *
 * @ORM\Table()
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="TermOfUseRepository")
 */
class TermOfUse {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * The "weight" of the term. Heavier terms are sorted lower.
     *
     * @var int
     * 
     * @ORM\Column(type="integer")
     */
    private $weight;

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
     * A term key code, something unique to all versions and translations
     * of a term.
     *
     * @var string
     * 
     * @ORM\Column(type="string")
     */
    private $keyCode;

    /**
     * ISO language code.
     *
     * @var string
     * 
     * @ORM\Column(type="string")
     */
    private $langCode = "en-US";

    /**
     * The content of the term, in the language in $langCode.
     *
     * @var string
     * 
     * @ORM\Column(type="string")
     */
    private $content;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     * @return TermOfUse
     */
    public function setWeight($weight) {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer 
     */
    public function getWeight() {
        return $this->weight;
    }

    /**
     * Set created
     *
     * @param DateTime $created
     * @return TermOfUse
     */
    public function setCreated(DateTime $created) {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return DateTime
     */
    public function getCreated() {
        return $this->created;
    }

    /**
     * Set keyCode
     *
     * @param string $keyCode
     * @return TermOfUse
     */
    public function setKeyCode($keyCode) {
        $this->keyCode = $keyCode;

        return $this;
    }

    /**
     * Get keyCode
     *
     * @return string 
     */
    public function getKeyCode() {
        return $this->keyCode;
    }

    /**
     * Set langCode
     *
     * @param string $langCode
     * @return TermOfUse
     */
    public function setLangCode($langCode) {
        $this->langCode = $langCode;

        return $this;
    }

    /**
     * Get langCode
     *
     * @return string 
     */
    public function getLangCode() {
        return $this->langCode;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return TermOfUse
     */
    public function setContent($content) {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string 
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * @ORM\PrePersist
     */
    public function setTimestamp() {
        $this->created = new DateTime();
    }

    public function __toString() {
        return $this->content;
    }

}

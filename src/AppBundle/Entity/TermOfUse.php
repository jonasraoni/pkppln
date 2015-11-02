<?php

namespace AppBundle\Entity;

use DateTime;
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
	 * The date the term was created. 
	 * 
	 * @var string
	 * 
	 * @ORM\Column(type="datetime")
	 */
	private $created;

	/**
	 * The date the term was updated. 
	 *
	 * @var string
	 * 
	 * @ORM\Column(type="datetime")
	 */
	private $updated;

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
	private $langCode;

	/**
	 * The content of the term, in the language in $langCode.
	 *
	 * @var string
	 * 
	 * @ORM\Column(type="text")
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
	public function setCreatedTimestamp() {
		if ($this->created === null) {
			$this->created = new DateTime();
		}
		if ($this->updated === null) {
			$this->updated = new DateTime();
		}
	}

	/**
	 * @ORM\PreUpdate
	 * @return type
	 */
	public function setUpdatedTimestamp() {
		$this->updated = new DateTime();
	}

	/**
	 * The term's content is a stringified representation.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->content;
	}

	/**
	 * Set updated
	 *
	 * @param \DateTime $updated
	 * @return TermOfUse
	 */
	public function setUpdated($updated) {
		$this->updated = $updated;

		return $this;
	}

	/**
	 * Get updated
	 *
	 * @return \DateTime 
	 */
	public function getUpdated() {
		return $this->updated;
	}

}

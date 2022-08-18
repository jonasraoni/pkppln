<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nines\UtilBundle\Entity\AbstractEntity;

/**
 * TermOfUse.
 *
 * @ORM\Table(name="term_of_use")
 * @ORM\Entity(repositoryClass="App\Repository\TermOfUseRepository")
 */
class TermOfUse extends AbstractEntity {
    /**
     * The "weight" of the term. Heavier terms are sorted lower.
     *
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $weight;

    /**
     * A term key code, something unique to all versions and translations of a term.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $keyCode;

    /**
     * The content of the term, in the language in $langCode.
     *
     * @var string
     *
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * Construct the term of use.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Return the term's content.
     */
    public function __toString() : string {
        return $this->content;
    }

    /**
     * Set weight.
     *
     * @param int $weight
     *
     * @return TermOfUse
     */
    public function setWeight($weight) {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight.
     *
     * @return int
     */
    public function getWeight() {
        return $this->weight;
    }

    /**
     * Set keyCode.
     *
     * @param string $keyCode
     *
     * @return TermOfUse
     */
    public function setKeyCode($keyCode) {
        $this->keyCode = $keyCode;

        return $this;
    }

    /**
     * Get keyCode.
     *
     * @return string
     */
    public function getKeyCode() {
        return $this->keyCode;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return TermOfUse
     */
    public function setContent($content) {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent() {
        return $this->content;
    }
}

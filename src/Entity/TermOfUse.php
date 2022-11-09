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
 * @ORM\HasLifecycleCallbacks
 */
class TermOfUse extends AbstractEntity
{
    /**
     * The "weight" of the term. Heavier terms are sorted lower.
     *
     * @ORM\Column(type="integer")
     */
    private int $weight;

    /**
     * A term key code, something unique to all versions and translations of a term.
     *
     * @ORM\Column(type="string")
     */
    private string $keyCode;

    /**
     * The content of the term, in the language in $langCode.
     *
     * @ORM\Column(type="text")
     */
    private string $content;

    /**
     * Return the term's content.
     */
    public function __toString(): string
    {
        return $this->content;
    }

    /**
     * Set weight.
     */
    public function setWeight(int $weight): static
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight.
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    /**
     * Set keyCode.
     */
    public function setKeyCode(string $keyCode): static
    {
        $this->keyCode = $keyCode;

        return $this;
    }

    /**
     * Get keyCode.
     */
    public function getKeyCode(): string
    {
        return $this->keyCode;
    }

    /**
     * Set content.
     */
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     */
    public function getContent(): string
    {
        return $this->content;
    }
}

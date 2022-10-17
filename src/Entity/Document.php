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
 * Document.
 *
 * @ORM\Table(name="document")
 * @ORM\Entity(repositoryClass="App\Repository\DocumentRepository")
 */
class Document extends AbstractEntity
{
    /**
     * Document title.
     *
     * @ORM\Column(type="string", nullable=false)
     */
    private string $title;

    /**
     * The URL slug for the document.
     *
     * @ORM\Column(type="string", nullable=false)
     */
    private string $path;

    /**
     * A brief summary to display on the list of documents.
     *
     * @ORM\Column(type="text", nullable=false)
     */
    private string $summary;

    /**
     * The content.
     *
     * @ORM\Column(type="text", nullable=false)
     */
    private string $content;

    /**
     * Build the document object.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return the document title.
     */
    public function __toString(): string
    {
        return $this->title;
    }

    /**
     * Set title.
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set path.
     */
    public function setPath(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set summary.
     */
    public function setSummary(string $summary): static
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * Get summary.
     */
    public function getSummary(): string
    {
        return $this->summary;
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

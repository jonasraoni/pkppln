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
class Document extends AbstractEntity {
    /**
     * Document title.
     *
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $title;

    /**
     * The URL slug for the document.
     *
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $path;

    /**
     * A brief summary to display on the list of documents.
     *
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    private $summary;

    /**
     * The content.
     *
     * @var string
     * @ORM\Column(type="text", nullable=false)
     */
    private $content;

    /**
     * Build the document object.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Return the document title.
     */
    public function __toString() : string {
        return $this->title;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Document
     */
    public function setTitle($title) {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set path.
     *
     * @param string $path
     *
     * @return Document
     */
    public function setPath($path) {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path.
     *
     * @return string
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Set summary.
     *
     * @param string $summary
     *
     * @return Document
     */
    public function setSummary($summary) {
        $this->summary = $summary;

        return $this;
    }

    /**
     * Get summary.
     *
     * @return string
     */
    public function getSummary() {
        return $this->summary;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return Document
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

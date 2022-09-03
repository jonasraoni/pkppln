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
 * TermOfUseHistory.
 *
 * @ORM\Table(name="term_of_use_history")
 * @ORM\Entity(repositoryClass="App\Repository\TermOfUseHistoryRepository")
 */
class TermOfUseHistory extends AbstractEntity
{
    /**
     * A term ID, similar to the OJS translation keys.
     *
     * @ORM\Column(type="integer")
     */
    private int $termId;

    /**
     * The history action: add, updated, remove.
     *
     * @ORM\Column(type="string")
     */
    private string $action;

    /**
     * The change set, as computed by Doctrine.
     *
     * @ORM\Column(type="array")
     */
    private array $changeSet;

    /**
     * The user who added/edited/deleted the term of use.
     *
     * This cannot be a foreign key, as the user may be deleted by the history
     * persists.
     *
     * @ORM\Column(type="string")
     */
    private string $user;

    /**
     * Return the action.
     */
    public function __toString(): string
    {
        return $this->action;
    }

    /**
     * Set termId.
     */
    public function setTermId(int $termId): self
    {
        $this->termId = $termId;

        return $this;
    }

    /**
     * Get termId.
     */
    public function getTermId(): int
    {
        return $this->termId;
    }

    /**
     * Set action.
     */
    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action.
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Set changeSet.
     */
    public function setChangeSet(array $changeSet): self
    {
        $this->changeSet = $changeSet;

        return $this;
    }

    /**
     * Get changeSet.
     */
    public function getChangeSet(): array
    {
        return $this->changeSet;
    }

    /**
     * Set user.
     */
    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     */
    public function getUser(): string
    {
        return $this->user;
    }
}

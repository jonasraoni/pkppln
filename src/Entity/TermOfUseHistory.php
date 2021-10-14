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
class TermOfUseHistory extends AbstractEntity {
    /**
     * A term ID, similar to the OJS translation keys.
     *
     * @var int
     *
     * @todo This is probably wrong. It shouldn't be an integer.
     *
     * @ORM\Column(type="integer")
     */
    private $termId;

    /**
     * The history action: add, updated, remove.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $action;

    /**
     * The change set, as computed by Doctrine.
     *
     * @var string
     *
     * @ORM\Column(type="array")
     */
    private $changeSet;

    /**
     * The user who added/edited/deleted the term of use.
     *
     * This cannot be a foreign key, as the user may be deleted by the history
     * persists.
     *
     * @var string
     *
     * @ORM\Column(type="string")
     */
    private $user;

    /**
     * Construct the history object.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Return the action.
     */
    public function __toString() : string {
        return $this->action;
    }

    /**
     * Set termId.
     *
     * @param int $termId
     *
     * @return TermOfUseHistory
     */
    public function setTermId($termId) {
        $this->termId = $termId;

        return $this;
    }

    /**
     * Get termId.
     *
     * @return int
     */
    public function getTermId() {
        return $this->termId;
    }

    /**
     * Set action.
     *
     * @param string $action
     *
     * @return TermOfUseHistory
     */
    public function setAction($action) {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action.
     *
     * @return string
     */
    public function getAction() {
        return $this->action;
    }

    /**
     * Set changeSet.
     *
     * @return TermOfUseHistory
     */
    public function setChangeSet(array $changeSet) {
        $this->changeSet = $changeSet;

        return $this;
    }

    /**
     * Get changeSet.
     *
     * @return array
     */
    public function getChangeSet() {
        return $this->changeSet;
    }

    /**
     * Set user.
     *
     * @param string $user
     *
     * @return TermOfUseHistory
     */
    public function setUser($user) {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return string
     */
    public function getUser() {
        return $this->user;
    }
}

<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services\Processing;

use App\Entity\Deposit;
use App\Services\SwordClient;

/**
 * Send a fully processed deposit to LOCKSSOMatic.
 *
 * @see SwordClient
 */
class Depositor {
    /**
     * Sword client to talk to LOCKSSOMatic.
     *
     * @var SwordClient
     */
    private $client;

    /**
     * Maximum OJS version or null.
     *
     * @var null|string
     */
    private $heldVersions;

    /**
     * Build the service.
     *
     * @param string $heldVersions
     */
    public function __construct(SwordClient $client, $heldVersions) {
        $this->client = $client;
        $this->heldVersions = $heldVersions;
    }

    /**
     * Process one deposit.
     *
     * @return null|bool|string
     */
    public function processDeposit(Deposit $deposit) {
        if ($this->heldVersions && version_compare($deposit->getJournalVersion(), $this->heldVersions, '>=')) {
            return 'hold';
        }

        return $this->client->createDeposit($deposit);
    }
}

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
class Depositor
{
    /**
     * Sword client to talk to LOCKSSOMatic.
     */
    private SwordClient $client;

    /**
     * Maximum supported application version or null.
     */
    private ?string $heldVersions;

    /**
     * Build the service.
     */
    public function __construct(SwordClient $client, ?string $heldVersions)
    {
        $this->client = $client;
        $this->heldVersions = $heldVersions;
    }

    /**
     * Process one deposit.
     */
    public function processDeposit(Deposit $deposit): null|bool|string
    {
        if ($this->heldVersions && version_compare($deposit->getVersion(), $this->heldVersions, '>')) {
            return 'hold';
        }

        return $this->client->createDeposit($deposit);
    }
}

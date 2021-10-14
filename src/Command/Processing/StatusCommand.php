<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Processing;

use App\Entity\Deposit;
use App\Services\SwordClient;
use Doctrine\ORM\EntityManagerInterface;

/**
 * PlnStatusCommand command.
 */
class StatusCommand extends AbstractProcessingCmd {
    /**
     * {@inheritdoc}
     */
    public function __construct(EntityManagerInterface $em, SwordClient $client) {
        parent::__construct($em);
        $this->client = $client;
    }

    /**
     * Configure the command.
     */
    protected function configure() : void {
        $this->setName('pln:status');
        $this->setDescription('Check status of deposits.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function processDeposit(Deposit $deposit) {
        $statusXml = $this->client->statement($deposit);
        $term = (string) $statusXml->xpath('//atom:category[@label="State"]/@term')[0];
        $deposit->setPlnState($term);

        return 'agreement' === $term;
    }

    /**
     * {@inheritdoc}
     */
    public function errorState() {
        return 'deposited';
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage() {
        return 'Status check with LOCKSSOMatic failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function nextState() {
        return 'complete';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState() {
        return 'deposited';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage() {
        return 'Status check with LOCKSSOMatic succeeded.';
    }
}

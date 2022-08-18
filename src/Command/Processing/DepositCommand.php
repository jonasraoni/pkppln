<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Processing;

use App\Entity\Deposit;
use App\Services\Processing\Depositor;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Send pending deposits to LOCKSS.
 */
class DepositCommand extends AbstractProcessingCmd {
    /**
     * Depositor service.
     *
     * @var Depositor
     */
    private $depositor;

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityManagerInterface $em, Depositor $depositor) {
        parent::__construct($em);
        $this->depositor = $depositor;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure() : void {
        $this->setName('pln:deposit');
        $this->setDescription('Send deposits to LockssOMatic.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function processDeposit(Deposit $deposit) {
        return $this->depositor->processDeposit($deposit);
    }

    /**
     * {@inheritdoc}
     */
    public function nextState() {
        return 'deposited';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState() {
        return 'reserialized';
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage() {
        return 'Deposit to Lockssomatic failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage() {
        return 'Deposit to Lockssomatic succeeded.';
    }

    /**
     * {@inheritdoc}
     */
    public function errorState() {
        return 'deposit-error';
    }
}

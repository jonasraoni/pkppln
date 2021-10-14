<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Processing;

use App\Entity\Deposit;
use App\Services\Processing\Harvester;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Harvest deposits from journals.
 */
class HarvestCommand extends AbstractProcessingCmd {
    /**
     * Harvester service.
     *
     * @var Harvester
     */
    private $harvester;

    /**
     * Build the command.
     */
    public function __construct(EntityManagerInterface $em, Harvester $harvester) {
        parent::__construct($em);
        $this->harvester = $harvester;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure() : void {
        $this->setName('pln:harvest');
        $this->setDescription('Harvest OJS deposits.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function processDeposit(Deposit $deposit) {
        return $this->harvester->processDeposit($deposit);
    }

    /**
     * {@inheritdoc}
     */
    public function nextState() {
        return 'harvested';
    }

    /**
     * {@inheritdoc}
     */
    public function errorState() {
        return 'harvest-error';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState() {
        return 'depositedByJournal';
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage() {
        return 'Deposit harvest failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage() {
        return 'Deposit harvest succeeded.';
    }
}

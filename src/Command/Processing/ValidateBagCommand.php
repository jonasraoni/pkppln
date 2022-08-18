<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Processing;

use App\Entity\Deposit;
use App\Services\Processing\BagValidator;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Validate a bag metadata and checksums.
 */
class ValidateBagCommand extends AbstractProcessingCmd {
    /**
     * Bag validator service.
     *
     * @var BagValidator
     */
    private $bagValidator;

    /**
     * Build the command.
     */
    public function __construct(EntityManagerInterface $em, BagValidator $bagValidator) {
        parent::__construct($em);
        $this->bagValidator = $bagValidator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure() : void {
        $this->setName('pln:validate:bag');
        $this->setDescription('Validate PLN deposit packages.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function processDeposit(Deposit $deposit) {
        return $this->bagValidator->processDeposit($deposit);
    }

    /**
     * {@inheritdoc}
     */
    public function nextState() {
        return 'bag-validated';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState() {
        return 'payload-validated';
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage() {
        return 'Bag checksum validation failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage() {
        return 'Bag checksum validation succeeded.';
    }

    /**
     * {@inheritdoc}
     */
    public function errorState() {
        return 'bag-error';
    }
}

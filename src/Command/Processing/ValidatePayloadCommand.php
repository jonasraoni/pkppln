<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Processing;

use App\Entity\Deposit;
use App\Services\Processing\PayloadValidator;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Validate the payload checksum.
 */
class ValidatePayloadCommand extends AbstractProcessingCmd {
    /**
     * Payload validator service.
     *
     * @var PayloadValidator
     */
    private $payloadValidator;

    /**
     * Build the command.
     */
    public function __construct(EntityManagerInterface $em, PayloadValidator $payloadValidator) {
        parent::__construct($em);
        $this->payloadValidator = $payloadValidator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure() : void {
        $this->setName('pln:validate:payload');
        $this->setDescription('Validate PLN deposit packages.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function processDeposit(Deposit $deposit) {
        return $this->payloadValidator->processDeposit($deposit);
    }

    /**
     * {@inheritdoc}
     */
    public function nextState() {
        return 'payload-validated';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState() {
        return 'harvested';
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage() {
        return 'Payload checksum validation failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage() {
        return 'Payload checksum validation succeeded.';
    }

    /**
     * {@inheritdoc}
     */
    public function errorState() {
        return 'payload-error';
    }
}

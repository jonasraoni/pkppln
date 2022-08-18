<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Processing;

use App\Entity\Deposit;
use App\Services\Processing\XmlValidator;
use App\Services\SchemaValidator;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Validate XML in a deposit.
 */
class ValidateXmlCommand extends AbstractProcessingCmd {
    /**
     * XML validator service.
     *
     * @var XmlValidator
     */
    private $xmlValidator;

    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

    /**
     * Build the command.
     */
    public function __construct(EntityManagerInterface $em, XmlValidator $xmlValidator) {
        parent::__construct($em);
        $this->xmlValidator = $xmlValidator;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure() : void {
        $this->setName('pln:validate:xml');
        $this->setDescription('Validate OJS XML export files.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function processDeposit(Deposit $deposit) {
        return $this->xmlValidator->processDeposit($deposit);
    }

    /**
     * {@inheritdoc}
     */
    public function nextState() {
        return 'xml-validated';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState() {
        return 'bag-validated';
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage() {
        return 'XML Validation failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage() {
        return 'XML validation succeeded.';
    }

    /**
     * {@inheritdoc}
     */
    public function errorState() {
        return 'xml-error';
    }
}

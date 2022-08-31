<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Processing;

use App\Entity\Deposit;
use App\Services\Processing\VirusScanner;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Scan a deposit for viruses.
 */
class ScanCommand extends AbstractProcessingCmd {
    /**
     * Virus scanning service.
     */
    private VirusScanner $scanner;

    /**
     * Build the command.
     */
    public function __construct(EntityManagerInterface $em, VirusScanner $scanner) {
        parent::__construct($em);
        $this->scanner = $scanner;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure() : void {
        $this->setName('pln:scan');
        $this->setDescription('Scan deposit packages for viruses.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function processDeposit(Deposit $deposit): null|bool|string {
        return $this->scanner->processDeposit($deposit);
    }

    /**
     * {@inheritdoc}
     */
    public function errorState(): string {
        return 'virus-error';
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage(): string {
        return 'Virus check failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function nextState(): string {
        return 'virus-checked';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState(): string {
        return 'xml-validated';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage(): string {
        return 'Virus check passed. No infections found.';
    }
}

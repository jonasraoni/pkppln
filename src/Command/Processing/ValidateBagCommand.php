<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Processing;

use App\Entity\Deposit;
use App\Services\FilePaths;
use App\Services\Processing\BagValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Validate a bag metadata and checksums.
 */
class ValidateBagCommand extends AbstractProcessingCmd
{
    private BagValidator $bagValidator;
    private FilePaths $filePaths;

    /**
     * Build the command.
     */
    public function __construct(EntityManagerInterface $em, BagValidator $bagValidator, FilePaths $filePaths)
    {
        parent::__construct($em);
        $this->bagValidator = $bagValidator;
        $this->filePaths = $filePaths;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('pln:validate:bag');
        $this->setDescription('Validate PLN deposit packages.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function processDeposit(Deposit $deposit): null|bool|string
    {
        return $this->bagValidator->processDeposit($deposit);
    }

    /**
     * {@inheritdoc}
     */
    protected function afterFailure(Deposit $deposit): void
    {
        $extractedPath = $this->filePaths->getProcessingBagPath($deposit);
        $fs = new Filesystem();
        if ($fs->exists($extractedPath)) {
            $this->logger?->info("Removing failed bag files {$extractedPath}.");
            $fs->remove($extractedPath);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function afterSuccess(Deposit $deposit): void
    {
        $harvestedPath = $this->filePaths->getHarvestFile($deposit);
        $fs = new Filesystem();
        if ($fs->exists($harvestedPath)) {
            $this->logger?->info("The bag is validated, removing initial harvested file {$harvestedPath}.");
            $fs->remove($harvestedPath);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function nextState(): string
    {
        return 'bag-validated';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState(): string
    {
        return 'payload-validated';
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage(): string
    {
        return 'Bag checksum validation failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage(): string
    {
        return 'Bag checksum validation succeeded.';
    }

    /**
     * {@inheritdoc}
     */
    public function errorState(): string
    {
        return 'bag-error';
    }
}

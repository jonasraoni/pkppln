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
use App\Services\Processing\BagReserializer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Reserialize the bags and add some metadata.
 */
class ReserializeCommand extends AbstractProcessingCmd {
    private BagReserializer $bagReserializer;
    private FilePaths $filePaths;

    /**
     * Build the command.
     */
    public function __construct(EntityManagerInterface $em, BagReserializer $bagReserializer, FilePaths $filePaths) {
        parent::__construct($em);
        $this->bagReserializer = $bagReserializer;
        $this->filePaths = $filePaths;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure() : void {
        $this->setName('pln:reserialize');
        $this->setDescription('Reserialize the deposit bag.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function processDeposit(Deposit $deposit): null|bool|string {
        return $this->bagReserializer->processDeposit($deposit);
    }

    /**
     * {@inheritdoc}
     */
    protected function afterSuccess(Deposit $deposit): void {
        $extractedPath = $this->filePaths->getProcessingBagPath($deposit);
        $fs = new Filesystem();
        if ($fs->exists($extractedPath)) {
            $this->logger->info("Bag was reserialized, removing extracted bag files {$extractedPath}.");
            $fs->remove($extractedPath);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage(): string {
        return 'Bag Reserialize failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function nextState(): string {
        return 'reserialized';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState(): string {
        return 'virus-checked';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage(): string {
        return 'Bag Reserialize succeeded.';
    }

    /**
     * {@inheritdoc}
     */
    public function errorState(): string {
        return 'reserialize-error';
    }
}

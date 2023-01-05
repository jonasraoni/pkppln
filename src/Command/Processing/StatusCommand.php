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
use App\Services\SwordClient;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Filesystem\Filesystem;

/**
 * StatusCommand command.
 */
class StatusCommand extends AbstractProcessingCmd
{
    /**
     * If true, completed deposits will be removed from disk.
     */
    private bool $cleanup;
    private FilePaths $filePaths;
    private SwordClient $client;

    /**
     * {@inheritdoc}
     */
    public function __construct(EntityManagerInterface $em, SwordClient $client, FilePaths $filePaths, bool $cleanup)
    {
        parent::__construct($em);
        $this->client = $client;
        $this->filePaths = $filePaths;
        $this->cleanup = $cleanup;
    }

    /**
     * Configure the command.
     */
    protected function configure(): void
    {
        $this->setName('pn:status');
        $this->setDescription('Check status of deposits.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function processDeposit(Deposit $deposit): null|bool|string
    {
        $termNode = $this->client->statement($deposit)->xpath('//atom:category[@label="State"]/@term');
        \assert(is_iterable($termNode));
        $term = (string) ($termNode[0] ?? null) ?: throw new Exception('Failed to retrieve term');
        $deposit->setLockssState($term);

        return 'agreement' === $term;
    }

    /**
     * {@inheritdoc}
     */
    protected function afterSuccess(Deposit $deposit): void
    {
        if (!$this->cleanup) {
            return;
        }

        $this->logger?->notice("Deposit complete. Removing processing files for deposit {$deposit->getId()}.");

        // Clear outdated files
        $fs = new Filesystem();
        $paths = [
            $this->filePaths->getHarvestFile($deposit),
            $this->filePaths->getProcessingBagPath($deposit),
            $this->filePaths->getStagingBagPath($deposit)
        ];
        foreach ($paths as $path) {
            try {
                $fs->remove($path);
            } catch (Exception $e) {
                $this->logger?->error($e->getMessage());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function errorState(): string
    {
        return 'deposited';
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage(): string
    {
        return 'Status check with LOCKSSOMatic failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function nextState(): string
    {
        return 'complete';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState(): string
    {
        return 'deposited';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage(): string
    {
        return 'Status check with LOCKSSOMatic succeeded.';
    }
}

<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use App\Entity\Deposit;
use App\Entity\Journal;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Calculate file paths.
 */
class FilePaths {
    /**
     * Base directory where the files are stored.
     */
    private string $root;

    /**
     * Symfony filesystem object.
     */
    private FileSystem $fs;

    /**
     * Build the service.
     *
     * If $root is a relative directory, the service will construct paths
     * relative to the symfony install director, inside $root.
     */
    public function __construct(string $root, string $projectDir, FileSystem $fs = null) {
        if ($root && '/' !== $root[0]) {
            $this->root = $projectDir . '/' . $root;
        } else {
            $this->root = $root;
        }
        $this->fs = $fs ?? new Filesystem();
    }

    /**
     * Get the root file system path.
     */
    public function getRootPath(): string {
        return $this->root;
    }

    /**
     * Get the directory where a journal's deposits should be saved from LOCKSS.
     */
    public function getRestoreDir(Journal $journal): string {
        $path = implode('/', [
            $this->getRootPath(),
            'restore',
            $journal->getUuid(),
        ]);
        if ( ! $this->fs->exists($path)) {
            $this->fs->mkdir($path);
        }

        return $path;
    }

    /**
     * Get the path to save a deposit from LOCKSS.
     */
    public function getRestoreFile(Deposit $deposit): string {
        return implode('/', [
            $this->getRestoreDir($deposit->getJournal()),
            $deposit->getDepositUuid() . '.zip',
        ]);
    }

    /**
     * Get the harvest directory.
     */
    public function getHarvestDir(Journal $journal): string {
        $path = implode('/', [
            $this->getRootPath(),
            'harvest',
            $journal->getUuid(),
        ]);
        if ( ! $this->fs->exists($path)) {
            $this->fs->mkdir($path);
        }

        return $path;
    }

    /**
     * Get the path to a harvested deposit.
     */
    public function getHarvestFile(Deposit $deposit): string {
        return implode('/', [
            $this->getHarvestDir($deposit->getJournal()),
            $deposit->getDepositUuid() . '.zip',
        ]);
    }

    /**
     * Get the processing directory.
     */
    public function getProcessingDir(Journal $journal): string {
        $path = implode('/', [
            $this->getRootPath(),
            'processing',
            $journal->getUuid(),
        ]);
        if ( ! $this->fs->exists($path)) {
            $this->fs->mkdir($path);
        }

        return $path;
    }

    /**
     * Get the path to a deposit bag being processed.
     */
    public function getProcessingBagPath(Deposit $deposit): string {
        $path = implode('/', [
            $this->getProcessingDir($deposit->getJournal()),
            $deposit->getDepositUuid(),
        ]);
        if ( ! $this->fs->exists($path)) {
            $this->fs->mkdir($path);
        }

        return $path;
    }

    /**
     * Get the staging directory for processed deposits.
     */
    public function getStagingDir(Journal $journal): string {
        $path = implode('/', [
            $this->getRootPath(),
            'staged',
            $journal->getUuid(),
        ]);
        if ( ! $this->fs->exists($path)) {
            $this->fs->mkdir($path);
        }

        return $path;
    }

    /**
     * Get the path to a processed, staged, bag.
     */
    public function getStagingBagPath(Deposit $deposit): string {
        $path = $this->getStagingDir($deposit->getJournal());

        return $path . '/' . $deposit->getDepositUuid() . '.zip';
    }

    /**
     * Get the path to the onix feed file.
     */
    public function getOnixPath(): string {
        return $this->root . '/onix.xml';
    }
}

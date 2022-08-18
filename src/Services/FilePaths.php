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
     *
     * @var string
     */
    private $root;

    /**
     * Symfony filesystem object.
     *
     * @var FileSystem
     */
    private $fs;

    /**
     * Build the service.
     *
     * If $root is a relative directory, the service will construct paths
     * relative to the symfony install director, inside $root.
     *
     * @param string $root
     * @param string $projectDir
     * @param FileSystem $fs
     */
    public function __construct($root, $projectDir, FileSystem $fs = null) {
        if ($root && '/' !== $root[0]) {
            $this->root = $projectDir . '/' . $root;
        } else {
            $this->root = $root;
        }
        if ($fs) {
            $this->fs = $fs;
        } else {
            $this->fs = new Filesystem();
        }
    }

    /**
     * Get the root file system path.
     *
     * @return string
     */
    public function getRootPath() {
        return $this->root;
    }

    /**
     * Get the directory where a journal's deposits should be saved from LOCKSS.
     *
     * @return string
     */
    public function getRestoreDir(Journal $journal) {
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
     *
     * @return string
     */
    public function getRestoreFile(Deposit $deposit) {
        return implode('/', [
            $this->getRestoreDir($deposit->getJournal()),
            $deposit->getDepositUuid() . '.zip',
        ]);
    }

    /**
     * Get the harvest directory.
     *
     * @return string
     */
    public function getHarvestDir(Journal $journal) {
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
     *
     * @return mixed
     */
    public function getHarvestFile(Deposit $deposit) {
        return implode('/', [
            $this->getHarvestDir($deposit->getJournal()),
            $deposit->getDepositUuid() . '.zip',
        ]);
    }

    /**
     * Get the processing directory.
     *
     * @return string
     */
    public function getProcessingDir(Journal $journal) {
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
     *
     * @return mixed
     */
    public function getProcessingBagPath(Deposit $deposit) {
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
     *
     * @return string
     */
    public function getStagingDir(Journal $journal) {
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
     *
     * @return mixed
     */
    public function getStagingBagPath(Deposit $deposit) {
        $path = $this->getStagingDir($deposit->getJournal());

        return $path . '/' . $deposit->getDepositUuid() . '.zip';
    }

    /**
     * Get the path to the onix feed file.
     *
     * @return string
     */
    public function getOnixPath() {
        return $this->root . '/onix.xml';
    }
}

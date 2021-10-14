<?php

/*
 * Copyright (C) 2015-2016 Michael Joyce <ubermichael@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Journal;
use Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Calculate file paths.
 */
class FilePaths
{
    /**
     * Base directory where the files are stored.
     *
     * @var string
     */
    private $baseDir;

    /**
     * Symfony filesystem object.
     *
     * @var FileSystem
     */
    private $fs;

    /**
     * Kernel environment, a path on the file system.
     *
     * @var string
     */
    private $env;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Build the service.
     */
    public function __construct()
    {
        $this->fs = new Filesystem();
    }

    /**
     * Set the service logger.
     *
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set the kernel environment.
     *
     * @param string $env
     */
    public function setKernelEnv($env)
    {
        $this->env = $env;
    }

    public function getBaseDir()
    {
        return $this->baseDir;
    }

    /**
     * Set the file system base directory.
     *
     * @param type $dir
     */
    public function setBaseDir($dir)
    {
        if (substr($dir, -1) !== '/') {
            $this->baseDir = $dir.'/';
        } else {
            $this->baseDir = $dir;
        }
    }

    /**
     * Get the root dir, based on the baseDir.
     *
     * @return string
     */
    public function rootPath($mkdir = true)
    {
        $path = $this->baseDir;
        if (!$this->fs->isAbsolutePath($path)) {
            $root = dirname($this->env);
            $path = $root.'/'.$path;
        }
        if (!$this->fs->exists($path) && $mkdir) {
            $this->fs->mkdir($path);
        }

        return realpath($path);
    }

    /**
     * Get an absolute path to a processing directory for the journal.
     *
     * @param string  $dirname
     * @param Journal $journal
     *
     * @return string
     */
    protected function absolutePath($dirname, Journal $journal = null)
    {
        $path = $this->rootPath().'/'.$dirname;
        if (substr($dirname, -1) !== '/') {
            $path .= '/';
        }
        if (!$this->fs->exists($path)) {
            $this->fs->mkdir($path);
        }
        if ($journal !== null) {
            return  $path.$journal->getUuid();
        }

        return realpath($path);
    }

    public function getRestoreDir(Journal $journal)
    {
        $path = $this->absolutePath('restore', $journal);
        if (!$this->fs->exists($path)) {
            $this->logger->notice("Creating directory {$path}");
            $this->fs->mkdir($path);
        }

        return $path;
    }

    /**
     * Get the harvest directory.
     *
     * @see AppKernel#getRootDir
     *
     * @param Journal $journal
     *
     * @return string
     */
    final public function getHarvestDir(Journal $journal = null)
    {
        $path = $this->absolutePath('received', $journal);
        if (!$this->fs->exists($path)) {
            $this->logger->notice("Creating directory {$path}");
            $this->fs->mkdir($path);
        }

        return $path;
    }

    /**
     * Get the path to a harvested deposit.
     *
     * @param Deposit $deposit
     *
     * @return type
     */
    final public function getHarvestFile(Deposit $deposit)
    {
        $path = $this->getHarvestDir($deposit->getJournal());

        return $path.'/'.$deposit->getFileName();
    }

    /**
     * Get the processing directory.
     *
     * @param Journal $journal
     *
     * @return string
     */
    final public function getProcessingDir(Journal $journal)
    {
        $path = $this->absolutePath('processing', $journal);
        if (!$this->fs->exists($path)) {
            $this->logger->notice("Creating directory {$path}");
            $this->fs->mkdir($path);
        }

        return $path;
    }

    /**
     * Get the path to a deposit bag being processed.
     *
     * @param Deposit $deposit
     *
     * @return string
     */
    public function getProcessingBagPath(Deposit $deposit)
    {
        $path = $this->getProcessingDir($deposit->getJournal());

        return $path.'/'.$deposit->getDepositUuid();
    }

    /**
     * Get the staging directory for processed deposits.
     *
     * @param Journal $journal
     *
     * @return string
     */
    final public function getStagingDir(Journal $journal)
    {
        $path = $this->absolutePath('staged', $journal);
        if (!$this->fs->exists($path)) {
            $this->logger->notice("Creating directory {$path}");
            $this->fs->mkdir($path);
        }

        return $path;
    }

    /**
     * Get the path to a processed, staged, bag.
     *
     * @param Deposit $deposit
     *
     * @return type
     */
    final public function getStagingBagPath(Deposit $deposit)
    {
        $path = $this->getStagingDir($deposit->getJournal());

        return $path.'/'.$deposit->getDepositUuid().'.zip';
    }

    /**
     * Get the path to the onix feed file.
     *
     * @param string $_format
     *
     * @return string
     */
    public function getOnixPath($_format = 'xml')
    {
        return $this->rootPath().'/onix.'.$_format;
    }
}

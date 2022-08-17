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

namespace AppBundle\Command\Processing;

use AppBundle\Entity\Deposit;
use AppBundle\Services\FilePaths;
use AppBundle\Services\SwordClient;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check the status of deposits in LOCKSSOMatic.
 *
 * @see SwordClient
 */
class StatusCommand extends AbstractProcessingCmd
{
    /**
     * @var SwordClient
     */
    private $client;

    /**
     * @var bool
     */
    private $cleanup;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null)
    {
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:status');
        $this->setDescription('Check the status of deposits in LOCKSSOMatic.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->cleanup = $this->container->getParameter('remove_complete_deposits');
        $this->client = $container->get('sword_client');
        $this->client->setLogger($this->logger);
    }

    /**
     * Remove a directory and its contents recursively. Use with caution.
     */
    private function delTree($path)
    {
        $directoryIterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $fileIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($fileIterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($path);
    }

    /**
     * Process one deposit. Fetch the data and write it to the file system.
     * Updates the deposit status, and may remove the processing files if
     * LOCKSSOatic reports agreement.
     *
     * @param Deposit $deposit
     *
     * @return boolean|null
     */
    protected function processDeposit(Deposit $deposit)
    {
        $this->logger->notice("Checking deposit {$deposit->getDepositUuid()}");
        $statement = $this->client->statement($deposit);
        $status = (string) $statement->xpath('//atom:category[@scheme="http://purl.org/net/sword/terms/state"]/@term')[0];
        $this->logger->notice('Deposit is '.$status);
        $deposit->setPlnState($status);

        if($status === 'agreement') {
            return true;
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function afterSuccess(Deposit $deposit)
    {
        if ($this->cleanup) {
            $this->logger->notice("Deposit complete. Removing processing files for deposit {$deposit->getId()}.");
            $harvestFile = $this->filePaths->getHarvestFile($deposit);
            if (file_exists($harvestFile)) {
                unlink($harvestFile);
            }
            $processingFolder = $this->filePaths->getProcessingBagPath($deposit);
            if (is_dir($processingFolder)) {
                $this->deltree($processingFolder);
            }
            $stagingFile = $this->filePaths->getStagingBagPath($deposit);
            if (file_exists($stagingFile)) {
                unlink($stagingFile);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function nextState()
    {
        return 'complete';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState()
    {
        return 'deposited';
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage()
    {
        return 'Deposit status failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage()
    {
        return 'Deposit status succeeded.';
    }

    /**
     * {@inheritdoc}
     */
    public function errorState()
    {
        return 'status-error';
    }
}

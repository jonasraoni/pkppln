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

// sigh. Something isn't autoloading here.
require_once 'vendor/scholarslab/bagit/lib/bagit.php';

use AppBundle\Entity\Deposit;
use BagIt;
use Exception;
use ZipArchive;

/**
 * Validate a bag, according to the bagit spec.
 */
class ValidateBagCommand extends AbstractProcessingCmd
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:validate-bag');
        $this->setDescription('Validate PLN deposit packages.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function processDeposit(Deposit $deposit)
    {
        $harvestedPath = $this->filePaths->getHarvestFile($deposit);
        $extractedPath = $this->filePaths->getProcessingBagPath($deposit);
        $this->logger->info("Processing {$harvestedPath}");

        if (!$this->fs->exists($harvestedPath)) {
            throw new Exception("Deposit file {$harvestedPath} does not exist");
        }

        $zipFile = new ZipArchive();
        if ($zipFile->open($harvestedPath) === false) {
            throw new Exception("Cannot open {$harvestedPath}: ".$zipFile->getStatusString());
        }

        $this->logger->info("Extracting to {$extractedPath}");

        if (file_exists($extractedPath)) {
            $this->logger->warning("{$extractedPath} is not empty. Removing it.");
            $this->fs->remove($extractedPath);
        }
        // dirname() is neccessary here - extractTo will create one layer too many
        // directories otherwise.
        if ($zipFile->extractTo(dirname($extractedPath)) === false) {
            throw new Exception("Cannot extract to {$extractedPath} ".$zipFile->getStatusString());
        }
        $this->logger->info("Validating {$extractedPath}");

        $bag = new BagIt($extractedPath);
        $bag->validate();

        if (count($bag->getBagErrors()) > 0) {
            foreach ($bag->getBagErrors() as $error) {
                $deposit->addErrorLog("Bagit validation error for {$error[0]} - {$error[1]}");
            }
            $this->logger->warning("BagIt validation failed for {$deposit->getDepositUuid()}");

            return false;
        }
        $journalVersion = $bag->getBagInfoData('PKP-PLN-OJS-Version');
        if($journalVersion && $journalVersion !== $deposit->getJournalVersion()) {
            $this->logger->warning("Bag journal version tag {$journalVersion} does not match deposit journal version {$deposit->getJournalVersion()}");
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function afterFailure(Deposit $deposit) {
        $extractedPath = $this->filePaths->getProcessingBagPath($deposit);
        if (file_exists($extractedPath)) {
            $this->logger->info("Removing failed bag files {$extractedPath}.");
            $this->fs->remove($extractedPath);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function afterSuccess(Deposit $deposit)
    {
        $harvestedPath = $this->filePaths->getHarvestFile($deposit);
        if (file_exists($harvestedPath)) {
            $this->logger->info("The bag is validated, removing initial harvested file {$harvestedPath}.");
            $this->fs->remove($harvestedPath);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function nextState()
    {
        return 'bag-validated';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState()
    {
        return 'payload-validated';
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage()
    {
        return 'Bag checksum validation failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage()
    {
        return 'Bag checksum validation succeeded.';
    }

    /**
     * {@inheritdoc}
     */
    public function errorState()
    {
        return 'bag-error';
    }
}

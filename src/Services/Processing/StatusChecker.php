<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services\Processing;

use App\Entity\Deposit;
use App\Services\SwordClient;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Check the status of deposits in LOCKSSOMatic.
 *
 * @see SwordClient
 */
class StatusChecker {
    /**
     * Sword client to communicate with LOCKSS.
     *
     * @var SwordClient
     */
    private $client;

    /**
     * If true, completed deposits will be removed from disk.
     *
     * @var bool
     */
    private $cleanup;

    /**
     * Construct the status checker.
     *
     * @param bool $cleanup
     */
    public function __construct(SwordClient $client, $cleanup) {
        $this->cleanup = $cleanup;
        $this->client = $client;
    }

    /**
     * Remove a directory and its contents recursively.
     *
     * Use with caution.
     *
     * @param mixed $path
     */
    private function delTree($path) : void {
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
     * Process one deposit.
     *
     * Updates the deposit status, and may remove the processing files if
     * LOCKSSOatic reports agreement.
     *
     * @return null|bool
     */
    protected function processDeposit(Deposit $deposit) {
        $this->logger->notice("Checking deposit {$deposit->getDepositUuid()}");
        $statement = $this->client->statement($deposit);
        $status = (string) $statement->xpath('//atom:category[@scheme="http://purl.org/net/sword/terms/state"]/@term')[0];
        $this->logger->notice('Deposit is ' . $status);
        $deposit->setPlnState($status);
        if ('agreement' === $status && $this->cleanup) {
            $this->logger->notice("Deposit complete. Removing processing files for deposit {$deposit->getId()}.");
            unlink($this->filePaths->getHarvestFile($deposit));
            $this->deltree($this->filePaths->getProcessingBagPath($deposit));
            unlink($this->filePaths->getStagingBagPath($deposit));
        }

        if ('agreement' === $status) {
            return true;
        }
    }
}

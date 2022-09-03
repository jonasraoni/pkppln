<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services\Processing;

use App\Entity\Deposit;
use App\Services\FilePaths;
use Exception;

/**
 * Validate the size and checksum of a downloaded deposit.
 */
class PayloadValidator
{
    /**
     * Buffer size for the hashing.
     */
    public const BUFFER_SIZE = 64 * 1024;

    /**
     * File path service.
     */
    private FilePaths $fp;

    /**
     * Construct the validator.
     */
    public function __construct(FilePaths $fp)
    {
        $this->fp = $fp;
    }

    /**
     * Override the file path service.
     */
    public function setFilePaths(FilePaths $filePaths): void
    {
        $this->fp = $filePaths;
    }

    /**
     * Hash a file.
     *
     * @throws Exception
     *                   If the algorithm is unknown.
     */
    public function hashFile(string $algorithm, string $filepath): string
    {
        $handle = fopen($filepath, 'r');
        $context = match (strtolower($algorithm)) {
            'sha1', 'sha-1' => hash_init('sha1'),
            'md5' => hash_init('md5'),
            default => throw new Exception("Unknown hash algorithm {$algorithm}")
        };
        while (($data = fread($handle, self::BUFFER_SIZE))) {
            hash_update($context, $data);
        }
        $hash = hash_final($context);
        fclose($handle);

        return strtoupper($hash);
    }

    /**
     * Process one deposit.
     */
    public function processDeposit(Deposit $deposit): bool
    {
        try {
            $depositPath = $this->fp->getHarvestFile($deposit);
            $checksumValue = $this->hashFile($deposit->getChecksumType(), $depositPath);
            if ($checksumValue !== $deposit->getChecksumValue()) {
                throw new Exception('Deposit checksum does not match. '
                        . "Expected {$deposit->getChecksumValue()} != "
                        . "Actual {$checksumValue}");
            }

            return true;
        } catch (Exception $e) {
            $deposit->addToProcessingLog($e->getMessage());

            return false;
        }
    }
}

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
class PayloadValidator {
    /**
     * Buffer size for the hashing.
     */
    public const BUFFER_SIZE = 64 * 1024;

    /**
     * File path service.
     *
     * @var FilePaths
     */
    private $fp;

    /**
     * Construct the validator.
     */
    public function __construct(FilePaths $fp) {
        $this->fp = $fp;
    }

    /**
     * Override the file path service.
     */
    public function setFilePaths(FilePaths $filePaths) : void {
        $this->fp = $filePaths;
    }

    /**
     * Hash a file.
     *
     * @param string $algorithm
     * @param string $filepath
     *
     * @throws Exception
     *                   If the algorithm is unknown.
     *
     * @return string
     */
    public function hashFile($algorithm, $filepath) {
        $handle = fopen($filepath, 'r');
        $context = null;
        switch (strtolower($algorithm)) {
            case 'sha-1':
            case 'sha1':
                $context = hash_init('sha1');

                break;
            case 'md5':
                $context = hash_init('md5');

                break;
            default:
                throw new Exception("Unknown hash algorithm {$algorithm}");
        }
        while (($data = fread($handle, self::BUFFER_SIZE))) {
            hash_update($context, $data);
        }
        $hash = hash_final($context);
        fclose($handle);

        return strtoupper($hash);
    }

    /**
     * Process one deposit.
     *
     * @return bool
     */
    public function processDeposit(Deposit $deposit) {
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

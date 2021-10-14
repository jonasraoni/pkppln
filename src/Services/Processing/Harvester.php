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
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Harvest a deposit from a journal.
 *
 * Attempts to check file sizes via HTTP HEAD before downloading, and checks
 * that there will be sufficient disk space.
 */
class Harvester {
    /**
     * Configuration for the harvester client.
     */
    public const CONF = [
        'allow_redirects' => true,
        'headers' => [
            'User-Agent' => 'PkpPlnBot 1.0; http://pkp.sfu.ca',
        ],
        'decode_content' => false,
    ];

    /**
     * Write files in 64kb chunks.
     */
    public const BUFFER_SIZE = 64 * 1024;

    /**
     * File size difference threshold.
     *
     * Deposit files with sizes that differ from the reported size in the SWORD
     * deposit will be considered fails.
     */
    public const FILE_SIZE_THRESHOLD = 0.08;

    /**
     * HTTP Client.
     *
     * @var Client
     */
    private $client;

    /**
     * Filesystem interface.
     *
     * @var Filesystem
     */
    private $fs;

    /**
     * Maximum number of harvest attempts before giving up.
     *
     * @var int
     */
    private $maxAttempts;

    /**
     * File path service.
     *
     * @var FilePaths
     */
    private $filePaths;

    /**
     * Construct the harvester.
     *
     * @param int $maxHarvestAttempts
     */
    public function __construct($maxHarvestAttempts, FilePaths $filePaths) {
        $this->maxAttempts = $maxHarvestAttempts;
        $this->filePaths = $filePaths;
        $this->fs = new Filesystem();
        $this->client = new Client(self::CONF);
    }

    /**
     * Override the HTTP client, usually based on Guzzle.
     */
    public function setClient(Client $client) : void {
        $this->client = $client;
    }

    /**
     * Override the file system client.
     */
    public function setFilesystem(Filesystem $fs) : void {
        $this->fs = $fs;
    }

    /**
     * Write a deposit's data to the filesystem at $path.
     *
     * Returns true on success and false on failure.
     *
     * @param string $path
     *
     * @return bool
     */
    public function writeDeposit($path, ResponseInterface $response) {
        $body = $response->getBody();
        if ( ! $body) {
            throw new Exception('Response body was empty.');
        }
        if ($this->fs->exists($path)) {
            $this->fs->remove($path);
        }
        // 64k chunks. Can't read/write the entire thing at once.
        while ($bytes = $body->read(self::BUFFER_SIZE)) {
            $this->fs->appendToFile($path, $bytes);
        }

        return true;
    }

    /**
     * Fetch a deposit URL with Guzzle.
     *
     * @param string $url
     *
     * @throws Exception
     *                   If the HTTP status code isn't 200, throw an error.
     *
     * @return Response
     */
    public function fetchDeposit($url) {
        $response = $this->client->get($url);
        if (200 !== $response->getStatusCode()) {
            throw new Exception('Harvest download error '
                    . "- {$url} - HTTP {$response->getHttpStatus()} "
                    . "- {$response->getError()}");
        }

        return $response;
    }

    /**
     * Do an HTTP HEAD to get the deposit download size.
     *
     * @throws Exception
     *                   If the HEAD request status code isn't 200, throw an exception.
     */
    public function checkSize(Deposit $deposit) : void {
        $response = $this->client->head($deposit->getUrl());
        if (200 !== $response->getStatusCode() || ! $response->hasHeader('Content-Length')) {
            throw new Exception('HTTP HEAD request cannot check file size: '
                    . "HTTP {$response->getStatusCode()} - {$response->getReasonPhrase()} "
                    . "- {$deposit->getUrl()}");
        }
        $values = $response->getHeader('Content-Length');
        $reported = (int) $values[0];
        if (0 === $reported) {
            throw new Exception('HTTP HEAD response does not include file size: '
                    . "HTTP {$response->getStatusCode()} - {$response->getReasonPhrase()} "
                    . "- {$deposit->getUrl()}");
        }
        $expected = $deposit->getSize() * 1000;
        $difference = abs($reported - $expected) / (($reported + $expected) / 2.0);
        if ($difference > self::FILE_SIZE_THRESHOLD) {
            throw new Exception("Expected file size {$expected} is not close to "
            . "reported size {$reported}");
        }
    }

    /**
     * Process one deposit.
     *
     * Fetch the data and write it to the file system.
     *
     * @return bool
     */
    public function processDeposit(Deposit $deposit) {
        if ($deposit->getHarvestAttempts() > $this->maxAttempts) {
            $deposit->setState('harvest-error');

            return false;
        }

        try {
            $deposit->setHarvestAttempts($deposit->getHarvestAttempts() + 1);
            $this->checkSize($deposit);
            $response = $this->fetchDeposit($deposit->getUrl(), $deposit->getSize());
            $deposit->setFileType($response->getHeaderLine('Content-Type'));
            $filePath = $this->filePaths->getHarvestFile($deposit);

            return $this->writeDeposit($filePath, $response);
        } catch (Exception $e) {
            $deposit->addToProcessingLog($e->getMessage());

            return;
        }
    }
}

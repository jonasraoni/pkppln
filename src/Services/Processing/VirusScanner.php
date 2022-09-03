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
use App\Utilities\XmlParser;
use DOMElement;
use DOMXPath;
use PharData;
use RecursiveIteratorIterator;
use Socket\Raw\Factory;
use Xenolope\Quahog\Client;
use Xenolope\Quahog\Result;

/**
 * Virus scanning service, via ClamAV.
 */
class VirusScanner
{
    /**
     * Buffer size for extracting embedded files.
     */
    public const BUFFER_SIZE = 64 * 1024;

    private FilePaths $filePaths;
    private int $bufferSize;

    /**
     * Path to the ClamAV socket.
     */
    private string $socketPath;

    /**
     * Socket factory, for use with the Quahog ClamAV interface.
     */
    private Factory $factory;

    /**
     * Construct the virus scanner.
     */
    public function __construct(string $socketPath, FilePaths $filePaths)
    {
        $this->filePaths = $filePaths;
        $this->socketPath = $socketPath;
        $this->bufferSize = self::BUFFER_SIZE;
        $this->factory = new Factory();
    }

    /**
     * Set the socket factory.
     */
    public function setFactory(Factory $factory): void
    {
        $this->factory = $factory;
    }

    /**
     * Get the Quahog client.
     *
     * The client can't be instantiated in the constructor. If the socket path
     * isn't configured or if the socket isn't set up yet the entire app will
     * fail. Symfony tries to instantiate all services for each request, and if
     * one constructor throws an exception everything gets cranky.
     */
    public function getClient(): Client
    {
        $socket = $this->factory->createClient('unix://' . $this->socketPath);
        $client = new Client($socket, 30, \PHP_NORMAL_READ);
        $client->startSession();

        return $client;
    }

    /**
     * Scan an embedded file.
     */
    public function scanEmbed(DOMElement $embed, DOMXpath $xp, Client $client): Result
    {
        $length = $xp->evaluate('string-length(./text())', $embed);
        // Xpath starts at 1.
        $offset = 1;
        $handle = fopen('php://temp', 'w+');
        while ($offset < $length) {
            $end = $offset + $this->bufferSize;
            $chunk = $xp->evaluate("substring(./text(), {$offset}, {$this->bufferSize})", $embed);
            $data = base64_decode($chunk, true);
            fwrite($handle, $data);
            $offset = $end;
        }
        rewind($handle);

        return $client->scanResourceStream($handle);
    }

    /**
     * Scan an XML file and it's embedded content.
     *
     * @return string[]
     */
    public function scanXmlFile(string $pathname, Client $client, XmlParser $parser = null): array
    {
        $parser ??= new XmlParser();
        $dom = $parser->fromFile($pathname);
        $xp = new DOMXPath($dom);
        $results = [];
        foreach ($xp->query('//embed') as $embed) {
            $filename = $embed->attributes->getNamedItem('filename')->nodeValue;
            $r = $this->scanEmbed($embed, $xp, $client);
            $results[] = $this->getStatusMessage($r, $filename);
        }

        return $results;
    }

    /**
     * Scan an archive.
     *
     * @return string[]
     */
    public function scanArchiveFiles(PharData $phar, Client $client, Deposit $deposit): array
    {
        $depositXML = '/data/' . 'Issue' . $deposit->getDepositUuid() . '.xml';
        $results = [];
        foreach (new RecursiveIteratorIterator($phar) as $file) {
            $fh = fopen($file->getPathname(), 'r');
            $r = $client->scanResourceStream($fh);
            fclose($fh);
            $results[] = $this->getStatusMessage($r, $file->getFileName());
            if ($file->getFileName() === $depositXML) {
                $results = array_merge($this->scanXmlFile($file->getPathname(), $client), $results);
            }
        }

        return $results;
    }

    /**
     * Process one deposit.
     */
    public function processDeposit(Deposit $deposit, Client $client = null): bool
    {
        if (null === $client) {
            $client = $this->getClient();
        }
        $harvestedPath = $this->filePaths->getHarvestFile($deposit);
        $basename = basename($harvestedPath);
        $phar = new PharData($harvestedPath);

        $r = $client->scanFile($harvestedPath);
        $messages = [
            $this->getStatusMessage($r, $basename),
            ...$this->scanArchiveFiles($phar, $client, $deposit)
        ];
        $deposit->addToProcessingLog(implode("\n", $messages));

        return true;
    }

    /**
     * Retrieves the status message of a scan
     */
    private function getStatusMessage(Result $result, string $filename): string
    {
        $status = $result->isOk() ? Client::RESULT_OK : ($result->isFound() ? Client::RESULT_FOUND : Client::RESULT_ERROR);
        $reason = $result->isOk() ? '' : ": {$result->getReason()}";
        return "{$filename} {$status}{$reason}";
    }
}

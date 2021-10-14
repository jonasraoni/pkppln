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
use Symfony\Component\Filesystem\Filesystem;
use Xenolope\Quahog\Client;

/**
 * Virus scanning service, via ClamAV.
 */
class VirusScanner {
    /**
     * Buffer size for extracting embedded files.
     */
    public const BUFFER_SIZE = 64 * 1024;

    /**
     * File path service.
     *
     * @var FilePaths
     */
    private $filePaths;

    /**
     * Path to the ClamAV socket.
     *
     * @var string
     */
    private $socketPath;

    /**
     * Socket factory, for use with the Quahog ClamAV interface.
     *
     * @var Factory
     */
    private $factory;

    /**
     * Filesystem client.
     *
     * @var Filesystem
     */
    private $fs;

    /**
     * Construct the virus scanner.
     *
     * @param string $socketPath
     */
    public function __construct($socketPath, FilePaths $filePaths) {
        $this->filePaths = $filePaths;
        $this->socketPath = $socketPath;
        $this->bufferSize = self::BUFFER_SIZE;
        $this->factory = new Factory();
        $this->fs = new Filesystem();
    }

    /**
     * Set the socket factory.
     */
    public function setFactory(Factory $factory) : void {
        $this->factory = $factory;
    }

    /**
     * Get the Quahog client.
     *
     * The client can't be instantiated in the constructor. If the socket path
     * isn't configured or if the socket isn't set up yet the entire app will
     * fail. Symfony tries it instantiate all services for each request, and if
     * one constructor throws an exception everything gets cranky.
     *
     * @return Client
     */
    public function getClient() {
        $socket = $this->factory->createClient('unix://' . $this->socketPath);
        $client = new Client($socket, 30, PHP_NORMAL_READ);
        $client->startSession();

        return $client;
    }

    /**
     * Scan an embedded file.
     *
     * @return array
     */
    public function scanEmbed(DOMElement $embed, DOMXpath $xp, Client $client) {
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
     * @param string $pathname
     * @param XmlParser $parser
     *
     * @return array
     */
    public function scanXmlFile($pathname, Client $client, XmlParser $parser = null) {
        if ( ! $parser) {
            $parser = new XmlParser();
        }
        $dom = $parser->fromFile($pathname);
        $xp = new DOMXPath($dom);
        $results = [];
        foreach ($xp->query('//embed') as $embed) {
            $filename = $embed->attributes->getNamedItem('filename')->nodeValue;
            $r = $this->scanEmbed($embed, $xp, $client);
            if ('OK' === $r['status']) {
                $results[] = $filename . ' OK';
            } else {
                $results[] = $filename . ' ' . $r['status'] . ': ' . $r['reason'];
            }
        }

        return $results;
    }

    /**
     * Find all the embedded files in the XML and scan them.
     *
     * @return array
     */
    public function scanEmbededFiles(PharData $phar, Client $client) {
        $results = [];
        $parser = new XmlParser();
        foreach (new RecursiveIteratorIterator($phar) as $file) {
            if ('.xml' !== substr($file->getFilename(), -4)) {
                continue;
            }
            $results = array_merge($this->scanXmlFile($file->getPathname(), $client, $parser), $results);
        }

        return $results;
    }

    /**
     * Scan an archive.
     *
     * @return array
     */
    public function scanArchiveFiles(PharData $phar, Client $client) {
        $results = [];
        foreach (new RecursiveIteratorIterator($phar) as $file) {
            $fh = fopen($file->getPathname(), 'rb');
            $r = $client->scanResourceStream($fh);
            if ('OK' === $r['status']) {
                $results[] = "{$file->getFileName()} OK";
            } else {
                $results[] = "{$file->getFileName()} {$r['status']}: {$r['reason']}";
            }
        }

        return $results;
    }

    /**
     * Process one deposit.
     *
     * @param Client $client
     *
     * @return bool
     */
    public function processDeposit(Deposit $deposit, Client $client = null) {
        if (null === $client) {
            $client = $this->getClient();
        }
        $harvestedPath = $this->filePaths->getHarvestFile($deposit);
        $basename = basename($harvestedPath);
        $phar = new PharData($harvestedPath);

        $baseResult = [];
        $r = $client->scanFile($harvestedPath);
        if ('OK' === $r['status']) {
            $baseResult[] = "{$basename} OK";
        } else {
            $baseResult[] = "{$basename} {$r['status']}: {$r['reason']}";
        }
        $archiveResult = $this->scanArchiveFiles($phar, $client);
        $embeddedResult = $this->scanEmbededFiles($phar, $client);
        $deposit->addToProcessingLog(implode("\n", array_merge(
            $baseResult,
            $archiveResult,
            $embeddedResult
        )));

        return true;
    }
}

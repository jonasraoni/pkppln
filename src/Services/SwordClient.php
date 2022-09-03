<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use App\Entity\Deposit;
use App\Utilities\Namespaces;
use App\Utilities\ServiceDocument;
use DateTime;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;

/**
 * Description of SwordClient.
 */
class SwordClient
{
    /**
     * Configuration for the http client.
     */
    public const CONF = [
        'allow_redirects' => false,
        'headers' => [
            'User-Agent' => 'PkpPlnBot 1.0; http://pkp.sfu.ca',
        ],
        'decode_content' => false,
        'verify' => false,
        'connect_timeout' => 15,
    ];

    /**
     * File system utility.
     */
    private Filesystem $fs;

    /**
     * File path service.
     */
    private FilePaths $fp;

    /**
     * Twig template engine service.
     */
    private Environment $templating;

    /**
     * Guzzle HTTP client,.
     */
    private Client $client;

    /**
     * URL for the service document.
     */
    private string $serviceUri;

    /**
     * If true, save the deposit XML at /path/to/deposit.zip.xml.
     */
    private bool $saveXml;

    /**
     * Staging server UUID.
     */
    private string $uuid;

    /**
     * Construct the sword client.
     */
    public function __construct(string $serviceUri, string $uuid, bool $saveXml, FilePaths $filePaths, Environment $templating)
    {
        $this->serviceUri = $serviceUri;
        $this->uuid = $uuid;
        $this->saveXml = $saveXml;
        $this->fp = $filePaths;
        $this->templating = $templating;
        $this->fs = new Filesystem();
        $this->client = new Client(self::CONF);
    }

    /**
     * Set or override the HTTP client, usually based on Guzzle.
     */
    public function setClient(Client $client): void
    {
        $this->client = $client;
    }

    /**
     * Set or override  the file system client.
     */
    public function setFilesystem(Filesystem $fs): void
    {
        $this->fs = $fs;
    }

    /**
     * Set or override the file path service.
     */
    public function setFilePaths(FilePaths $fp): void
    {
        $this->fp = $fp;
    }

    /**
     * Set or override the service document URI.
     */
    public function setServiceUri(string $serviceUri): void
    {
        $this->serviceUri = $serviceUri;
    }

    /**
     * Set or override the UUID.
     */
    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    /**
     * Make a SWORD request.
     *
     * @param array<string, string|string[]> $headers
     * @param array<string, mixed> $options
     * @throws Exception
     */
    public function request(string $method, string $url, array $headers = [], string $xml = null, ?Deposit $deposit = null, array $options = []): ResponseInterface
    {
        try {
            $request = new Request($method, $url, $headers, $xml);

            return $this->client->send($request, $options);
        } catch (RequestException $e) {
            $message = Message::toString($e->getRequest());
            if ($e->hasResponse()) {
                $message .= "\n\n" . Message::toString($e->getResponse());
            }
            if ($deposit) {
                $deposit->addErrorLog($message);
            }

            throw new Exception($message);
        } catch (Exception $e) {
            $message = $e->getMessage();
            if ($deposit) {
                $deposit->addErrorLog($message);
            }

            throw new Exception($message);
        }
    }

    /**
     * Fetch the service document.
     *
     * @throws Exception
     */
    public function serviceDocument(): ServiceDocument
    {
        $response = $this->request('GET', $this->serviceUri, [
            'On-Behalf-Of' => $this->uuid,
        ]);

        return new ServiceDocument($response->getBody()->getContents());
    }

    /**
     * Create a deposit in LOCKSSOMatic.
     *
     * @throws Exception
     */
    public function createDeposit(Deposit $deposit): bool
    {
        $sd = $this->serviceDocument();
        $xml = $this->templating->render('sword/deposit.xml.twig', [
            'deposit' => $deposit,
        ]);
        if ($this->saveXml) {
            $path = $this->fp->getStagingBagPath($deposit) . '.xml';
            $this->fs->dumpFile($path, $xml);
        }
        $response = $this->request('POST', $sd->getCollectionUri(), [], $xml, $deposit);
        $locationHeader = $response->getHeader('Location');
        if (\count($locationHeader) > 0) {
            $deposit->setDepositReceipt($locationHeader[0]);
        }
        $deposit->setDepositDate(new DateTime());

        return true;
    }

    /**
     * Fetch the deposit receipt for $deposit.
     *
     * @throws Exception
     */
    public function receipt(Deposit $deposit): ?SimpleXMLElement
    {
        if (! $deposit->getDepositReceipt()) {
            return null;
        }
        $response = $this->request('GET', $deposit->getDepositReceipt(), [], null, $deposit);
        $xml = new SimpleXMLElement($response->getBody()->getContents());
        Namespaces::registerNamespaces($xml);

        return $xml;
    }

    /**
     * Fetch the sword statement for $deposit.
     *
     * @throws Exception
     */
    public function statement(Deposit $deposit): SimpleXMLElement
    {
        $receiptXml = $this->receipt($deposit);
        $statementUrl = (string) $receiptXml->xpath('atom:link[@rel="http://purl.org/net/sword/terms/statement"]/@href')[0];
        $response = $this->request('GET', $statementUrl, [], null, $deposit);
        $statementXml = new SimpleXMLElement($response->getBody()->getContents());
        Namespaces::registerNamespaces($statementXml);

        return $statementXml;
    }

    /**
     * Fetch the deposit back from LOCKSSOmatic.
     * Saves the file to disk and returns the full path to the file.
     *
     * @throws Exception
     */
    public function fetch(Deposit $deposit): string
    {
        $statement = $this->statement($deposit);
        $original = (string) $statement->xpath('//sword:originalDeposit/@href')[0];
        $filepath = $this->fp->getRestoreFile($deposit);

        $this->request('GET', $original, [], null, $deposit, [
            'allow_redirects' => false,
            'decode_content' => false,
            'save_to' => $filepath,
        ]);

        return $filepath;
    }
}

<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use App\Entity\Journal;
use App\Entity\Whitelist;
use App\Utilities\PingResult;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;

/**
 * Ping service.
 */
class Ping {
    /**
     * Http client configuration.
     */
    public const CONF = [
        'allow_redirects' => true,
        'headers' => [
            'User-Agent' => 'PkpPlnBot 1.0; http://pkp.sfu.ca',
            'Accept' => 'application/xml,text/xml,*/*;q=0.1',
        ],
    ];

    /**
     * Minimum expected OJS version.
     *
     * @var string
     */
    private $minOjsVersion;

    /**
     * Doctrine instance.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Black and white service.
     *
     * @var BlackWhiteList
     */
    private $list;

    /**
     * Guzzle http client.
     *
     * @var Client
     */
    private $client;

    /**
     * Construct the ping service.
     *
     * @param type $minOjsVersion
     */
    public function __construct($minOjsVersion, EntityManagerInterface $em, BlackWhiteList $list) {
        $this->minOjsVersion = $minOjsVersion;
        $this->em = $em;
        $this->list = $list;
        $this->client = new Client();
    }

    /**
     * Set the HTTP client.
     */
    public function setClient(Client $client) : void {
        $this->client = $client;
    }

    /**
     * Process a ping response.
     */
    public function process(Journal $journal, PingResult $result) : void {
        if ( ! $result->getOjsRelease()) {
            $journal->setStatus('ping-error');
            $result->addError('Journal version information missing in ping result.');

            return;
        }
        $journal->setContacted(new DateTime());
        $journal->setTitle($result->getJournalTitle());
        $journal->setOjsVersion($result->getOjsRelease());
        $journal->setTermsAccepted('yes' === $result->areTermsAccepted());
        $journal->setStatus('healthy');
        if (version_compare($result->getOjsRelease(), $this->minOjsVersion, '<')) {
            return;
        }
        if ($this->list->isListed($journal->getUuid())) {
            return;
        }
        $whitelist = new Whitelist();
        $whitelist->setUuid($journal->getUuid());
        $whitelist->setComment("{$journal->getUrl()} added by ping.");
        $this->em->persist($whitelist);
        $this->em->flush();
    }

    /**
     * Ping $journal and return the result.
     *
     * @return PingResult
     */
    public function ping(Journal $journal) {
        try {
            $response = $this->client->get($journal->getGatewayUrl(), self::CONF);
            $result = new PingResult($response);
            $this->process($journal, $result);

            return $result;
        } catch (Exception $e) {
            $journal->setStatus('ping-error');
            $message = strip_tags($e->getMessage());

            return new PingResult(null, $message);
        }
    }
}

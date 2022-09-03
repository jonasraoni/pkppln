<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use App\Entity\Journal;
use App\Repository\Repository;
use App\Utilities\Xpath;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use SimpleXMLElement;

/**
 * Journal builder service.
 */
class JournalBuilder
{
    /**
     * Doctrine instance.
     */
    private EntityManagerInterface $em;

    /**
     * Construct the builder.
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Build and persist a journal from XML.
     *
     * Does not flush the journal to the database.
     */
    public function fromXml(SimpleXMLElement $xml, string $uuid): Journal
    {
        $journal = Repository::Journal()->findOneBy(['uuid' => strtoupper($uuid)]);
        if (null === $journal) {
            $journal = new Journal();
        }
        $journal->setUuid($uuid);
        $journal->setTitle(Xpath::getXmlValue($xml, '//atom:title'));
        // &amp; -> &.
        $journal->setUrl(html_entity_decode(Xpath::getXmlValue($xml, '//pkp:journal_url')));
        $journal->setEmail(Xpath::getXmlValue($xml, '//atom:email'));
        $journal->setIssn(Xpath::getXmlValue($xml, '//pkp:issn'));
        $journal->setPublisherName(Xpath::getXmlValue($xml, '//pkp:publisherName'));
        // &amp; -> &.
        $journal->setPublisherUrl(html_entity_decode(Xpath::getXmlValue($xml, '//pkp:publisherUrl')));
        $journal->setContacted(new DateTime());
        $this->em->persist($journal);

        return $journal;
    }

    /**
     * The journal with UUID $uuid has contacted the PLN.
     */
    public function fromRequest(string $uuid, string $url): Journal
    {
        $journal = Repository::Journal()->findOneBy([
            'uuid' => strtoupper($uuid),
        ]);
        if (null === $journal) {
            $journal = new Journal();
            $journal->setUuid($uuid);
            $journal->setStatus('new');
            $journal->setEmail('unknown@unknown.com');
            $this->em->persist($journal);
        }
        $journal->setUrl($url);
        $journal->setContacted(new DateTime());
        if ('new' !== $journal->getStatus()) {
            $journal->setStatus('healthy');
        }

        return $journal;
    }
}

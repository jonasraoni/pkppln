<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use App\Entity\Deposit;
use App\Entity\Journal;
use App\Utilities\Xpath;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use SimpleXMLElement;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Description of DepositBuilder.
 */
class DepositBuilder {
    /**
     * Entity manager.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * URL generator.
     *
     * @var UrlGeneratorInterface
     */
    private $generator;

    /**
     * Build the service.
     */
    public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $generator) {
        $this->em = $em;
        $this->generator = $generator;
    }

    /**
     * Find and return the deposit with $uuid or create a new deposit.
     *
     * @param string $uuid
     *
     * @return Deposit
     */
    protected function findDeposit($uuid) {
        $deposit = $this->em->getRepository(Deposit::class)->findOneBy([
            'depositUuid' => strtoupper($uuid),
        ]);
        $action = 'edit';
        if ( ! $deposit) {
            $action = 'add';
            $deposit = new Deposit();
            $deposit->setDepositUuid($uuid);
        }
        if ('add' === $action) {
            $deposit->addToProcessingLog('Deposit received.');
        } else {
            $deposit->addToProcessingLog('Deposit edited or reset by journal manager.');
        }
        $deposit->setAction($action);

        return $deposit;
    }

    /**
     * Build a deposit from XML.
     *
     * @return Deposit
     */
    public function fromXml(Journal $journal, SimpleXMLElement $xml) {
        $id = Xpath::getXmlValue($xml, '//atom:id');
        $deposit = $this->findDeposit(substr($id, 9, 36));
        $deposit->setState('depositedByJournal');
        $deposit->setChecksumType(Xpath::getXmlValue($xml, 'pkp:content/@checksumType'));
        $deposit->setChecksumValue(Xpath::getXmlValue($xml, 'pkp:content/@checksumValue'));
        $deposit->setFileType('');
        $deposit->setIssue(Xpath::getXmlValue($xml, 'pkp:content/@issue'));
        $deposit->setVolume(Xpath::getXmlValue($xml, 'pkp:content/@volume'));
        $deposit->setPubDate(new DateTime(Xpath::getXmlValue($xml, 'pkp:content/@pubdate')));
        $deposit->setJournal($journal);
        $deposit->setSize(Xpath::getXmlValue($xml, 'pkp:content/@size'));
        $deposit->setUrl(html_entity_decode(Xpath::getXmlValue($xml, 'pkp:content')));

        $deposit->setJournalVersion(Xpath::getXmlValue($xml, 'pkp:content/@ojsVersion', Deposit::DEFAULT_JOURNAL_VERSION));
        foreach ($xml->xpath('//pkp:license/node()') as $node) {
            $deposit->addLicense($node->getName(), (string) $node);
        }
        $this->em->persist($deposit);

        return $deposit;
    }
}

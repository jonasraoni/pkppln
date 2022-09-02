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
use Exception;
use Psr\Log\LoggerAwareTrait;
use SimpleXMLElement;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Description of DepositBuilder.
 */
class DepositBuilder
{
    use LoggerAwareTrait;

    /**
     * Entity manager.
     */
    private EntityManagerInterface $em;

    /**
     * URL generator.
     */
    private UrlGeneratorInterface $generator;

    /**
     * File paths.
     */
    private FilePaths $filePaths;

    /**
     * Build the service.
     */
    public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $generator, FilePaths $filePaths)
    {
        $this->em = $em;
        $this->generator = $generator;
        $this->filePaths = $filePaths;
    }

    /**
     * Find and return the deposit with $uuid or create a new deposit.
     */
    protected function findDeposit(string $uuid): Deposit
    {
        /** @var Deposit */
        $deposit = $this->em->getRepository(Deposit::class)->findOneBy(['depositUuid' => strtoupper($uuid)]);
        if (!$deposit) {
            return (new Deposit())->setDepositUuid($uuid)
                ->setAction('add')
                ->addToProcessingLog('Deposit received.');
        }

        // Clear outdated files
        $fs = new Filesystem();
        $paths = [
            $this->filePaths->getHarvestFile($deposit),
            $this->filePaths->getProcessingBagPath($deposit),
            $this->filePaths->getStagingBagPath($deposit)
        ];
        foreach ($paths as $path) {
            try {
                $fs->remove($path);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        return $deposit->setAction('edit')
            ->setHarvestAttempts(0)
            ->addToProcessingLog('Deposit edited or reset by journal manager.');
    }

    /**
     * Build a deposit from XML.
     */
    public function fromXml(Journal $journal, SimpleXMLElement $xml): Deposit
    {
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
        $deposit->setSize((int) Xpath::getXmlValue($xml, 'pkp:content/@size'));
        $deposit->setUrl(html_entity_decode(Xpath::getXmlValue($xml, 'pkp:content')));

        $deposit->setJournalVersion(Xpath::getXmlValue($xml, 'pkp:content/@ojsVersion', Deposit::DEFAULT_JOURNAL_VERSION));
        foreach ($xml->xpath('//pkp:license/node()') as $node) {
            $deposit->addLicense($node->getName(), (string) $node);
        }
        $this->em->persist($deposit);

        return $deposit;
    }
}

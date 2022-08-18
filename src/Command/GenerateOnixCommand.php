<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command;

use App\Entity\Journal;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Internal\Hydration\IterableResult;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use XMLWriter;

/**
 * Generate an ONIX-PH feed for all the deposits in the PLN.
 *
 * @see http://www.editeur.org/127/ONIX-PH/
 */
class GenerateOnixCommand extends Command {
    public const BATCH_SIZE = 50;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Set the service container, and initialize the command.
     */
    public function __construct(LoggerInterface $logger, EntityManagerInterface $em) {
        parent::__construct();
        $this->logger = $logger;
        $this->em = $em;
    }

    /**
     * Get the journals to process.
     *
     * @return IterableResult|Journal[]
     */
    protected function getJournals() {
        $query = $this->em->createQuery('SELECT j FROM App:Journal j');

        return $query->iterate();
    }

    /**
     * Generate a CSV file at $filePath.
     *
     * @param string $filePath
     */
    protected function generateCsv($filePath) : void {
        $handle = fopen($filePath, 'w');
        $iterator = $this->getJournals();
        fputcsv($handle, ['Generated', date('Y-m-d')]);
        fputcsv($handle, [
            'ISSN',
            'Title',
            'Publisher',
            'Url',
            'Vol',
            'No',
            'Published',
            'Deposited',
        ]);
        $i = 0;
        foreach ($iterator as $row) {
            /** @var Journal $journal */
            $journal = $row[0];
            $deposits = $journal->getSentDeposits();
            if (0 === $deposits->count()) {
                continue;
            }
            foreach ($deposits as $deposit) {
                if (null === $deposit->getDepositDate()) {
                    continue;
                }
                fputcsv($handle, [
                    $journal->getIssn(),
                    $journal->getTitle(),
                    $journal->getPublisherName(),
                    $journal->getUrl(),
                    $deposit->getVolume(),
                    $deposit->getIssue(),
                    $deposit->getPubDate()->format('Y-m-d'),
                    $deposit->getDepositDate()->format('Y-m-d'),
                ]);
            }
            $i++;
            $this->em->detach($journal);
            if ($i % self::BATCH_SIZE) {
                $this->em->clear();
            }
        }
    }

    /**
     * Generate an XML file at $filePath.
     *
     * @param string $filePath
     */
    protected function generateXml($filePath) : void {
        $iterator = $this->getJournals();

        $writer = new XMLWriter();
        $writer->openUri($filePath);
        $writer->setIndent(true);
        $writer->setIndentString(' ');
        $writer->startDocument();
        $writer->startElement('ONIXPreservationHoldings');
        $writer->writeAttribute('version', '0.2');
        $writer->writeAttribute('xmlns', 'http://www.editeur.org/onix/serials/SOH');

        $writer->startElement('Header');
        $writer->startElement('Sender');
        $writer->writeElement('SenderName', 'Public Knowledge Project PLN');
        $writer->endElement(); // Sender
        $writer->writeElement('SentDateTime', date('Ymd'));
        $writer->writeElement('CompleteFile');
        $writer->endElement(); // Header.

        $writer->startElement('HoldingsList');
        $writer->startElement('PreservationAgency');
        $writer->writeElement('PreservationAgencyName', 'Public Knowledge Project PLN');
        $writer->endElement(); // PreservationAgency

        foreach ($iterator as $row) {
            $journal = $row[0];
            $deposits = $journal->getSentDeposits();
            if (0 === count($deposits)) {
                $this->em->detach($journal);

                continue;
            }
            $writer->startElement('HoldingsRecord');

            $writer->startElement('NotificationType');
            $writer->text('00');
            $writer->endElement(); // NotificationType

            $writer->startElement('ResourceVersion');

            $writer->startElement('ResourceVersionIdentifier');
            $writer->writeElement('ResourceVersionIDType', '07');
            $writer->writeElement('IDValue', $journal->getIssn());
            $writer->endElement(); // ResourceVersionIdentifier

            $writer->startElement('Title');
            $writer->writeElement('TitleType', '01');
            $writer->writeElement('TitleText', $journal->getTitle());
            $writer->endElement(); // Title

            $writer->startElement('Publisher');
            $writer->writeElement('PublishingRole', '01');
            $writer->writeElement('PublisherName', $journal->getPublisherName());
            $writer->endElement(); // Publisher

            $writer->startElement('OnlinePackage');

            $writer->startElement('Website');
            $writer->writeElement('WebsiteRole', '05');
            $writer->writeElement('WebsiteLink', $journal->getUrl());
            $writer->endElement(); // Website

            foreach ($deposits as $deposit) {
                $writer->startElement('PackageDetail');
                $writer->startElement('Coverage');

                $writer->writeElement('CoverageDescriptionLevel', '03');
                $writer->writeElement('SupplementInclusion', '04');
                $writer->writeElement('IndexInclusion', '04');

                $writer->startElement('FixedCoverage');
                $writer->startElement('Release');

                $writer->startElement('Enumeration');

                $writer->startElement('Level1');
                $writer->writeElement('Unit', 'Volume');
                $writer->writeElement('Number', $deposit->getVolume());
                $writer->endElement(); // Level1

                $writer->startElement('Level2');
                $writer->writeElement('Unit', 'Issue');
                $writer->writeElement('Number', $deposit->getIssue());
                $writer->endElement(); // Level2

                $writer->endElement(); // Enumeration

                $writer->startElement('NominalDate');
                $writer->writeElement('Calendar', '00');
                $writer->writeElement('DateFormat', '00');
                $writer->writeElement('Date', $deposit->getPubDate()->format('Ymd'));
                $writer->endElement(); // NominalDate

                $writer->endElement(); // Release
                $writer->endElement(); // FixedCoverage
                $writer->endElement(); // Coverage

                $writer->startElement('PreservationStatus');
                $writer->writeElement('PreservationStatusCode', '05');
                $writer->writeElement('DateOfStatus', $deposit->getDepositDate() ? $deposit->getDepositDate()
                    ->format('Ymd') : date('Ymd'));
                $writer->endElement(); // PreservationStatus

                $writer->writeElement('VerificationStatus', '01');
                $writer->endElement(); // PackageDetail
                $this->em->detach($deposit);
            }
            $writer->endElement(); // OnlinePackage
            $writer->endElement(); // ResourceVersion
            $writer->endElement(); // HoldingsRecord

            $writer->flush();
            $this->em->detach($journal);
            $this->em->clear();
        }

        $writer->endElement(); // HoldingsList
        $writer->endElement(); // ONIXPreservationHoldings
        $writer->endDocument();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) : void {
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        ini_set('memory_limit', '512M');
        $files = $input->getArgument('file');
        if ( ! $files || 0 === count($files)) {
            $fp = $this->getContainer()->get('filepaths');
            $files[] = $fp->getOnixPath('xml');
            $files[] = $fp->getOnixPath('csv');
        }

        foreach ($files as $file) {
            $this->logger->info("Writing {$file}");
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            switch ($ext) {
                case 'xml':
                    $this->generateXml($file);

                    break;
                case 'csv':
                    $this->generateCsv($file);

                    break;
                default:
                    $this->logger->error("Cannot generate {$ext} ONIX format.");

                    break;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configure() : void {
        $this->setName('pln:onix');
        $this->setDescription('Generate ONIX-PH feed.');
        $this->addArgument('file', InputArgument::IS_ARRAY, 'File(s) to write the feed to.');
    }
}

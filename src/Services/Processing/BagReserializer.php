<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services\Processing;

use App\Entity\AuContainer;
use App\Entity\Deposit;
use App\Repository\Repository;
use App\Services\FilePaths;
use App\Utilities\BagReader;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Filesystem\Filesystem;
use whikloj\BagItTools\Bag;

/**
 * Take a processed bag and reserialize it.
 */
class BagReserializer
{
    /**
     * File path service.
     */
    private FilePaths $filePaths;

    /**
     * Bag reader service.
     */
    private BagReader $bagReader;

    private EntityManagerInterface $em;

    private int $maxAuSize;

    /**
     * Construct the reserializer service.
     */
    public function __construct(int $maxAuSize, FilePaths $fp, BagReader $bagReader, EntityManagerInterface $em)
    {
        $this->maxAuSize = $maxAuSize;
        $this->bagReader = $bagReader;
        $this->filePaths = $fp;
        $this->em = $em;
    }

    /**
     * Add the metadata from the database to the bag-info.txt file.
     */
    protected function addMetadata(Bag $bag, Deposit $deposit): void
    {
        $bag->addBagInfoTag('External-Identifier', $deposit->getDepositUuid());
        $bag->addBagInfoTag('PKP-PLN-Deposit-UUID', $deposit->getDepositUuid());
        $bag->addBagInfoTag('PKP-PLN-Deposit-Received', (string) $deposit->getReceived()?->format('c'));
        $bag->addBagInfoTag('PKP-PLN-Deposit-Volume', $deposit->getVolume());
        $bag->addBagInfoTag('PKP-PLN-Deposit-Issue', $deposit->getIssue());
        $bag->addBagInfoTag('PKP-PLN-Deposit-PubDate', $deposit->getPubDate()->format('c'));

        $journal = $deposit->getJournal();
        $bag->addBagInfoTag('PKP-PLN-Journal-UUID', $journal->getUuid());
        $bag->addBagInfoTag('PKP-PLN-Journal-Title', (string) $journal->getTitle());
        $bag->addBagInfoTag('PKP-PLN-Journal-ISSN', (string) $journal->getIssn());
        $bag->addBagInfoTag('PKP-PLN-Journal-URL', $journal->getUrl());
        $bag->addBagInfoTag('PKP-PLN-Journal-Email', (string) $journal->getEmail());
        $bag->addBagInfoTag('PKP-PLN-Publisher-Name', (string) $journal->getPublisherName());
        $bag->addBagInfoTag('PKP-PLN-Publisher-URL', (string) $journal->getPublisherUrl());

        foreach ($deposit->getLicense() as $key => $value) {
            $bag->addBagInfoTag('PKP-PLN-' . $key, $value);
        }
    }

    /**
     * Override the default bag reader.
     */
    public function setBagReader(BagReader $bagReader): void
    {
        $this->bagReader = $bagReader;
    }

    public function processDeposit(Deposit $deposit): bool
    {
        $processingPath = $this->filePaths->getProcessingBagPath($deposit);
        $bag = $this->bagReader->readBag($processingPath);
        $bag->createFile($deposit->getProcessingLog(), 'data/processing-log.txt');
        $errorLog = $deposit->getErrorLog("\n\n");
        \assert(\is_string($errorLog));
        $bag->createFile($errorLog, 'data/error-log.txt');
        $this->addMetadata($bag, $deposit);
        $bag->update();

        $path = $this->filePaths->getStagingBagPath($deposit);
        (new Filesystem())->remove($path);

        $bag->package($path);
        // Bytes to kb.
        $deposit->setPackageSize((int) ceil(filesize($path) / 1000));
        $deposit->setPackageChecksumType('sha1');
        $deposit->setPackageChecksumValue(hash_file('sha1', $path) ?: throw new Exception("Failed to generate hash for {$path}"));

        $auContainer = Repository::auContainer()->getOpenContainer();
        if (null === $auContainer) {
            $auContainer = new AuContainer();
            $this->em->persist($auContainer);
        }
        $deposit->setAuContainer($auContainer);
        $auContainer->addDeposit($deposit);
        if ($auContainer->getSize() > $this->maxAuSize) {
            $auContainer->setOpen(false);
        }
        $this->em->flush();

        return true;
    }
}

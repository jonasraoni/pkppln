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
use App\Services\FilePaths;
use App\Utilities\BagReader;
use Doctrine\ORM\EntityManagerInterface;
use whikloj\BagItTools\Bag;

/**
 * Take a processed bag and reserialize it.
 */
class BagReserializer {
    /**
     * File path service.
     *
     * @var FilePaths
     */
    private $filePaths;

    /**
     * Bag reader service.
     *
     * @var BagReader
     */
    private $bagReader;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var int
     */
    private $maxAuSize;

    /**
     * Construct the reserializer service.
     *
     * @param mixed $maxAuSize
     */
    public function __construct($maxAuSize, FilePaths $fp, BagReader $bagReader, EntityManagerInterface $em) {
        $this->maxAuSize = $maxAuSize;
        $this->bagReader = $bagReader;
        $this->filePaths = $fp;
        $this->em = $em;
    }

    /**
     * Add the metadata from the database to the bag-info.txt file.
     */
    protected function addMetadata(Bag $bag, Deposit $deposit) : void {
        // @todo this is very very bad. Once BagItPHP is updated it should be $bag->clearAllBagInfo();
        $bag->addBagInfoTag('External-Identifier', $deposit->getDepositUuid());
        $bag->addBagInfoTag('PKP-PLN-Deposit-UUID', $deposit->getDepositUuid());
        $bag->addBagInfoTag('PKP-PLN-Deposit-Received', $deposit->getReceived()->format('c'));
        $bag->addBagInfoTag('PKP-PLN-Deposit-Volume', $deposit->getVolume());
        $bag->addBagInfoTag('PKP-PLN-Deposit-Issue', $deposit->getIssue());
        $bag->addBagInfoTag('PKP-PLN-Deposit-PubDate', $deposit->getPubDate()->format('c'));

        $journal = $deposit->getJournal();
        $bag->addBagInfoTag('PKP-PLN-Journal-UUID', $journal->getUuid());
        $bag->addBagInfoTag('PKP-PLN-Journal-Title', $journal->getTitle());
        $bag->addBagInfoTag('PKP-PLN-Journal-ISSN', $journal->getIssn());
        $bag->addBagInfoTag('PKP-PLN-Journal-URL', $journal->getUrl());
        $bag->addBagInfoTag('PKP-PLN-Journal-Email', $journal->getEmail());
        $bag->addBagInfoTag('PKP-PLN-Publisher-Name', $journal->getPublisherName());
        $bag->addBagInfoTag('PKP-PLN-Publisher-URL', $journal->getPublisherUrl());

        foreach ($deposit->getLicense() as $key => $value) {
            $bag->addBagInfoTag('PKP-PLN-' . $key, $value);
        }
    }

    /**
     * Override the default bag reader.
     */
    public function setBagReader(BagReader $bagReader) : void {
        $this->bagReader = $bagReader;
    }

    public function processDeposit(Deposit $deposit) {
        $harvestedPath = $this->filePaths->getHarvestFile($deposit);
        $bag = $this->bagReader->readBag($harvestedPath);
        $bag->createFile($deposit->getProcessingLog(), 'data/processing-log.txt');
        $bag->createFile($deposit->getErrorLog("\n\n"), 'data/error-log.txt');
        $this->addMetadata($bag, $deposit);
        $bag->update();

        $path = $this->filePaths->getStagingBagPath($deposit);
        if (file_exists($path)) {
            unlink($path);
        }

        $bag->package($path, 'zip');
        // Bytes to kb.
        $deposit->setPackageSize(ceil(filesize($path) / 1000));
        $deposit->setPackageChecksumType('sha1');
        $deposit->setPackageChecksumValue(hash_file('sha1', $path));

        $auContainer = $this->em->getRepository('App:AuContainer')->getOpenContainer();
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

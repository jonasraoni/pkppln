<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services\Processing;

use App\Entity\Deposit;
use App\Services\DtdValidator;
use App\Services\FilePaths;
use App\Services\SchemaValidator;
use App\Utilities\BagReader;
use App\Utilities\XmlParser;

/**
 * Validate the OJS XML export.
 *
 * @todo Rewrite this to use XmlParser.
 */
class XmlValidator {
    /**
     * The PKP Public Identifier for OJS export XML.
     */
    public const PKP_PUBLIC_ID = '-//PKP//OJS Articles and Issues XML//EN';

    /**
     * Block size for reading very large files.
     */
    public const BLOCKSIZE = 64 * 1023;

    /**
     * Calculate file path locations.
     *
     * @var FilePaths
     */
    private $filePaths;

    /**
     * Validator service.
     *
     * @var DtdValidator
     */
    private $dtdValidator;

    /**
     * Parser for XML files.
     *
     * @var XmlParser
     */
    private $xmlParser;

    /**
     * Bag Reader.
     *
     * @var BagReader
     */
    private $bagReader;

    /**
     * @var SchemaValidator
     */
    private $schemaValidator;

    /**
     * Build the validator.
     */
    public function __construct(FilePaths $filePaths, DtdValidator $dtdValidator, SchemaValidator $schemaValidator) {
        $this->filePaths = $filePaths;
        $this->dtdValidator = $dtdValidator;
        $this->schemaValidator = $schemaValidator;
        $this->xmlParser = new XmlParser();
        $this->bagReader = new BagReader();
    }

    /**
     * Override the default bag reader.
     */
    public function setBagReader(BagReader $bagReader) : void {
        $this->bagReader = $bagReader;
    }

    /**
     * Override the default Xml Parser.
     */
    public function setXmlParser(XmlParser $xmlParser) : void {
        $this->xmlParser = $xmlParser;
    }

    /**
     * Add any errors to the report.
     *
     * @param string $report
     */
    public function reportErrors(array $errors, &$report) : void {
        foreach ($errors as $error) {
            $report .= "On line {$error['line']}: {$error['message']}\n";
        }
    }

    public function processDeposit(Deposit $deposit) {
        $harvestedPath = $this->filePaths->getHarvestFile($deposit);
        $bag = $bag = $this->bagReader->readBag($harvestedPath);
        $report = '';

        $issuePath = $bag->getBagRoot() . '/data/' . 'Issue' . $deposit->getDepositUuid() . '.xml';
        $dom = $this->xmlParser->fromFile($issuePath);
        $root = $dom->documentElement;
        if ($root->hasAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'schemaLocation')) {
            $this->schemaValidator->validate($dom, $bag->getBagRoot() . '/data/');
        } else {
            $this->dtdValidator->validate($dom, $bag->getBagRoot() . '/data/');
        }
        $this->reportErrors($this->dtdValidator->getErrors(), $report);
        if (trim($report)) {
            $deposit->addToProcessingLog($report);

            return false;
        }

        return true;
    }
}

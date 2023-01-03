<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Utilities;

use Exception;
use SimpleXMLElement;

/**
 * Wrapper around a SWORD service document.
 */
class ServiceDocument
{
    /**
     * XML from the document.
     */
    private SimpleXMLElement $xml;

    /**
     * Construct the object.
     */
    public function __construct(string $data)
    {
        $this->xml = new SimpleXMLElement($data);
        Namespaces::registerNamespaces($this->xml);
    }

    /**
     * Return the XML for the document.
     */
    public function __toString(): string
    {
        return $this->xml->asXML() ?: throw new Exception('Failed to generate XML');
    }

    /**
     * Get a single value from the document based on the XPath query $xpath.
     *
     * @throws Exception
     *                   If the query results in multiple values.
     */
    public function getXpathValue(string $xpath): ?string
    {
        $result = $this->xml->xpath($xpath) ?: [];
        return match (\count($result)) {
            0 => null,
            1 => (string) $result[0],
            default => throw new Exception('Too many values returned by xpath query.')
        };
    }

    /**
     * Get the maximum upload size.
     */
    public function getMaxUpload(): string
    {
        return $this->getXpathValue('sword:maxUploadSize') ?: throw new Exception('Empty max upload size');
    }

    /**
     * Get the upload checksum type.
     */
    public function getUploadChecksum(): string
    {
        return $this->getXpathValue('lom:uploadChecksumType') ?: throw new Exception('Empty upload checksum type');
    }

    /**
     * Get the collection URI from the service document.
     */
    public function getCollectionUri(): string
    {
        return $this->getXpathValue('.//app:collection/@href') ?: throw new Exception('Empty collection URI');
    }
}

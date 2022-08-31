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
class ServiceDocument {
    /**
     * XML from the document.
     */
    private SimpleXMLElement $xml;

    /**
     * Construct the object.
     */
    public function __construct(string $data) {
        $this->xml = new SimpleXMLElement($data);
        Namespaces::registerNamespaces($this->xml);
    }

    /**
     * Return the XML for the document.
     */
    public function __toString(): string {
        return $this->xml->asXML();
    }

    /**
     * Get a single value from the document based on the XPath query $xpath.
     *
     * @throws Exception
     *                   If the query results in multiple values.
     */
    public function getXpathValue(string $xpath): ?string {
        $result = $this->xml->xpath($xpath);
        if (0 === count($result)) {
            return null;
        }
        if (count($result) > 1) {
            throw new Exception('Too many values returned by xpath query.');
        }

        return (string) $result[0];
    }

    /**
     * Get the maximum upload size.
     */
    public function getMaxUpload(): string {
        return $this->getXpathValue('sword:maxUploadSize');
    }

    /**
     * Get the upload checksum type.
     */
    public function getUploadChecksum(): string {
        return $this->getXpathValue('lom:uploadChecksumType');
    }

    /**
     * Get the collection URI from the service document.
     */
    public function getCollectionUri(): string {
        return $this->getXpathValue('.//app:collection/@href');
    }
}

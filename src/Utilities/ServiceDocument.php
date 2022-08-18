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
     *
     * @var SimpleXMLElement
     */
    private $xml;

    /**
     * Construct the object.
     *
     * @param string $data
     */
    public function __construct($data) {
        $this->xml = new SimpleXMLElement($data);
        Namespaces::registerNamespaces($this->xml);
    }

    /**
     * Return the XML for the document.
     *
     * @return string
     */
    public function __toString() {
        return $this->xml->asXML();
    }

    /**
     * Get a single value from the document based on the XPath query $xpath.
     *
     * @param string $xpath
     *
     * @throws Exception
     *                   If the query results in multiple values.
     *
     * @return null|string
     */
    public function getXpathValue($xpath) {
        $result = $this->xml->xpath($xpath);
        if (0 === count($result)) {
            return;
        }
        if (count($result) > 1) {
            throw new Exception('Too many values returned by xpath query.');
        }

        return (string) $result[0];
    }

    /**
     * Get the maximum upload size.
     *
     * @return string
     */
    public function getMaxUpload() {
        return $this->getXpathValue('sword:maxUploadSize');
    }

    /**
     * Get the upload checksum type.
     *
     * @return string
     */
    public function getUploadChecksum() {
        return $this->getXpathValue('lom:uploadChecksumType');
    }

    /**
     * Get the collection URI from the service document.
     *
     * @return string
     */
    public function getCollectionUri() {
        return $this->getXpathValue('.//app:collection/@href');
    }
}

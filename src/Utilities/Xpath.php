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
 * Wrapper around some XPath functions.
 */
class Xpath
{
    /**
     * Get a single XML value as a string.
     *
     * @throws Exception
     *                   If there are more than one result.
     */
    public static function getXmlValue(SimpleXMLElement $xml, string $xpath, string $default = null): string
    {
        $data = $xml->xpath($xpath);
        if (1 === \count($data)) {
            return trim((string) $data[0]);
        }
        if (0 === \count($data)) {
            return $default;
        }

        throw new Exception("Too many elements for '{$xpath}'");
    }

    /**
     * Query an XML document.
     */
    public static function query(SimpleXMLElement $xml, string $xpath): array
    {
        return $xml->xpath($xpath);
    }
}

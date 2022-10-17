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
     *                   If there is more than one result.
     */
    public static function getXmlValue(SimpleXMLElement $xml, string $xpath, ?string $default = null): ?string
    {
        $data = $xml->xpath($xpath);
        if (!is_countable($data)) {
            throw new Exception("Failed to query '{$xpath}'");
        }
        return match (count($data)) {
            1 => trim((string) $data[0]),
            0 => $default,
            default => throw new Exception("Too many elements for '{$xpath}'")
        };
    }

    /**
     * Query an XML document.
     * @return SimpleXMLElement[]|bool|null
     */
    public static function query(SimpleXMLElement $xml, string $xpath): array|bool|null
    {
        return $xml->xpath($xpath);
    }
}

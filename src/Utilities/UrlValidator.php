<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Utilities;

use Symfony\Component\HttpFoundation\Request;

/**
 * Validates URLs
 */
class UrlValidator
{
    /**
     * Retrieves whether the URL is acceptable by the system
     * @param string[] $forbiddenHosts
     */
    public static function isValid(?string $url, array $forbiddenHosts = []): bool
    {
        $host = parse_url((string) $url, PHP_URL_HOST);
        return !!$host
            && preg_match('/\./', $host) // Blocks top level domains (localhost, local network name, aliases)
            && !preg_match('/^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+/', $host) // Blocks IP addresses
            && (!count($forbiddenHosts) || !preg_match('/^(?:' . implode('|', array_map(fn (string $host) => preg_quote($host, '/'), $forbiddenHosts)) . ')/i', $host)); // Blocks forbidden hosts
    }
}

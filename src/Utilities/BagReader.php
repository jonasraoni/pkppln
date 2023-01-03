<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Utilities;

use Symfony\Component\Filesystem\Filesystem;
use Throwable;
use whikloj\BagItTools\Bag;
use whikloj\BagItTools\BagItException;

/**
 * Wrapper around BagIt.
 */
class BagReader
{
    /**
     * Decompresses a bag and retrieves it.
     *
     * @throws BagItException&Throwable
     */
    public function readCompressedBag(string $path, string $cachePath): Bag
    {
        $bag = static::readBag($path);
        $cachePath = realpath($cachePath);
        $path = realpath($bag->getBagRoot());
        // If the bag is in a different path, it was probably temporarily decompressed to another path
        if ($path && $cachePath && $path !== $cachePath) {
            $fs = new Filesystem();
            $fs->rename($path, $cachePath, true);
            $bag = static::readBag($cachePath);
        }

        return $bag;
    }

        /**
     * Read a bag from the file system.
     *
     * @throws BagItException&Throwable
     */
    public function readBag(string $path): Bag
    {
        $bag = Bag::load($path);
        return $bag;
    }
}

<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Utilities;

use Throwable;
use whikloj\BagItTools\Bag;
use whikloj\BagItTools\BagItException;

/**
 * Wrapper around BagIt.
 */
class BagReader
{
    /**
     * Read a bag from the file system.
     *
     * @throws BagItException&Throwable
     */
    public function readBag(string $path): Bag
    {
        return Bag::load($path);
    }
}

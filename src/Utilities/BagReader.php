<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Utilities;

use Symfony\Component\Filesystem\Filesystem;
use whikloj\BagItTools\Bag;
use whikloj\BagItTools\BagItException;

/**
 * Wrapper around BagIt.
 */
class BagReader {
    /**
     * @var Filesystem
     */
    private $fs;

    /**
     * Build the reader.
     */
    public function __construct() {
        $this->fs = new Filesystem();
    }

    /**
     * Read a bag from the file system.
     *
     * @param string $path
     *
     * @throws BagItException
     *
     * @return Bag
     */
    public function readBag($path) {
        return Bag::load($path);
    }
}

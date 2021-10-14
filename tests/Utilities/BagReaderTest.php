<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Utilities;

use App\Utilities\BagReader;
use Exception;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

/**
 * Description of XpathTest.
 */
class BagReaderTest extends TestCase {
    /**
     * @var vfsStreamDirectory
     */
    private $root;

    /**
     * @var BagReader
     */
    private $reader;

    public function testReadBagException() : void {
        $this->expectException(Exception::class);
        $this->reader->readBag($this->root->url() . '/doesnotexist');
    }

    protected function setup() : void {
        parent::setUp();
        $this->root = vfsStream::setup();
        $this->reader = new BagReader();
    }
}

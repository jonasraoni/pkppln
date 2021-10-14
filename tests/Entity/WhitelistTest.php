<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Entity;

use App\Entity\Whitelist;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of WhitelistTest.
 */
class WhitelistTest extends ControllerBaseCase {
    private $whitelist;

    public function testInstance() : void {
        $this->assertInstanceOf(Whitelist::class, $this->whitelist);
    }

    public function testSetUuid() : void {
        $this->whitelist->setUuid('abc123');
        $this->assertSame('ABC123', $this->whitelist->getUuid());
    }

    public function testToString() : void {
        $this->whitelist->setUuid('abc123');
        $this->assertSame('ABC123', (string) $this->whitelist);
    }

    protected function setup() : void {
        parent::setUp();
        $this->whitelist = new Whitelist();
    }
}

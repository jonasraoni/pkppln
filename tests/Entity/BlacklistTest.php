<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Entity;

use App\Entity\Blacklist;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of BlacklistTest.
 */
class BlacklistTest extends ControllerBaseCase {
    private $blacklist;

    public function testInstance() : void {
        $this->assertInstanceOf(Blacklist::class, $this->blacklist);
    }

    public function testSetUuid() : void {
        $this->blacklist->setUuid('abc123');
        $this->assertSame('ABC123', $this->blacklist->getUuid());
    }

    public function testToString() : void {
        $this->blacklist->setUuid('abc123');
        $this->assertSame('ABC123', (string) $this->blacklist);
    }

    protected function setup() : void {
        parent::setUp();
        $this->blacklist = new Blacklist();
    }
}

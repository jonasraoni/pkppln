<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Entity;

use App\Entity\Deposit;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of DepositTest.
 */
class DepositTest extends ControllerBaseCase {
    private $deposit;

    public function testInstance() : void {
        $this->assertInstanceOf(Deposit::class, $this->deposit);
    }

    public function testSetUuid() : void {
        $this->deposit->setDepositUuid('abc123');
        $this->assertSame('ABC123', $this->deposit->getDepositUuid());
    }

    public function testToString() : void {
        $this->deposit->setDepositUuid('abc123');
        $this->assertSame('ABC123', (string) $this->deposit);
    }

    public function testSetChecksumType() : void {
        $this->deposit->setChecksumType('ABC123');
        $this->assertSame('abc123', $this->deposit->getChecksumType());
    }

    public function testSetChecksumValue() : void {
        $this->deposit->setChecksumValue('abc123');
        $this->assertSame('ABC123', $this->deposit->getChecksumValue());
    }

    public function testAddErrorLog() : void {
        $this->deposit->addErrorLog('foo');
        $this->assertSame(['foo'], $this->deposit->getErrorLog());
    }

    protected function setup() : void {
        parent::setUp();
        $this->deposit = new Deposit();
    }
}

<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Entity;

use Nines\UtilBundle\Tests\ControllerBaseCase;

class AuContainerTest extends ControllerBaseCase {
    /**
     * @var AuContainer
     */
    protected $auContainer;

    public function setOpenClosed() : void {
        $this->auContainer->setOpen(false);
        $this->assertSame(false, $this->auContainer->isOpen());
    }

    public function setClosedOpen() : void {
        $this->auContainer->setOpen(false);
        $this->auContainer->setOpen(true);
        $this->assertSame(false, $this->auContainer->isOpen());
    }

    public function testGetSizeEmpty() : void {
        $this->assertSame(0, $this->auContainer->getSize());
    }

    public function testGetSizeSingle() : void {
        $deposit = new Deposit();
        $deposit->setPackageSize(1234);
        $this->auContainer->addDeposit($deposit);
        $this->assertSame(1234, $this->auContainer->getSize());
    }

    public function testGetSizeMultiple() : void {
        $d1 = new Deposit();
        $d1->setPackageSize(1234);
        $this->auContainer->addDeposit($d1);
        $d2 = new Deposit();
        $d2->setPackageSize(4321);
        $this->auContainer->addDeposit($d2);
        $this->assertSame(5555, $this->auContainer->getSize());
    }

    public function testCountDepositsEmpty() : void {
        $this->assertSame(0, $this->auContainer->countDeposits());
    }

    public function testCountDepositsSingle() : void {
        $deposit = new Deposit();
        $this->auContainer->addDeposit($deposit);
        $this->assertSame(1, $this->auContainer->countDeposits());
    }

    public function testCountDepositsMultiple() : void {
        $d1 = new Deposit();
        $this->auContainer->addDeposit($d1);
        $d2 = new Deposit();
        $this->auContainer->addDeposit($d2);
        $this->assertSame(2, $this->auContainer->countDeposits());
    }

    public function setUp() : void {
        $this->auContainer = new AuContainer();
    }
}

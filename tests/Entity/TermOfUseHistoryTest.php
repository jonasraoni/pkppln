<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Entity;

use App\Entity\TermOfUseHistory;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of TermOfUseHistoryTest.
 */
class TermOfUseHistoryTest extends ControllerBaseCase {
    private $history;

    public function testToString() : void {
        $this->history->setAction('update');
        $this->assertSame('update', (string) $this->history);
    }

    public function testGetUser() : void {
        $this->history->setUser('Yoda');
        $this->assertSame('Yoda', $this->history->getUser());
    }

    protected function setup() : void {
        parent::setUp();
        $this->history = new TermOfUseHistory();
    }
}

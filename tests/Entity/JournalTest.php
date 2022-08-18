<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Entity;

use App\Entity\Journal;
use DateTime;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of JournalTest.
 */
class JournalTest extends ControllerBaseCase {
    private $journal;

    public function testInstance() : void {
        $this->assertInstanceOf(Journal::class, $this->journal);
    }

    public function testSetUuid() : void {
        $this->journal->setUuid('abc123');
        $this->assertSame('ABC123', $this->journal->getUuid());
    }

    public function testSetNotified() : void {
        $this->journal->setNotified(new DateTime());
        $this->assertInstanceOf(DateTime::class, $this->journal->getNotified());
    }

    protected function setup() : void {
        parent::setUp();
        $this->journal = new Journal();
    }
}

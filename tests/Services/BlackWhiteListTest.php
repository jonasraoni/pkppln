<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Services;

use App\DataFixtures\BlacklistFixtures;
use App\DataFixtures\WhitelistFixtures;
use App\Services\BlackWhiteList;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class BlackWhiteListTest extends ControllerBaseCase {
    /**
     * @var BlackWhiteList
     */
    protected $list;

    public function fixtures() : array {
        return [
            BlacklistFixtures::class,
            WhitelistFixtures::class,
        ];
    }

    public function testInstance() : void {
        $this->assertInstanceOf(BlackWhiteList::class, $this->list);
    }

    public function testIsWhitelisted() : void {
        $this->assertTrue($this->list->isWhitelisted(WhitelistFixtures::UUIDS[0]));
        $this->assertTrue($this->list->isWhitelisted(strtolower(WhitelistFixtures::UUIDS[0])));

        $this->assertFalse($this->list->isWhitelisted(BlacklistFixtures::UUIDS[0]));
        $this->assertFalse($this->list->isWhitelisted(strtolower(BlacklistFixtures::UUIDS[0])));
    }

    public function testIsBlacklisted() : void {
        $this->assertTrue($this->list->isBlacklisted(BlacklistFixtures::UUIDS[0]));
        $this->assertTrue($this->list->isBlacklisted(strtolower(BlacklistFixtures::UUIDS[0])));

        $this->assertFalse($this->list->isBlacklisted(WhitelistFixtures::UUIDS[0]));
        $this->assertFalse($this->list->isBlacklisted(strtolower(WhitelistFixtures::UUIDS[0])));
    }

    protected function setup() : void {
        parent::setUp();
        $this->list = self::$container->get(BlackWhiteList::class);
    }
}

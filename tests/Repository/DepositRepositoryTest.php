<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Repository;

use App\DataFixtures\DepositFixtures;
use App\DataFixtures\JournalFixtures;
use App\Entity\Deposit;
use App\Repository\DepositRepository;
use App\Repository\Repository;
use App\Tests\TestCase\BaseControllerTestCase;

/**
 * Description of DepositRepositoryTest.
 */
class DepositRepositoryTest extends BaseControllerTestCase
{
    /**
     * @return DepositRepository
     */
    private $repo;

    protected function fixtures(): array
    {
        return [
            DepositFixtures::class,
            JournalFixtures::class,
        ];
    }

    public function testSearchQueryUuid(): void
    {
        $result = $this->repo->searchQuery('A584');
        $this->assertCount(1, $result->execute());
    }

    public function testSearchQueryUrl(): void
    {
        $result = $this->repo->searchQuery('1.zip');
        $this->assertCount(1, $result->execute());
    }

    public function testSearchQueryUuidWithJournal(): void
    {
        $result = $this->repo->searchQuery('A584', $this->getReference('journal.1'));
        $this->assertCount(1, $result->execute());
    }

    public function testSearchQueryUrlWithJorunal(): void
    {
        $result = $this->repo->searchQuery('1.zip', $this->getReference('journal.1'));
        $this->assertCount(1, $result->execute());
    }

    public function testSearchQueryUuidWithOtherJournal(): void
    {
        $result = $this->repo->searchQuery('A584', $this->getReference('journal.2'));
        $this->assertCount(0, $result->execute());
    }

    public function testSearchQueryUrlWithOtherJorunal(): void
    {
        $result = $this->repo->searchQuery('1.zip', $this->getReference('journal.2'));
        $this->assertCount(0, $result->execute());
    }

    protected function setup(): void
    {
        parent::setUp();
        $this->repo = Repository::deposit();
    }
}

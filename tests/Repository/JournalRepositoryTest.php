<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Repository;

use App\DataFixtures\JournalFixtures;
use App\Entity\Blacklist;
use App\Entity\Journal;
use App\Entity\Whitelist;
use App\Repository\JournalRepository;
use App\Tests\TestCase\BaseControllerTestCase;

/**
 * Description of JournalRepositoryTest.
 */
class JournalRepositoryTest extends BaseControllerTestCase
{
    /**
     * @return JournalRepository
     */
    private $repo;

    protected function fixtures(): array
    {
        return [
            JournalFixtures::class,
        ];
    }

    public function testGetJournalsToPingNoListed(): void
    {
        $this->assertCount(4, $this->repo->getJournalsToPing());
    }

    public function testGetJournalsToPingListed(): void
    {
        $whitelist = new Whitelist();
        $whitelist->setUuid(JournalFixtures::UUIDS[0]);
        $whitelist->setComment('Test');
        $this->em->persist($whitelist);

        $blacklist = new Blacklist();
        $blacklist->setUuid(JournalFixtures::UUIDS[1]);
        $blacklist->setComment('Test');
        $this->em->persist($blacklist);

        $this->em->flush();

        $this->assertCount(2, $this->repo->getJournalsToPing());
    }

    public function testGetJournalsToPingPingErrors(): void
    {
        $journal = $this->em->find(Journal::class, 1);
        $journal->setStatus('ping-error');
        $this->em->flush();

        $this->assertCount(3, $this->repo->getJournalsToPing());
    }

    /**
     * @dataProvider searchQueryData
     */
    public function testSearchQuery(): void
    {
        $query = $this->repo->searchQuery('CDC4');
        $result = $query->execute();
        $this->assertCount(1, $result);
    }

    public function searchQueryData()
    {
        return [
            [1, 'CDC4'],
            [1, 'Title 1'],
            [1, '1234-1234'],
            [4, 'example.com'],
            [4, 'email@'],
            [4, 'PublisherName'],
            [1, 'publisher/1'],
        ];
    }

    protected function setup(): void
    {
        parent::setUp();
        $this->repo = $this->em->getRepository(Journal::class);
    }
}

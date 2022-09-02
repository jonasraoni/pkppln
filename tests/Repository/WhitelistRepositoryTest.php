<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Repository;

use App\DataFixtures\WhitelistFixtures;
use App\Entity\Whitelist;
use App\Repository\WhitelistRepository;
use App\Tests\TestCase\BaseControllerTestCase;

/**
 * Description of WhitelistRepositoryTest.
 */
class WhitelistRepositoryTest extends BaseControllerTestCase
{
    /**
     * @return WhitelistRepository
     */
    private $repo;

    protected function fixtures(): array
    {
        return [
            WhitelistFixtures::class,
        ];
    }

    public function testSearchQuery(): void
    {
        $query = $this->repo->searchQuery('960CD4D9');
        $result = $query->execute();
        $this->assertCount(1, $result);
    }

    protected function setup(): void
    {
        parent::setUp();
        $this->repo = $this->em->getRepository(Whitelist::class);
    }
}

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
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of WhitelistRepositoryTest.
 */
class WhitelistRepositoryTest extends ControllerBaseCase {
    /**
     * @return WhitelistRepository
     */
    private $repo;

    protected function fixtures() : array {
        return [
            WhitelistFixtures::class,
        ];
    }

    public function testSearchQuery() : void {
        $query = $this->repo->searchQuery('960CD4D9');
        $result = $query->execute();
        $this->assertSame(1, count($result));
    }

    protected function setup() : void {
        parent::setUp();
        $this->repo = $this->entityManager->getRepository(Whitelist::class);
    }
}

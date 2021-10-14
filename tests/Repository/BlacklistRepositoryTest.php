<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Repository;

use App\DataFixtures\BlacklistFixtures;
use App\Entity\Blacklist;
use App\Repository\BlacklistRepository;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of BlacklistRepositoryTest.
 */
class BlacklistRepositoryTest extends ControllerBaseCase {
    /**
     * @return BlacklistRepository
     */
    private $repo;

    protected function fixtures() : array {
        return [
            BlacklistFixtures::class,
        ];
    }

    public function testSearchQuery() : void {
        $query = $this->repo->searchQuery('B156FACD');
        $result = $query->execute();
        $this->assertSame(1, count($result));
    }

    protected function setup() : void {
        parent::setUp();
        $this->repo = $this->entityManager->getRepository(Blacklist::class);
    }
}

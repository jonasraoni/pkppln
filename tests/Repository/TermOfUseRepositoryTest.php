<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Repository;

use App\DataFixtures\TermOfUseFixtures;
use App\Entity\TermOfUse;
use App\Repository\TermOfUseRepository;
use Nines\UtilBundle\Tests\ControllerBaseCase;

/**
 * Description of JournalRepositoryTest.
 */
class TermOfUseRepositoryTest extends ControllerBaseCase {
    /**
     * @return TermOfUseRepository
     */
    private $repo;

    protected function fixtures() : array {
        return [
            TermOfUseFixtures::class,
        ];
    }

    public function testGetTerms() : void {
        $terms = $this->repo->getTerms();
        $this->assertSame([4, 3, 2, 1], array_map(function ($term) {return $term->getId(); }, $terms));
    }

    public function testGetLastUpdated() : void {
        $this->assertNotNull($this->repo->getLastUpdated());
    }

    protected function setup() : void {
        parent::setUp();
        $this->repo = $this->entityManager->getRepository(TermOfUse::class);
    }
}

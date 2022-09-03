<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Entity;

use App\DataFixtures\AuContainerFixtures;
use App\Repository\Repository;
use App\Tests\TestCase\BaseControllerTestCase;

class AuContainerRepositoryTest extends BaseControllerTestCase
{
    /**
     * @var AuContainer
     */
    protected $repository;

    public function testGetOpenContainer(): void
    {
        $c = $this->repository->getOpenContainer();
        $this->assertInstanceOf('App\Entity\AuContainer', $c);
        $this->assertTrue($c->isOpen());
        $this->assertSame(2, $c->getId());
    }

    public function fixtures(): array
    {
        return [
            AuContainerFixtures::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Repository::auContainer();
    }
}

<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Entity;

use App\DataFixtures\AuContainerFixtures;
use Nines\UtilBundle\Tests\ControllerBaseCase;

class AuContainerRepositoryTest extends ControllerBaseCase {
    /**
     * @var AuContainer
     */
    protected $repository;

    public function testGetOpenContainer() : void {
        $c = $this->repository->getOpenContainer();
        $this->assertInstanceOf('App\Entity\AuContainer', $c);
        $this->assertSame(true, $c->isOpen());
        $this->assertSame(2, $c->getId());
    }

    public function fixtures() : array {
        return [
            AuContainerFixtures::class,
        ];
    }

    public function setUp() : void {
        parent::setUp();
        $this->repository = $this->entityManager->getRepository('App:AuContainer');
    }
}

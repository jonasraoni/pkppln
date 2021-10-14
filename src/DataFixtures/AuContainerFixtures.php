<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\AuContainer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Load a deposit for testing.
 */
class AuContainerFixtures extends Fixture {
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager) : void {
        $c1 = new AuContainer();
        $c1->setOpen(false);
        $this->setReference('aucontainer', $c1);
        $manager->persist($c1);

        $c2 = new AuContainer();
        $manager->persist($c2);
        $c3 = new AuContainer();
        $manager->persist($c3);
        $manager->flush();
    }
}

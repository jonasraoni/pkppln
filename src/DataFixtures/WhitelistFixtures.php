<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\Whitelist;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * LoadWhitelist form.
 */
class WhitelistFixtures extends Fixture {
    // The first journal is whitelisted.
    public const UUIDS = [
        '44428B12-CDC4-453E-8157-319004CD8CE6',
        'E8F084C6-F932-43C0-8B77-B6E8BA9EDF6F',
        '960CD4D9-C4DD-4E47-96ED-532306DE7DBD',
        '930FAF91-7E65-4A61-A589-8D220B686F84',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $em) : void {
        for ($i = 0; $i < 4; $i++) {
            $fixture = new Whitelist();
            $fixture->setUuid(self::UUIDS[$i]);
            $fixture->setComment('Comment ' . $i);

            $em->persist($fixture);
            $this->setReference('whitelist.' . $i, $fixture);
        }

        $em->flush();
    }
}

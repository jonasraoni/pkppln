<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\DataFixtures;

use App\Entity\Blacklist;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * LoadBlacklist form.
 */
class BlacklistFixtures extends Fixture {
    public const UUIDS = [
        'AC54ED1A-9795-4EED-94FD-D80CB62E0C84',
        'B156FACD-5210-4111-B4C2-D5C0C348D93A',
        '2DE4DC03-3E02-43D3-A088-E7536743C083',
        'A13C33E6-CDC4-4D09-BB62-1BE3B0E74A0A',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $em) : void {
        for ($i = 0; $i < 4; $i++) {
            $fixture = new Blacklist();
            $fixture->setUuid(self::UUIDS[$i]);
            $fixture->setComment('Comment ' . $i);

            $em->persist($fixture);
            $this->setReference('blacklist.' . $i, $fixture);
        }

        $em->flush();
    }
}

<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Repository;

use App\Entity\TermOfUse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Custom doctrine queries for terms of use.
 */
class TermOfUseRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, TermOfUse::class);
    }

    /**
     * Get the terms of use, sorted by weight.
     *
     * @return Collection|TermOfUse[]
     *                                The terms of use.
     */
    public function getTerms() {
        return $this->findBy([], [
            'weight' => 'ASC',
        ]);
    }

    /**
     * Get the date of the most recent update to the terms of use.
     *
     * @return string
     */
    public function getLastUpdated() {
        $qb = $this->createQueryBuilder('t');
        $qb->select('MAX(t.updated)');

        return $qb->getQuery()->getSingleScalarResult();
    }
}

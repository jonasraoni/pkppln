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
use Doctrine\Persistence\ManagerRegistry;

/**
 * Custom doctrine queries for terms of use.
 * @extends ServiceEntityRepository<TermOfUse>
 */
class TermOfUseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TermOfUse::class);
    }

    /**
     * Get the terms of use, sorted by weight.
     *
     * @return TermOfUse[]
     *                                The terms of use.
     */
    public function getTerms(): array
    {
        return $this->findBy([], [
            'weight' => 'ASC',
        ]);
    }

    /**
     * Get the date of the most recent update to the terms of use.
     */
    public function getLastUpdated(): string
    {
        $qb = $this->createQueryBuilder('t');
        $qb->select('MAX(t.updated)');

        return (string) $qb->getQuery()->getSingleScalarResult();
    }
}

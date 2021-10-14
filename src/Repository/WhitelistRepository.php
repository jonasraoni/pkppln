<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Repository;

use App\Entity\Whitelist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Whitelist repository for custom doctrine queries.
 */
class WhitelistRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Whitelist::class);
    }

    /**
     * Build a query to search for whitelist entries.
     *
     * @param string $q
     *
     * @return Query
     */
    public function searchQuery($q) {
        $qb = $this->createQueryBuilder('b');
        $qb->where('CONCAT(b.uuid, \' \', b.comment) LIKE :q');
        $qb->setParameter('q', '%' . $q . '%');

        return $qb->getQuery();
    }
}

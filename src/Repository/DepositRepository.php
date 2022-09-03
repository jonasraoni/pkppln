<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Repository;

use App\Entity\Deposit;
use App\Entity\Journal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Custom doctrine queries for deposits.
 */
class DepositRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deposit::class);
    }

    /**
     * Create a search query and return it.
     *
     * The query isn't executed here.
     */
    public function searchQuery(string $q, Journal $journal = null): Query
    {
        $qb = $this->createQueryBuilder('d');
        $qb->where('CONCAT(d.depositUuid, d.url) LIKE :q');
        $qb->setParameter('q', '%' . $q . '%');
        if ($journal) {
            $qb->andWhere('d.journal = :journal');
            $qb->setParameter('journal', $journal);
        }

        return $qb->getQuery();
    }

    /**
     * Summarize deposits by counting them by state.
     */
    public function stateSummary(): array
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('e.state, count(e) as ct')
            ->groupBy('e.state')
            ->orderBy('e.state');

        return $qb->getQuery()->getResult();
    }

    /**
     * Return some recent deposits.
     *
     * @return Deposit[]
     */
    public function findNew(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('d');
        $qb->orderBy('d.id', 'DESC');
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}

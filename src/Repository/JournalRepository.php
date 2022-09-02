<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Repository;

use App\Entity\Blacklist;
use App\Entity\Journal;
use App\Entity\Whitelist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Custom journal queries for doctrine.
 */
class JournalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Journal::class);
    }

    /**
     * Get a list of journals that need to be pinged.
     *
     * @return Collection|Journal[]
     *                              List of journals.
     */
    public function getJournalsToPing(): array
    {
        $blacklist = $this->getEntityManager()->getRepository(Blacklist::class)
            ->createQueryBuilder('bl')
            ->select('bl.uuid')
        ;

        $whitelist = $this->getEntityManager()->getRepository(Whitelist::class)
            ->createQueryBuilder('wl')
            ->select('wl.uuid')
        ;

        $qb = $this->createQueryBuilder('j');
        $qb->andWhere('j.status != :status');
        $qb->setParameter('status', 'ping-error');
        $qb->andWhere($qb->expr()->notIn('j.uuid', $blacklist->getDQL()));
        $qb->andWhere($qb->expr()->notIn('j.uuid', $whitelist->getDQL()));

        return $qb->getQuery()->execute();
    }

    /**
     * Build a query to search for journals.
     *
     * Search is based on uuid, title, issn, url, email, publisher name, and
     * publisher url.
     */
    public function searchQuery(string $q): Query
    {
        $qb = $this->createQueryBuilder('j');
        $qb->where('CONCAT(j.uuid, j.title, j.issn, j.url, j.email, j.publisherName, j.publisherUrl) LIKE :q');
        $qb->setParameter('q', '%' . $q . '%');

        return $qb->getQuery();
    }

    /**
     * Summarize the journal statuses, counting them by status.
     */
    public function statusSummary(): array
    {
        $qb = $this->createQueryBuilder('e');
        $qb->select('e.status, count(e) as ct')
            ->groupBy('e.status')
            ->orderBy('e.status')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * Find journals that haven't contacted the PLN in $days.
     *
     * @return Collection|Journal[]
     */
    public function findSilent(int $days): array
    {
        $dt = new DateTime("-{$days} day");

        $qb = $this->createQueryBuilder('e');
        $qb->andWhere('e.contacted < :dt');
        $qb->setParameter('dt', $dt);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find journals that have gone silent.
     *
     * Excludes journals wehre notifications have been sent.
     *
     * @return Collection|Journal[]
     */
    public function findOverdue(int $days): array
    {
        $dt = new DateTime("-{$days} day");
        $qb = $this->createQueryBuilder('e');
        $qb->Where('e.notified < :dt');
        $qb->setParameter('dt', $dt);

        return $qb->getQUery()->getResult();
    }

    /**
     * Find the $limit most recent journals to contact the PLN for the first time.
     *
     * @todo This method should be called findRecent(). It does not find
     * journals with status=new
     *
     * @return Collection|Journal[]
     */
    public function findNew(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('e');
        $qb->orderBy('e.id', 'DESC');
        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}

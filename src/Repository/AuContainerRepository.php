<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Repository;

use App\Entity\AuContainer;
use App\Entity\Deposit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * AuContainerRepository makes it easy to find AuContainers.
 */
class AuContainerRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, AuContainer::class);
    }

    /**
     * Find the open container with the lowest database ID. There should only
     * ever be one open container, but finding the one with lowest database ID
     * guarantees it.
     *
     * @return AuContainer|object
     */
    public function getOpenContainer() {
        return $this->findOneBy(
            ['open' => true],
            ['id' => 'ASC']
        );
    }

    public function getSizes() {
        $qb = $this->_em->createQueryBuilder()
            ->from(Deposit::class, 'd')
            ->select('identity(d.auContainer) as acid, sum(d.size) as size')
            ->groupBy('acid')
        ;

        return $qb->getQuery()->execute();
    }
}

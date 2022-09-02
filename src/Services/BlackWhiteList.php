<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Services;

use App\Entity\Blacklist;
use App\Entity\Whitelist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Description of BlackWhiteList.
 */
class BlackWhiteList
{
    /**
     * Entity manager.
     */
    private EntityManagerInterface $em;

    /**
     * Build the service.
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Get an entry for the UUID.
     */
    private function getEntry(ServiceEntityRepository $repo, string $uuid)
    {
        return null !== $repo->findOneBy(['uuid' => strtoupper($uuid)]);
    }

    /**
     * Return true if the uuid is whitelisted.
     */
    public function isWhitelisted(string $uuid): bool
    {
        $repo = $this->em->getRepository(Whitelist::class);

        return $this->getEntry($repo, $uuid);
    }

    /**
     * Return true if the uuid is blacklisted.
     */
    public function isBlacklisted(string $uuid): bool
    {
        $repo = $this->em->getRepository(Blacklist::class);

        return $this->getEntry($repo, $uuid);
    }

    /**
     * Check if a journal is whitelisted or blacklisted.
     */
    public function isListed(string $uuid): bool
    {
        return $this->isWhitelisted($uuid) || $this->isBlacklisted($uuid);
    }
}

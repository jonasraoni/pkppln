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
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Description of BlackWhiteList.
 */
class BlackWhiteList {
    /**
     * Entity manager.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Build the service.
     */
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    /**
     * Get an entry for the UUID.
     *
     * @param string $uuid
     */
    private function getEntry(ObjectRepository $repo, $uuid) {
        return null !== $repo->findOneBy(['uuid' => strtoupper($uuid)]);
    }

    /**
     * Return true if the uuid is whitelisted.
     *
     * @param string $uuid
     *
     * @return bool
     */
    public function isWhitelisted($uuid) {
        $repo = $this->em->getRepository(Whitelist::class);

        return $this->getEntry($repo, $uuid);
    }

    /**
     * Return true if the uuid is blacklisted.
     *
     * @param string $uuid
     *
     * @return bool
     */
    public function isBlacklisted($uuid) {
        $repo = $this->em->getRepository(Blacklist::class);

        return $this->getEntry($repo, $uuid);
    }

    /**
     * Check if a journal is whitelisted or blacklisted.
     *
     * @param mixed $uuid
     */
    public function isListed($uuid) {
        return $this->isWhitelisted($uuid) || $this->isBlacklisted($uuid);
    }
}

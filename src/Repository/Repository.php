<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Repository;

use App\Entity\AuContainer;
use App\Entity\Blacklist;
use App\Entity\Deposit;
use App\Entity\Document;
use App\Entity\Journal;
use App\Entity\TermOfUse;
use App\Entity\TermOfUseHistory;
use App\Entity\Whitelist;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Nines\UserBundle\Entity\User;
use Nines\UserBundle\Repository\UserRepository;

/**
 * Repository makes it easy to grab typed repositories.
 */
class Repository
{
    /**
     * @template T of \Nines\UtilBundle\Entity\AbstractEntity
     * @param class-string<T> $className
     * @return EntityRepository<T>
     */
    public static function getRepository(string $className): EntityRepository
    {
        $em = Kernel::getInstance()->getContainer()->get('doctrine.orm.entity_manager');
        \assert($em instanceof EntityManagerInterface);
        return $em->getRepository($className);
    }

    /**
     * Retrieves a AuContainer repository
     */
    public static function auContainer(): AuContainerRepository
    {
        $repo = self::getRepository(AuContainer::class);
        \assert($repo instanceof AuContainerRepository);
        return $repo;
    }

    /**
     * Retrieves a Blacklist repository
     */
    public static function blacklist(): BlacklistRepository
    {
        $repo = self::getRepository(Blacklist::class);
        \assert($repo instanceof BlacklistRepository);
        return $repo;
    }

    /**
     * Retrieves a Deposit repository
     */
    public static function deposit(): DepositRepository
    {
        $repo = self::getRepository(Deposit::class);
        \assert($repo instanceof DepositRepository);
        return $repo;
    }

    /**
     * Retrieves a Document repository
     */
    public static function document(): DocumentRepository
    {
        $repo = self::getRepository(Document::class);
        \assert($repo instanceof DocumentRepository);
        return $repo;
    }

    /**
     * Retrieves a Journal repository
     */
    public static function journal(): JournalRepository
    {
        $repo = self::getRepository(Journal::class);
        \assert($repo instanceof JournalRepository);
        return $repo;
    }

    /**
     * Retrieves a TermOfUseHistory repository
     */
    public static function termOfUseHistory(): TermOfUseHistoryRepository
    {
        $repo = self::getRepository(TermOfUseHistory::class);
        \assert($repo instanceof TermOfUseHistoryRepository);
        return $repo;
    }

    /**
     * Retrieves a TermOfUse repository
     */
    public static function termOfUse(): TermOfUseRepository
    {
        $repo = self::getRepository(TermOfUse::class);
        \assert($repo instanceof TermOfUseRepository);
        return $repo;
    }

    /**
     * Retrieves a User repository
     */
    public static function user(): UserRepository
    {
        $repo = self::getRepository(User::class);
        \assert($repo instanceof UserRepository);
        return $repo;
    }

    /**
     * Retrieves a Whitelist repository
     */
    public static function whitelist(): WhitelistRepository
    {
        $repo = self::getRepository(Whitelist::class);
        \assert($repo instanceof WhitelistRepository);
        return $repo;
    }
}

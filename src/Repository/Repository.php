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
    public static function getRepository(string $className): EntityRepository
    {
        static $doctrine = null;
        $doctrine ??= Kernel::getInstance()->getContainer()->get('doctrine.orm.entity_manager');
        /** @var EntityManagerInterface $doctrine */
        return $doctrine->getRepository($className);
    }

    public static function AuContainer(): AuContainerRepository
    {
        $repository = self::getRepository(AuContainer::class);
        assert($repository instanceof AuContainer);
        return $repository;
    }

    public static function Blacklist(): BlacklistRepository
    {
        $repository = self::getRepository(Blacklist::class);
        assert($repository instanceof Blacklist);
        return $repository;
    }

    public static function Deposit(): DepositRepository
    {
        $repository = self::getRepository(Deposit::class);
        assert($repository instanceof Deposit);
        return $repository;
    }

    public static function Document(): DocumentRepository
    {
        $repository = self::getRepository(Document::class);
        assert($repository instanceof Document);
        return $repository;
    }

    public static function Journal(): JournalRepository
    {
        $repository = self::getRepository(Journal::class);
        assert($repository instanceof Journal);
        return $repository;
    }

    public static function TermOfUseHistory(): TermOfUseHistoryRepository
    {
        $repository = self::getRepository(TermOfUseHistory::class);
        assert($repository instanceof TermOfUseHistory);
        return $repository;
    }

    public static function TermOfUse(): TermOfUseRepository
    {
        $repository = self::getRepository(TermOfUse::class);
        assert($repository instanceof TermOfUse);
        return $repository;
    }

    public static function User(): UserRepository
    {
        $repository = self::getRepository(User::class);
        assert($repository instanceof User);
        return $repository;
    }

    public static function Whitelist(): WhitelistRepository
    {
        $repository = self::getRepository(Whitelist::class);
        assert($repository instanceof Whitelist);
        return $repository;
    }
}

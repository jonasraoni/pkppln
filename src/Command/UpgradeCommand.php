<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command;

use App\Entity\AuContainer;
use App\Entity\Blacklist;
use App\Entity\Deposit;
use App\Entity\Document;
use App\Entity\Journal;
use App\Entity\TermOfUse;
use App\Entity\TermOfUseHistory;
use App\Entity\Whitelist;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nines\UserBundle\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Upgrade a PKP PLN instance from version 1 to version 2.
 */
class UpgradeCommand extends Command {
    /**
     * Doctrine connection to the old database.
     *
     * @var Connection
     */
    private $source;

    /**
     * Entity manager for the new database.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Mapping of old IDs to new IDs based on class names.
     *
     * Something like this if the old user ID was three and the new one was 5.
     * $idMapping[User::class][3] = 5
     *
     * @var array
     */
    private $idMapping;

    /**
     * If true the changes will be flushed to the new database.
     *
     * @var bool
     */
    private $force;

    /**
     * Construct the command instance.
     *
     * $oldEm is a Doctrine connection configured for the previous version
     * of the database. $em is an entity manager configured for the current
     * version.
     *
     * This file and the corresponding configuration should both be removed
     * after the upgrade is complete.
     *
     * see app/config/config.yml for examples of the configuration.
     * see app/config/services.yml to configure the dependency injection.
     */
    public function __construct(Connection $oldEm, EntityManagerInterface $em) {
        parent::__construct();
        $this->source = $oldEm;
        $this->em = $em;
        $this->idMapping = [];
        $this->force = false;
        gc_enable();
    }

    /**
     * Map an old database ID to a new one.
     *
     * @param string $class
     * @param int $old
     * @param int $new
     */
    protected function setIdMap($class, $old, $new) : void {
        $this->idMapping[$class][$old] = $new;
    }

    /**
     * Get the new database ID for a $class.
     *
     * @param string $class
     * @param int $old
     * @param int $default
     *
     * @return null|int
     */
    protected function getIdMap($class, $old, $default = null) {
        if (isset($this->idMapping[$class][$old])) {
            return $this->idMapping[$class][$old];
        }

        return $default;
    }

    /**
     * Configure the command.
     */
    public function configure() : void {
        $this->setName('pln:upgrade');
        $this->setDescription('Copy and upgrade data from old database to new one.');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Actually make the database changes.');
    }

    /**
     * Perform an upgrade on one table.
     *
     * Processes each row of the table with $callback. If $callback returns an
     * object it is persisted and flushed, and the old ID is mapped to the
     * new one.
     *
     * @param string $table
     */
    public function upgradeTable($table, callable $callback) : void {
        $countQuery = $this->source->query("SELECT count(*) c FROM {$table}");
        $countQuery->execute();
        $countRow = $countQuery->fetch();
        echo "upgrading {$countRow['c']} entities in {$table}.\n";

        $query = $this->source->query("SELECT * FROM {$table}");
        $n = 0;
        $query->execute();
        echo "{$n}\r";
        while ($row = $query->fetch()) {
            $entity = $callback($row);
            if ($entity) {
                $this->em->persist($entity);
                $this->em->flush();
                $this->em->clear();
                $this->setIdMap(get_class($entity), $row['id'], $entity->getId());
                $this->em->detach($entity);
            }
            $n++;
            echo "{$n}\r";
        }
        echo "\n";
    }

    /**
     * Upgrade the whitelist table.
     */
    public function upgradeWhitelist() : void {
        $callback = function ($row) {
            $entry = new Whitelist();
            $entry->setComment($row['comment']);
            $entry->setUuid($row['uuid']);
            $entry->setCreated(new DateTime($row['created']));

            return $entry;
        };
        $this->upgradeTable('whitelist', $callback);
    }

    /**
     * Upgrade the blacklist table.
     */
    public function upgradeBlacklist() : void {
        $callback = function ($row) {
            $entry = new Blacklist();
            $entry->setComment($row['comment']);
            $entry->setUuid($row['uuid']);
            $entry->setCreated(new DateTime($row['created']));

            return $entry;
        };
        $this->upgradeTable('blacklist', $callback);
    }

    /**
     * Upgrade the users table.
     */
    public function upgradeUsers() : void {
        $callback = function ($row) {
            $entry = new User();
            $entry->setEmail($row['username']);
            $entry->setActive($row['enabled'] == 1);
            $entry->setPassword($row['password']);
            $entry->setRoles(unserialize($row['roles']));
            $entry->setFullname($row['fullname']);
            $entry->setAffiliation($row['institution']);

            return $entry;
        };
        $this->upgradeTable('appuser', $callback);
    }

    /**
     * Upgrade the terms of use.
     *
     * Term history is upgraded elsewhere.
     */
    public function upgradeTerms() : void {
        $callback = function ($row) {
            $term = new TermOfUse();
            $term->setWeight($row['weight']);
            $term->setKeyCode($row['key_code']);
            $term->setContent($row['content']);
            $term->setCreated(new DateTime($row['created']));
            $term->setUpdated(new DateTime($row['updated']));

            return $term;
        };
        $this->upgradeTable('term_of_use', $callback);
    }

    /**
     * Upgrade the terms of use history.
     *
     * Terms of Use must be upgraded first.
     */
    public function upgradeTermHistory() : void {
        $callback = function ($row) {
            $history = new TermOfUseHistory();
            $termId = $this->getIdMap(TermOfUse::class, $row['term_id'], $row['term_id']);
            $history->setTermId($termId);
            $history->setAction($row['action']);
            $history->setUser($row['user']);
            $history->setChangeSet(unserialize($row['change_set']));
            $history->setCreated(new DateTime($row['created']));
            $history->setUpdated(new DateTime($row['created']));

            return $history;
        };
        $this->upgradeTable('term_of_use_history', $callback);
    }

    /**
     * Upgrade the journal table.
     */
    public function upgradeJournals() : void {
        $callback = function ($row) {
            $journal = new Journal();
            $journal->setUuid($row['uuid']);
            $journal->setContacted(new DateTime($row['contacted']));
            $journal->setTermsAccepted($row['terms_accepted']);
            if ($row['notified']) {
                $journal->setNotified(new DateTime($row['notified']));
            }
            $journal->setTitle($row['title']);
            if ('unknown' !== $row['issn']) {
                $journal->setIssn($row['issn']);
            }
            $journal->setUrl($row['url']);
            $journal->setStatus($row['status']);
            if ('unknown@unknown.com' !== $row['email']) {
                $journal->setEmail($row['email']);
            }
            if ($row['publisher_name']) {
                $journal->setPublisherName($row['publisher_name']);
            }
            if ($row['publisher_url']) {
                $journal->setPublisherUrl($row['publisher_url']);
            }
            if ($row['ojs_version']) {
                $journal->setOjsVersion($row['ojs_version']);
            }

            return $journal;
        };
        $this->upgradeTable('journal', $callback);
    }

    /**
     * Upgrade the deposit table.
     *
     * Journals must be upgraded first.
     *
     * @throws Exception
     *                   If a deposit came from a journal that cannot be found.
     */
    public function upgradeDeposits() : void {
        $callback = function ($row) {
            $deposit = new Deposit();

            $journalId = $this->getIdMap(Journal::class, $row['journal_id']);
            if ( ! $journalId) {
                throw new Exception('No ID for journal: ' . $row['journal_id']);
            }
            $journal = $this->em->find(Journal::class, $journalId);
            if ( ! $journal) {
                throw new Exception("Journal {$row['journal_id']} not found.");
            }
            $deposit->setJournal($journal);

            $auContainerId = $this->getIdMap(AuContainer::class, $row['au_container_id']);
            if ($auContainerId) {
                $auContainer = $this->em->find(AuContainer::class, $auContainerId);
                if ( ! $auContainer) {
                    throw new Exception("Cannot find au container {$row['au_container_id']} for deposit {$row['id']}.");
                }
                $deposit->setAuContainer($auContainer);
            }

            if ($row['file_type']) {
                $deposit->setFileType($row['file_type']);
            }
            $deposit->setDepositUuid($row['deposit_uuid']);
            $deposit->setCreated(new DateTime($row['received']));
            $deposit->setAction($row['action']);
            $deposit->setVolume($row['volume']);
            $deposit->setIssue($row['issue']);
            $deposit->setPubDate(new DateTime($row['pub_date']));
            $deposit->setChecksumType($row['checksum_type']);
            $deposit->setChecksumValue($row['checksum_value']);
            $deposit->setUrl($row['url']);
            $deposit->setSize($row['size']);
            $deposit->setState($row['state']);
            $deposit->setPlnState($row['pln_state']);
            $deposit->setPackageSize($row['package_size']);
            $deposit->setPackageChecksumType($row['package_checksum_type']);
            $deposit->setPackageChecksumValue($row['package_checksum_value']);
            if ($row['deposit_date']) {
                $deposit->setDepositDate(new DateTime($row['deposit_date']));
            }
            if ( ! preg_match('|^http://pkp-pln|', $row['deposit_receipt'])) {
                $deposit->setDepositReceipt($row['deposit_receipt']);
            }
            $deposit->setProcessingLog($row['processing_log']);
            $deposit->setLicense(unserialize($row['license']));
            $deposit->setErrorLog(unserialize($row['error_log']));
            $deposit->setHarvestAttempts($row['harvest_attempts']);
            $deposit->setJournalVersion($row['journal_version']);

            return $deposit;
        };
        $this->upgradeTable('deposit', $callback);
    }

    /**
     * Upgrade the documents table.
     */
    public function upgradeDocuments() : void {
        $callback = function ($row) {
            $document = new Document();
            $document->setTitle($row['title']);
            $document->setPath($row['path']);
            $document->setSummary($row['summary']);
            $document->setContent($row['content']);

            return $document;
        };
        $this->upgradeTable('document', $callback);
    }

    public function upgradeAuContainers() : void {
        $callback = function ($row) {
            $auContainer = new AuContainer();
            $auContainer->setOpen($row['open']);

            return $auContainer;
        };
        $this->upgradeTable('au_container', $callback);
    }

    /**
     * Execute the command.
     *
     * Does all of the upgrades in an appropriate order.
     *
     * @throws Exception
     *                   If an error occurred.
     */
    public function execute(InputInterface $input, OutputInterface $output) : void {
        if ( ! $input->getOption('force')) {
            $output->writeln('Will not run without --force.');
            exit;
        }
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $this->source->getConfiguration()->setSQLLogger(null);

        $this->upgradeDocuments();
        $this->upgradeWhitelist();
        $this->upgradeBlacklist();
        $this->upgradeUsers();
        $this->upgradeTerms();
        $this->upgradeTermHistory();
        $this->upgradeJournals();
        $this->upgradeAuContainers();
        $this->upgradeDeposits();
    }
}

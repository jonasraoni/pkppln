<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Shell;

use App\Entity\Deposit;
use App\Entity\Journal;
use App\Repository\Repository;
use App\Services\FilePaths;
use App\Services\SwordClient;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Fetch all the content of one or more journals from LOCKSS via LOCKSSOMatic.
 */
class FetchContentCommand extends Command
{
    use LoggerAwareTrait;

    protected EntityManagerInterface $em;
    protected Filesystem $fs;
    protected FilePaths $filePaths;
    private SwordClient $swordClient;
    private ?Client $httpClient = null;

    /**
     * Initialize the command.
     */
    public function __construct(LoggerInterface $logger, EntityManagerInterface $em, FilePaths $filePaths, SwordClient $swordClient)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->em = $em;
        $this->filePaths = $filePaths;
        $this->swordClient = $swordClient;
        $this->fs = new Filesystem();
    }

    /**
     * Configure the command.
     */
    public function configure(): void
    {
        $this->setName('pn:fetch');
        $this->setDescription('Download the archived content for one or more journals.');
        $this->addArgument('journals', InputArgument::IS_ARRAY, 'The database ID of one or more journals.');
    }

    /**
     * Set the HTTP client for contacting LOCKSSOMatic.
     */
    public function setHttpClient(Client $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Build and configure and return an HTTP client. Uses the client set
     * from setHttpClient() if available.
     */
    public function getHttpClient(): Client
    {
        return $this->httpClient ??= new Client(['verify' => false, 'connect_timeout' => 15]);
    }

    /**
     * Fetch one deposit from LOCKSSOMatic.
     */
    public function fetch(Deposit $deposit, string $href): void
    {
        $client = $this->getHttpClient();
        $filepath = $this->filePaths->getRestoreDir($deposit->getJournal()) . '/' . basename($href);
        $this->logger?->notice("Saving {$deposit->getJournal()->getTitle()} vol. {$deposit->getVolume()} no. {$deposit->getIssue()} to {$filepath}");

        try {
            $client->get($href, [
                'allow_redirects' => false,
                'decode_content' => false,
                'save_to' => $filepath,
            ]);
            $hash = strtoupper(hash_file($deposit->getPackageChecksumType() ?: throw new Exception('Invalid checksum type'), $filepath));
            if ($hash !== $deposit->getPackageChecksumValue()) {
                $this->logger?->warning("Package checksum failed. Expected {$deposit->getPackageChecksumValue()} but got {$hash}");
            }
        } catch (Exception $ex) {
            $this->logger?->error($ex->getMessage());
        }
    }

    /**
     * Download all the content from one journal.
     *
     * Requests a SWORD deposit statement from LOCKSSOMatic, and uses the
     * sword:originalDeposit element to fetch the content.
     */
    public function downloadJournal(Journal $journal): void
    {
        foreach ($journal->getDeposits() as $deposit) {
            $originals = $this->swordClient->statement($deposit)->xpath('//sword:originalDeposit');
            \assert(is_iterable($originals));
            foreach ($originals as $element) {
                $this->fetch($deposit, (string) $element['href']);
            }
        }
    }

    /**
     * Get a list of journals to download.
     *
     * @param int[] $journalIds
     *
     * @return Journal[]
     */
    public function getJournals(array $journalIds): array
    {
        return Repository::journal()->findBy(['id' => $journalIds]);
    }

    /**
     * Execute the command.
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $journalIds = $input->getArgument('journals');
        $journals = $this->getJournals($journalIds);
        foreach ($journals as $journal) {
            $this->downloadJournal($journal);
        }
        return 0;
    }
}

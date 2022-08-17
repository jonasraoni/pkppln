<?php

/*
 * Copyright (C) 2015-2016 Michael Joyce <ubermichael@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AppBundle\Command\Shell;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Journal;
use AppBundle\Services\FilePaths;
use AppBundle\Services\SwordClient;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Collection;
use Exception;
use GuzzleHttp\Client;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Fetch all the content of one or more journals from LOCKSS via LOCKSSOMatic.
 */
class FetchContentCommand extends ContainerAwareCommand
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Registry
     */
    protected $em;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var FilePaths
     */
    protected $filePaths;

    /**
     * @var SwordClient
     */
    private $swordClient;

    /**
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Set the service container, and initialize the command.
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->logger = $container->get('monolog.logger.processing');
        $this->em = $container->get('doctrine')->getManager();
        $this->filePaths = $container->get('filepaths');
        $this->swordClient = $container->get('sword_client');
        $this->fs = new Filesystem();
    }

    /**
     * Configure the command.
     */
    public function configure()
    {
        $this->setName('pln:fetch');
        $this->setDescription('Download the archived content for one or more journals.');
        $this->addArgument('journals', InputArgument::IS_ARRAY, 'The database ID of one or more journals.');
    }

    /**
     * Set the HTTP client for contacting LOCKSSOMatic.
     *
     * @param Client $httpClient
     */
    public function setHttpClient(Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Build and configure and return an HTTP client. Uses the client set
     * from setHttpClient() if available.
     *
     * @return Client
     */
    public function getHttpClient()
    {
        if (!$this->httpClient) {
            $this->httpClient = new Client(['defaults' => ['verify' => false, 'connect_timeout' => 15]]);
        }

        return $this->httpClient;
    }

    /**
     * Fetch one deposit from LOCKSSOMatic.
     *
     * @param Deposit $deposit
     * @param string  $href
     */
    public function fetch(Deposit $deposit, $href)
    {
        $client = $this->getHttpClient();
        $filepath = $this->filePaths->getRestoreDir($deposit->getJournal()).'/'.basename($href);
        $this->logger->notice("Saving {$deposit->getJournal()->getTitle()} vol. {$deposit->getVolume()} no. {$deposit->getIssue()} to {$filepath}");
        try {
            $client->get($href, array(
                'allow_redirects' => false,
                'decode_content' => false,
                'save_to' => $filepath,
            ));
            $hash = strtoupper(hash_file($deposit->getPackageChecksumType(), $filepath));
            if ($hash !== $deposit->getPackageChecksumValue()) {
                $this->logger->warning("Package checksum failed. Expected {$deposit->getPackageChecksumValue()} but got {$hash}");
            }
        } catch (Exception $ex) {
            $this->logger->error($ex->getMessage());
        }
    }

    /**
     * Download all the content from one journal.
     *
     * Requests a SWORD deposit statement from LOCKSSOMatic, and uses the
     * sword:originalDeposit element to fetch the content.
     *
     * @param Journal $journal
     */
    public function downloadJournal(Journal $journal)
    {
        foreach ($journal->getDeposits() as $deposit) {
            $statement = $this->swordClient->statement($deposit);
            $originals = $statement->xpath('//sword:originalDeposit');

            foreach ($originals as $element) {
                $this->fetch($deposit, $element['href']);
            }
        }
    }

    /**
     * Get a list of journals to download.
     *
     * @param array $journalIds
     *
     * @return Collection|Journal[]
     */
    public function getJournals($journalIds)
    {
        return $this->em->getRepository('AppBundle:Journal')->findBy(array('id' => $journalIds));
    }

    /**
     * Execute the command.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $journalIds = $input->getArgument('journals');
        $journals = $this->getJournals($journalIds);
        foreach ($journals as $journal) {
            $this->downloadJournal($journal);
        }
    }
}

<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Shell;

use App\Entity\Deposit;
use App\Services\FilePaths;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Clean completed deposits from the file system.
 */
class CleanupCommand extends Command {
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Registry
     */
    protected $em;

    /**
     * @var FilePaths
     */
    protected $filePaths;

    /**
     * {@inheritdoc}
     */
    public function __construct(LoggerInterface $logger, EntityManagerInterface $em, FilePaths $filePaths) {
        parent::__construct();
        $this->logger = $logger;
        $this->em = $em;
        $this->filePaths = $filePaths;
    }

    /**
     * Remove a directory and its contents recursively. Use with caution.
     *
     * @param mixed $path
     * @param mixed $force
     */
    private function delFileTree($path, $force = false) : void {
        if ( ! file_exists($path)) {
            return;
        }
        $this->logger->notice("Cleaning {$path}");
        if ( ! is_dir($path)) {
            if (file_exists($path) && true === $force) {
                unlink($path);
            }

            return;
        }
        $directoryIterator = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $fileIterator = new RecursiveIteratorIterator($directoryIterator, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($fileIterator as $file) {
            if ($file->isDir()) {
                if (true === $force) {
                    rmdir($file->getRealPath());
                }
            } else {
                if (true === $force) {
                    unlink($file->getRealPath());
                }
            }
        }
        if (true === $force) {
            rmdir($path);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configure() : void {
        $this->setName('pln:clean');
        $this->setDescription('Clean processed deposits from the data directory.');
        $this->addOption('force', '-f', InputOption::VALUE_NONE, 'Delete files.');
    }

    /**
     * Process one deposit.
     *
     * @param mixed $force
     */
    protected function processDeposit(Deposit $deposit, $force = false) : void {
        if ('agreement' === $deposit->getPlnState()) {
            $this->delFileTree($this->filePaths->getHarvestFile($deposit), $force);
            $this->delFileTree($this->filePaths->getProcessingBagPath($deposit), $force);
            $this->delFileTree($this->filePaths->getStagingBagPath($deposit), $force);
        }
    }

    /**
     * Execute the command.
     */
    final protected function execute(InputInterface $input, OutputInterface $output) : void {
        $force = $input->getOption('force');
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
        $q = $this->em->createQuery('SELECT d FROM App\Entity\Deposit d where d.plnState = :state');
        $q->setParameter('state', 'agreement');
        $iterator = $q->iterate();

        $i = 0;
        foreach ($iterator as $row) {
            $deposit = $row[0];
            $this->processDeposit($deposit, $force);
            $i++;
            if (0 === ($i % 10)) {
                $this->em->flush();
                $this->em->clear();
                gc_collect_cycles();
            }
        }
    }
}

<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Processing;

use App\Entity\Deposit;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Parent class for all processing commands.
 */
abstract class AbstractProcessingCmd extends Command {
    use LoggerAwareTrait;
    private EntityManagerInterface $em;

    /**
     * Build the command.
     */
    public function __construct(EntityManagerInterface $em) {
        parent::__construct();
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure() : void {
        $this->addOption('retry', 'r', InputOption::VALUE_NONE, 'Retry failed deposits');
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Do not update processing status');
        $this->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Only process $limit deposits.');
        $this->addArgument('deposit-id', InputArgument::IS_ARRAY, 'One or more deposit database IDs to process');
    }

    /**
     * Preprocess the list of deposits.
     *
     * @param Deposit[] $deposits
     */
    protected function preprocessDeposits(array $deposits = []) : void {
        // Do nothing by default.
    }

    /**
     * Process one deposit return true on success and false on failure.
     */
    abstract protected function processDeposit(Deposit $deposit): null|bool|string;

    /**
     * Code to run before executing the command.
     */
    protected function preExecute() : void {
        // Do nothing, let subclasses override if needed.
    }

    /**
     * {@inheritdoc}
     */
    final protected function execute(InputInterface $input, OutputInterface $output) : void {
        $this->preExecute();
        $deposits = $this->getDeposits(
            $input->getOption('retry'),
            $input->getArgument('deposit-id'),
            $input->getOption('limit')
        );

        $this->preprocessDeposits($deposits);
        foreach ($deposits as $deposit) {
            $this->runDeposit($deposit, $output, $input->getOption('dry-run'));
        }
    }

    /**
     * Deposits in this state will be processed by the commands.
     */
    abstract public function processingState(): string;

    /**
     * Successfully processed deposits will be given this state.
     */
    abstract public function nextState(): string;

    /**
     * Deposits which generate errors will be given this state.
     */
    abstract public function errorState(): string;

    /**
     * Successfully processed deposits will be given this log message.
     */
    abstract public function successLogMessage(): string;

    /**
     * Failed deposits will be given this log message.
     */
    abstract public function failureLogMessage(): string;

    /**
     * Code to run after each successfully deposit is saved to the database.
     */
    protected function afterSuccess(Deposit $deposit): void {
        // do nothing, let subclasses override if needed.
    }

    /**
     * Code to run after each failed deposit is saved to the database.
     */
    protected function afterFailure(Deposit $deposit): void {
        // do nothing, let subclasses override if needed.
    }

    /**
     * Get a list of deposits to process.
     * 
     * @return Deposit[]
     */
    public function getDeposits(bool $retry = false, array $depositIds = [], ?int $limit = null): array {
        ($qb = $this->em->createQueryBuilder())->select('d')->from(Deposit::class, 'd')
            ->where('d.state = :state')
            ->setParameter('state', $retry ? $this->errorState() : $this->processingState());

        if (count($depositIds)) {
            $qb->andWhere('d.id in (:ids)')->setParameter('ids', $depositIds);
        }

        $qb->orderBy(['action' => 'ASC', 'size' => 'ASC']);
        if($limit) {
            $qb->setMaxResults($limit);
        }
        return $qb->getQuery()->execute();
    }

    /**
     * Run and process one deposit.
     *
     * If $dryRun is is true results will not be flushed to the database.
     */
    public function runDeposit(Deposit $deposit, OutputInterface $output, bool $dryRun = false) : void {
        try {
            $result = $this->processDeposit($deposit);
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
            $deposit->setState($this->errorState());
            $deposit->addToProcessingLog($this->failureLogMessage());
            $deposit->addErrorLog($e::class . $e->getMessage());
            $this->em->flush();
            $this->afterFailure($deposit);

            return;
        }

        if ($dryRun) {
            return;
        }

        if (is_string($result)) {
            $deposit->setState($result);
            $deposit->addToProcessingLog('Holding deposit.');
        } elseif (true === $result) {
            $deposit->setState($this->nextState());
            $deposit->addToProcessingLog($this->successLogMessage());
        } elseif (false === $result) {
            $deposit->setState($this->errorState());
            $deposit->addToProcessingLog($this->failureLogMessage());
        } elseif (null === $result) {
            // dunno, do nothing I guess.
        }
        $this->em->flush();
        if ($result === true) {
            $this->afterSuccess($deposit);
        } elseif ($result === false) {
            $this->afterFailure($deposit);
        } 
    }
}

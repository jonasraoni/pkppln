<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Tests\Command\Processing;

use App\DataFixtures\DepositFixtures;
use App\DataFixtures\JournalFixtures;
use App\Entity\Deposit;
use App\Tests\TestCase\BaseControllerTestCase;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Description of AbstractProcessingCmdTest.
 *
 * @author michael
 */
class AbstractProcessingCmdTest extends BaseControllerTestCase
{
    /**
     * @var OutputInterface
     */
    private $output;

    public function fixtures(): array
    {
        return [
            JournalFixtures::class,
            DepositFixtures::class,
        ];
    }

    public function testSuccessfulRun(): void
    {
        $deposit = $this->em->find(Deposit::class, 1);
        $deposit->setState('dummy-state');
        $cmd = new DummyCommand($this->em, true);
        $cmd->runDeposit($deposit, $this->output);
        $this->assertSame('next-state', $deposit->getState());
        $this->assertStringEndsWith('success', trim($deposit->getProcessingLog()));
    }

    public function testFailureRun(): void
    {
        $deposit = $this->em->find(Deposit::class, 1);
        $deposit->setState('dummy-state');
        $cmd = new DummyCommand($this->em, false);
        $cmd->runDeposit($deposit, $this->output);
        $this->assertSame('dummy-error', $deposit->getState());
        $this->assertStringEndsWith('dummy log message', trim($deposit->getProcessingLog()));
    }

    public function testUncertainRun(): void
    {
        $deposit = $this->em->find(Deposit::class, 1);
        $deposit->setState('dummy-state');
        $cmd = new DummyCommand($this->em, null);
        $cmd->runDeposit($deposit, $this->output);
        $this->assertSame('dummy-state', $deposit->getState());
        $this->assertSame('', trim($deposit->getProcessingLog()));
    }

    public function testCustomRun(): void
    {
        $deposit = $this->em->find(Deposit::class, 1);
        $deposit->setState('dummy-state');
        $cmd = new DummyCommand($this->em, 'held');
        $cmd->runDeposit($deposit, $this->output);
        $this->assertSame('held', $deposit->getState());
        $this->assertStringEndsWith('Holding deposit.', trim($deposit->getProcessingLog()));
    }

    public function testSuccessfulDryRun(): void
    {
        $deposit = $this->em->find(Deposit::class, 1);
        $deposit->setState('dummy-state');
        $cmd = new DummyCommand($this->em, true);
        $cmd->runDeposit($deposit, $this->output, true);
        $this->assertSame('dummy-state', $deposit->getState());
        $this->assertStringEndsWith('', trim($deposit->getProcessingLog()));
    }

    public function testFailureDryRun(): void
    {
        $deposit = $this->em->find(Deposit::class, 1);
        $deposit->setState('dummy-state');
        $cmd = new DummyCommand($this->em, false);
        $cmd->runDeposit($deposit, $this->output, true);
        $this->assertSame('dummy-state', $deposit->getState());
        $this->assertStringEndsWith('', trim($deposit->getProcessingLog()));
    }

    public function testUncertainDryRun(): void
    {
        $deposit = $this->em->find(Deposit::class, 1);
        $deposit->setState('dummy-state');
        $cmd = new DummyCommand($this->em, null);
        $cmd->runDeposit($deposit, $this->output, true);
        $this->assertSame('dummy-state', $deposit->getState());
        $this->assertSame('', trim($deposit->getProcessingLog()));
    }

    public function testCustomDryRun(): void
    {
        $deposit = $this->em->find(Deposit::class, 1);
        $deposit->setState('dummy-state');
        $cmd = new DummyCommand($this->em, 'held');
        $cmd->runDeposit($deposit, $this->output, true);
        $this->assertSame('dummy-state', $deposit->getState());
        $this->assertStringEndsWith('', trim($deposit->getProcessingLog()));
    }

    public function testGetDeposits(): void
    {
        $deposit = $this->em->find(Deposit::class, 1);
        $deposit->setState('dummy-state');
        $this->em->flush();

        $cmd = new DummyCommand($this->em, 'held');
        $deposits = $cmd->getDeposits();
        $this->assertCount(1, $deposits);
        $this->assertSame($deposit->getDepositUuid(), $deposits[0]->getDepositUuid());
    }

    public function testGetDepositsRetry(): void
    {
        $deposit = $this->em->find(Deposit::class, 1);
        $deposit->setState('dummy-error');
        $this->em->flush();

        $cmd = new DummyCommand($this->em, 'held');
        $deposits = $cmd->getDeposits(true);
        $this->assertCount(1, $deposits);
        $this->assertSame($deposit->getDepositUuid(), $deposits[0]->getDepositUuid());
    }

    public function testGetDepositsId(): void
    {
        $deposit = $this->em->find(Deposit::class, 1);
        $deposit->setState('dummy-state');
        $this->em->flush();

        $cmd = new DummyCommand($this->em, 'held');
        $deposits = $cmd->getDeposits(false, [1]);
        $this->assertCount(1, $deposits);
        $this->assertSame($deposit->getDepositUuid(), $deposits[0]->getDepositUuid());
    }

    public function testGetDepositsRetryId(): void
    {
        $deposit = $this->em->find(Deposit::class, 1);
        $deposit->setState('dummy-error');
        $this->em->flush();

        $cmd = new DummyCommand($this->em, 'held');
        $deposits = $cmd->getDeposits(true, [1]);
        $this->assertCount(1, $deposits);
        $this->assertSame($deposit->getDepositUuid(), $deposits[0]->getDepositUuid());
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->output = $this->createMock(OutputInterface::class);
        $this->output->method('writeln')->willReturn(null);
    }
}

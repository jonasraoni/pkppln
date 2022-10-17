<?php

declare(strict_types=1);

namespace App\Tests\Command\Processing;

use App\Command\Processing\AbstractProcessingCmd;
use App\Entity\Deposit;
use Doctrine\ORM\EntityManagerInterface;

class DummyCommand extends AbstractProcessingCmd
{
    private null|bool|string $return = null;

    public function __construct(EntityManagerInterface $em, null|bool|string $return)
    {
        parent::__construct($em);
        $this->return = $return;
    }

    protected function processDeposit(Deposit $deposit): null|bool|string
    {
        return $this->return;
    }

    public function errorState(): string
    {
        return 'dummy-error';
    }

    public function failureLogMessage(): string
    {
        return 'dummy log message';
    }

    public function nextState(): string
    {
        return 'next-state';
    }

    public function processingState(): string
    {
        return 'dummy-state';
    }

    public function successLogMessage(): string
    {
        return 'success';
    }
}

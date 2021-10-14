<?php

namespace App\Tests\Command\Processing;

use App\Command\Processing\AbstractProcessingCmd;
use App\Entity\Deposit;
use Doctrine\ORM\EntityManagerInterface;

class DummyCommand extends AbstractProcessingCmd {
    private $return;

    public function __construct(EntityManagerInterface $em, $return) {
        parent::__construct($em);
        $this->return = $return;
    }

    protected function processDeposit(Deposit $deposit) {
        return $this->return;
    }

    public function errorState() {
        return 'dummy-error';
    }

    public function failureLogMessage() {
        return 'dummy log message';
    }

    public function nextState() {
        return 'next-state';
    }

    public function processingState() {
        return 'dummy-state';
    }

    public function successLogMessage() {
        return 'success';
    }
}

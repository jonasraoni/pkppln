<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Run all the processing commands in order.
 */
class RunAllCommand extends Command {
    /**
     * List of commands to run, in order.
     */
    public const COMMAND_LIST = [
        'pln:harvest',
        'pln:validate:payload',
        'pln:validate:bag',
        'pln:validate:xml',
        'pln:scan',
        'pln:reserialize',
        'pln:deposit',
        // 'pln:status',.
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure() : void {
        $this->setName('pln:run-all');
        $this->setDescription('Run all processing commands.');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the processing state to be updated');
        $this->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Only process $limit deposits.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) : void {
        foreach (self::COMMAND_LIST as $cmd) {
            $output->writeln("Running {$cmd}");
            $command = $this->getApplication()->find($cmd);
            $command->run($input, $output);
        }
    }
}

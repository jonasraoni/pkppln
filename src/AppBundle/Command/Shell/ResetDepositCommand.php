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
use Doctrine\Common\Collections\ArrayCollection;
use Monolog\Registry;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * Reset the processing status for one or more deposit.
 */
class ResetDepositCommand extends ContainerAwareCommand
{
    /**
     * @var Registry
     */
    protected $em;

    /**
     * @var Logger
     */
    protected $logger;

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
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('pln:reset');
        $this->setDescription('Reset deposits.');
        $this->addOption('clear', 'c', InputOption::VALUE_NONE, 'Clear error log and processing log for deposit.');
        $this->addArgument(
            'state',
            InputArgument::REQUIRED,
            'New state for the deposit(s)'
        );
        $this->addArgument(
            'deposit',
            InputArgument::IS_ARRAY,
            'Deposit UUID(s) to process'
        );
    }

    /**
     * @return Deposit[]|ArrayCollection
     */
    protected function getDeposits($uuids = array())
    {
        $repo = $this->em->getRepository('AppBundle:Deposit');
        $deposits = array();
        if ($uuids && count($uuids) > 0) {
            $deposits = $repo->findBy(array('depositUuid' => $uuids));
        } else {
            $deposits = $repo->findAll();
        }

        return $deposits;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $state = $input->getArgument('state');
        $uuids = $input->getArgument('deposit');
        $deposits = $this->getDeposits($uuids);
        foreach ($deposits as $deposit) {
            $this->logger->notice("Setting {$deposit->getDepositUuid()} to {$state}");
            $deposit->setState($state);
            if ($input->getOption('clear')) {
                $deposit->setErrorLog(array());
                $deposit->setPackageSize(null);
                $deposit->setPlnState(null);
                $deposit->setProcessingLog('');
                $deposit->addToProcessingLog('Deposit reset.');
                $deposit->setAuContainer(null);
                $deposit->setHarvestAttempts(0);
            }
        }
        $this->em->flush();
    }
}

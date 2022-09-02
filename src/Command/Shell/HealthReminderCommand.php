<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Shell;

use DateTime;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Send reminders about journals that haven't contacted the PLN in a while.
 */
class HealthReminderCommand extends Command
{
    use LoggerAwareTrait;

    private Environment $templating;
    private ContainerInterface $container;
    private MailerInterface $mailer;

    public function __construct(Environment $environment, LoggerInterface $logger, ContainerInterface $container, MailerInterface $mailer)
    {
        parent::__construct();
        $this->templating = $environment;
        $this->logger = $logger;
        $this->container = $container;
        $this->mailer = $mailer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('pln:health:reminder');
        $this->setDescription('Remind admins about silent journals.');
        $this->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_NONE,
            'Do not update journal status'
        );
        parent::configure();
    }

    /**
     * Send the notifications.
     *
     * @param User[] $users
     * @param Journal[] $journals
     */
    protected function sendReminders(int $days, array $users, array $journals): void
    {
        $notification = $this->templating->render('App:HealthCheck:reminder.txt.twig', [
            'journals' => $journals,
            'days' => $days,
        ]);
        foreach ($users as $user) {
            $email = (new Email())
                ->from('noreplies@pkp-pln.lib.sfu.ca')
                ->to(new Address($user->getEmail(), $user->getFullname()))
                ->subject('Automated notification from the PKP PLN')
                ->text($notification);
            $this->mailer->send($email);
        }
    }

    /**
     * Execute the runall command, which executes all the commands.
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $em = $this->container->get('doctrine')->getManager();
        $days = $this->container->getParameter('days_reminder');
        $journals = $em->getRepository('App:Journal')->findOverdue($days);
        $count = \count($journals);
        $this->logger->notice("Found {$count} overdue journals.");
        if (0 === \count($journals)) {
            return;
        }

        $users = $em->getRepository('AppUserBundle:User')->findUserToNotify();
        if (0 === \count($users)) {
            $this->logger->error('No users to notify.');

            return;
        }
        $this->sendReminders($days, $users, $journals);

        foreach ($journals as $journal) {
            $journal->setNotified(new DateTime());
        }

        if (! $input->getOption('dry-run')) {
            $em->flush();
        }
    }
}

<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Shell;

use App\Entity\Journal;
use App\Repository\JournalRepository;
use App\Services\Ping;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Nines\UserBundle\Entity\User;
use Nines\UserBundle\Repository\UserRepository;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Ping all the journals that haven't contacted the PLN in a while, and send
 * notifications to interested users.
 */
class HealthCheckCommand extends Command
{
    use LoggerAwareTrait;

    protected Ping $ping;
    private Environment $templating;
    private ContainerInterface $container;
    private EntityManagerInterface $em;
    private MailerInterface $mailer;

    /**
     * Set the service container, and initialize the command.
     */
    public function __construct(LoggerInterface $logger, Ping $ping, Environment $environment, ContainerInterface $container, EntityManagerInterface $em, MailerInterface $mailer, )
    {
        parent::__construct();
        $this->templating = $environment;
        $this->logger = $logger;
        $this->ping = $ping;
        $this->container = $container;
        $this->em = $em;
        $this->mailer = $mailer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('pln:health:check');
        $this->setDescription('Find journals that have gone silent.');
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Do not update journal status');
        parent::configure();
    }

    /**
     * Send the notifications.
     *
     * @throws \Twig\Error\Error
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    protected function sendNotifications(int $days, array $users, array $journals): void
    {
        $notification = $this->templating->render('App:HealthCheck:notification.txt.twig', [
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
     * Request a ping from a journal.
     *
     * @todo Use the Ping service
     */
    protected function pingJournal(Journal $journal): bool
    {
        $result = $this->ping->ping($journal);
        if ($result->hasError()) {
            $this->logger->error($result->getError());
        }
        return $result->areTermsAccepted() === 'yes';
    }

    /**
     * @throws \Twig\Error\Error
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $days = $this->container->get('days_silent');
        /** @var JournalRepository */
        $journalRepository = $this->em->getRepository(Journal::class);
        $journals = $journalRepository->findSilent($days);
        $count = \count($journals);
        $this->logger->notice("Found {$count} silent journals.");
        if (0 === \count($journals)) {
            return;
        }

        /** @var UserRepository */
        $userRepository = $this->em->getRepository(User::class);
        $users = $userRepository->findUserToNotify();
        if (0 === \count($users)) {
            $this->logger->error('No users to notify.');

            return;
        }
        $this->sendNotifications($days, $users, $journals);

        foreach ($journals as $journal) {
            if ($this->pingJournal($journal)) {
                $this->logger->notice("Ping Success {$journal->getUrl()})");
                $journal->setStatus('healthy');
                $journal->setContacted(new DateTime());
            } else {
                $journal->setStatus('unhealthy');
                $journal->setNotified(new DateTime());
            }
        }

        if (! $input->getOption('dry-run')) {
            $this->em->flush();
        }
    }
}

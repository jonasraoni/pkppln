<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Command\Shell;

use App\Entity\Journal;
use App\Services\Ping;
use AppUserBundle\Entity\User;
use DateTime;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\XmlParseException;
use Psr\Log\LoggerInterface;
use Swift_Message;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Tests\Logger;
use Twig\Environment;

/**
 * Ping all the journals that haven't contacted the PLN in a while, and send
 * notifications to interested users.
 */
class HealthCheckCommand extends Command {
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Ping
     */
    protected $ping;
    /**
     * @var TwigEngine
     */
    private $templating;

    /**
     * Set the service container, and initialize the command.
     */
    public function __construct(LoggerInterface $logger, Ping $ping, Environment $environment) {
        parent::__construct();
        $this->templating = $environment;
        $this->logger = $logger;
        $this->ping = $ping;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure() : void {
        $this->setName('pln:health:check');
        $this->setDescription('Find journals that have gone silent.');
        $this->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Do not update journal status');
        parent::configure();
    }

    /**
     * Send the notifications.
     *
     * @param int $days
     * @param User[] $users
     * @param Journal[] $journals
     *
     * @throws \Twig\Error\Error
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    protected function sendNotifications($days, $users, $journals) : void {
        $notification = $this->templating->render('App:HealthCheck:notification.txt.twig', [
            'journals' => $journals,
            'days' => $days,
        ]);
        $mailer = $this->getContainer()->get('mailer');
        foreach ($users as $user) {
            $message = Swift_Message::newInstance(
                'Automated notification from the PKP PLN',
                $notification,
                'text/plain',
                'utf-8'
            );
            $message->setFrom('noreplies@pkp-pln.lib.sfu.ca');
            $message->setTo($user->getEmail(), $user->getFullname());
            $mailer->send($message);
        }
    }

    /**
     * Request a ping from a journal.
     *
     * @todo Use the Ping service
     *
     * @return bool
     */
    protected function pingJournal(Journal $journal) {
        $client = new Client();

        try {
            $response = $client->get($journal->getGatewayUrl());
            if (200 !== $response->getStatusCode()) {
                return false;
            }
            $xml = $response->xml();
            $element = $xml->xpath('//terms')[0];
            if ($element && isset($element['termsAccepted']) && 'yes' === ((string) $element['termsAccepted'])) {
                return true;
            }
        } catch (RequestException $e) {
            $this->logger->error("Cannot ping {$journal->getUrl()}: {$e->getMessage()}");
            if ($e->hasResponse()) {
                $this->logger->error($e->getResponse()->getStatusCode() . ' ' . $this->logger->error($e->getResponse()->getReasonPhrase()));
            }
        } catch (XmlParseException $e) {
            $this->logger->error("Cannot parse journal ping response {$journal->getUrl()}: {$e->getMessage()}");
        }

        return false;
    }

    /**
     * @throws \Twig\Error\Error
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    protected function execute(InputInterface $input, OutputInterface $output) : void {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $days = $this->getContainer()->getParameter('days_silent');
        $journals = $em->getRepository('App:Journal')->findSilent($days);
        $count = count($journals);
        $this->logger->notice("Found {$count} silent journals.");
        if (0 === count($journals)) {
            return;
        }

        $users = $em->getRepository('AppUserBundle:User')->findUserToNotify();
        if (0 === count($users)) {
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

        if ( ! $input->getOption('dry-run')) {
            $em->flush();
        }
    }
}

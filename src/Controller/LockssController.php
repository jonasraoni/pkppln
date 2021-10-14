<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Deposit;
use App\Entity\Journal;
use App\Services\FilePaths;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Lockss Controller for handling all interaction with the LOCKSS network.
 */
class LockssController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * The logger instance.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Sets a logger.
     *
     * @param LoggerInterface $lockssLogger
     */
    public function setLogger(LoggerInterface $lockssLogger)
    {
        $this->logger = $lockssLogger;
    }

    /**
     * The LOCKSS permision statement.
     */
    public const PERMISSION_STMT = 'LOCKSS system has permission to collect, preserve, and serve this Archival Unit.';

    /**
     * Fetch a processed and packaged deposit.
     *
     * @throws BadRequestHttpException
     * @throws NotFoundHttpException
     *
     * @return BinaryFileResponse
     *
     * @Route("/fetch/{journalUuid}/{depositUuid}.zip", name="fetch", methods={"GET"})
     *
     * @ParamConverter("journal", class="App:Journal", options={"mapping": {"journalUuid"="uuid"}})
     * @ParamConverter("deposit", class="App:Deposit", options={"mapping": {"depositUuid"="depositUuid"}})
     */
    public function fetchAction(Request $request, Journal $journal, Deposit $deposit, FilePaths $fp) {
        $this->logger->notice("{$request->getClientIp()} - fetch - {$journal->getUuid()} - {$deposit->getDepositUuid()}");
        if ($deposit->getJournal() !== $journal) {
            throw new BadRequestHttpException("The requested Journal ID does not match the deposit's journal ID.");
        }
        $fs = new Filesystem();
        $path = $fp->getStagingBagPath($deposit);
        if ( ! $fs->exists($path)) {
            throw new NotFoundHttpException('Deposit not found.');
        }

        return new BinaryFileResponse($path);
    }

    /**
     * Return the permission statement for LOCKSS.
     *
     * @return Response
     *
     * @Route("/permission", name="lockss_permission", methods={"GET"})
     */
    public function permissionAction(Request $request) {
        $this->logger->notice("{$request->getClientIp()} - permission");

        return new Response(self::PERMISSION_STMT, Response::HTTP_OK, [
            'content-type' => 'text/plain',
        ]);
    }
}

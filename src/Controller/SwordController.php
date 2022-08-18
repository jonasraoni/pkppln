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
use App\Entity\TermOfUse;
use App\Services\BlackWhiteList;
use App\Services\DepositBuilder;
use App\Services\JournalBuilder;
use App\Utilities\Namespaces;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Psr\Log\LoggerAwareTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * An implementation of the SWORD v2 protocol.
 *
 * @Route("/api/sword/2.0")
 */
class SwordController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;
    use LoggerAwareTrait;

    /**
     * Black and white list service.
     *
     * @var BlackWhiteList
     */
    private $blackwhitelist;

    /**
     * Doctrine entity manager.
     *
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Build the controller.
     */
    public function __construct(BlackWhiteList $blackwhitelist, EntityManagerInterface $em) {
        $this->blackwhitelist = $blackwhitelist;
        $this->em = $em;
    }

    /**
     * Fetch an HTTP header.
     *
     * Checks the HTTP headers for $key and X-$key variant. If the app
     * is in the dev environment, will also check the query parameters for
     * $key.
     *
     * If $required is true and the header is not present BadRequestException
     * will be thrown.
     *
     * @param string $key
     * @param string $required
     *
     * @throws BadRequestException
     *
     * @return null|string
     *                     The value of the header or null if that's OK.
     */
    private function fetchHeader(Request $request, $key, $required = false) {
        if ($request->headers->has($key)) {
            return $request->headers->get($key);
        }
        if ('dev' === $this->getParameter('kernel.environment') && $request->query->has($key)) {
            return $request->query->get($key);
        }
        if ($required) {
            throw new BadRequestHttpException("HTTP header {$key} is required.", null, Response::HTTP_BAD_REQUEST);
        }

        return '';
    }

    /**
     * Check if a journal's uuid is whitelised or blacklisted.
     *
     * The rules are:
     *
     * If the journal uuid is whitelisted, return true
     * If the journal uuid is blacklisted, return false
     * Return the pln_accepting parameter from parameters.yml
     *
     * @param string $uuid
     *
     * @return bool
     */
    private function checkAccess($uuid) {
        if( ! $uuid) {
            return false;
        }
        if ($this->blackwhitelist->isWhitelisted($uuid)) {
            return true;
        }
        if ($this->blackwhitelist->isBlacklisted($uuid)) {
            return false;
        }

        return $this->getParameter('pln.accepting');
    }

    /**
     * Figure out which message to return for the network status widget in OJS.
     *
     * @return string
     */
    private function getNetworkMessage(Journal $journal) {
        if (null === $journal->getOjsVersion()) {
            return $this->getParameter('pln.network_default');
        }
        if (version_compare($journal->getOjsVersion(), $this->getParameter('pln.min_ojs_version'), '>=')) {
            return $this->getParameter('pln.network_accepting');
        }

        return $this->getParameter('pln.network_oldojs');
    }

    /**
     * Get the XML from an HTTP request.
     *
     * @throws BadRequestHttpException
     *
     * @return SimpleXMLElement
     */
    private function getXml(Request $request) {
        $content = $request->getContent();
        if ( ! $content || ! is_string($content)) {
            throw new BadRequestHttpException('Expected request body. Found none.', null, Response::HTTP_BAD_REQUEST);
        }

        try {
            $xml = simplexml_load_string($content);
            Namespaces::registerNamespaces($xml);

            return $xml;
        } catch (Exception $e) {
            throw new BadRequestHttpException('Cannot parse request XML.', $e, Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Return a SWORD service document for a journal.
     *
     * Requires On-Behalf-Of and Journal-Url HTTP headers.
     *
     * @return array
     *
     * @Template()
     * @Route("/sd-iri.{_format}", methods={"GET"},
     *  name="sword_service_document",
     *  defaults={"_format": "xml"},
     *  requirements={"_format": "xml"}
     * )
     */
    public function serviceDocumentAction(Request $request, JournalBuilder $builder) {
        $obh = strtoupper($this->fetchHeader($request, 'On-Behalf-Of'));
        $journalUrl = $this->fetchHeader($request, 'Journal-Url');
        $accepting = $this->checkAccess($obh);
        $this->logger->notice("{$request->getClientIp()} - service document - {$obh} - {$journalUrl} - accepting: " . ($accepting ? 'yes' : 'no'));
        if ( ! $obh) {
            throw new BadRequestHttpException('Missing On-Behalf-Of header.', null, 400);
        }
        if ( ! $journalUrl) {
            throw new BadRequestHttpException('Missing Journal-Url header.', null, 400);
        }

        $journal = $builder->fromRequest($obh, $journalUrl);
        if ( ! $journal->getTermsAccepted()) {
            $accepting = false;
        }
        $this->em->flush();
        $termsRepo = $this->getDoctrine()->getRepository(TermOfUse::class);

        return [
            'onBehalfOf' => $obh,
            'accepting' => $accepting ? 'Yes' : 'No',
            'maxUpload' => $this->getParameter('pln.max_upload'),
            'checksumType' => $this->getParameter('pln.checksum_type'),
            'message' => $this->getNetworkMessage($journal),
            'journal' => $journal,
            'terms' => $termsRepo->getTerms(),
            'termsUpdated' => $termsRepo->getLastUpdated(),
        ];
    }

    /**
     * Create a deposit.
     *
     * @return Response
     *
     * @Route("/col-iri/{uuid}", methods={"POST"}, name="sword_create_deposit", requirements={
     *      "uuid": ".{36}",
     * })
     * @ParamConverter("journal", options={"mapping": {"uuid"="uuid"}})
     */
    public function createDepositAction(Request $request, Journal $journal, JournalBuilder $journalBuilder, DepositBuilder $depositBuilder) {
        $accepting = $this->checkAccess($journal->getUuid());
        if ( ! $journal->getTermsAccepted()) {
            $this->accepting = false;
        }
        $this->logger->notice("{$request->getClientIp()} - create deposit - {$journal->getUuid()} - accepting: " . ($accepting ? 'yes' : 'no'));

        if ( ! $accepting) {
            throw new BadRequestHttpException('Not authorized to create deposits.', null, 400);
        }

        $xml = $this->getXml($request);
        // Update the journal metadata.
        $journalBuilder->fromXml($xml, $journal->getUuid());
        $deposit = $depositBuilder->fromXml($journal, $xml);
        $this->em->flush();

        // @var Response
        $response = $this->statementAction($request, $journal, $deposit);
        $response->headers->set('Location', $this->generateUrl('sword_statement', [
            'journal_uuid' => $journal->getUuid(),
            'deposit_uuid' => $deposit->getDepositUuid(),
        ], UrlGeneratorInterface::ABSOLUTE_URL));
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }

    /**
     * Check that status of a deposit by fetching the sword statemt.
     *
     * @return Response
     *
     * @Route("/cont-iri/{journal_uuid}/{deposit_uuid}/state", methods={"GET"}, name="sword_statement", requirements={
     *      "journal_uuid": ".{36}",
     *      "deposit_uuid": ".{36}"
     * })
     * @ParamConverter("journal", options={"mapping": {"journal_uuid"="uuid"}})
     * @ParamConverter("deposit", options={"mapping": {"deposit_uuid"="depositUuid"}})
     */
    public function statementAction(Request $request, Journal $journal, Deposit $deposit) {
        $accepting = $this->checkAccess($journal->getUuid());
        $this->logger->notice("{$request->getClientIp()} - statement - {$journal->getUuid()} - {$deposit->getDepositUuid()} - accepting: " . ($accepting ? 'yes' : 'no'));
        if ( ! $accepting && ! $this->isGranted('ROLE_USER')) {
            throw new BadRequestHttpException('Not authorized to request statements.', null, 400);
        }
        if ($journal !== $deposit->getJournal()) {
            throw new BadRequestHttpException('Deposit does not belong to journal.', null, 400);
        }
        $journal->setContacted(new DateTime());
        $journal->setStatus('healthy');
        $this->em->flush();
        $response = $this->render('sword/statement.xml.twig', [
            'deposit' => $deposit,
        ]);
        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }

    /**
     * Edit a deposit with an HTTP PUT.
     *
     * @return Response
     *
     * @Route("/cont-iri/{journal_uuid}/{deposit_uuid}/edit", methods={"PUT"}, name="sword_edit", requirements={
     *      "journal_uuid": ".{36}",
     *      "deposit_uuid": ".{36}"
     * })
     * @ParamConverter("journal", options={"mapping": {"journal_uuid"="uuid"}})
     * @ParamConverter("deposit", options={"mapping": {"deposit_uuid"="depositUuid"}})
     */
    public function editAction(Request $request, Journal $journal, Deposit $deposit, DepositBuilder $builder) {
        $accepting = $this->checkAccess($journal->getUuid());
        $this->logger->notice("{$request->getClientIp()} - edit deposit - {$journal->getUuid()} - {$deposit->getDepositUuid()} - accepting: " . ($accepting ? 'yes' : 'no'));
        if ( ! $accepting) {
            throw new BadRequestHttpException('Not authorized to create deposits.', null, 400);
        }
        if ($journal !== $deposit->getJournal()) {
            throw new BadRequestHttpException('Deposit does not belong to journal.', null, 400);
        }
        $xml = $this->getXml($request);
        $newDeposit = $builder->fromXml($journal, $xml);
        $newDeposit->setAction('edit');
        $this->em->flush();

        // @var Response
        $response = $this->statementAction($request, $journal, $deposit);
        $response->headers->set('Location', $this->generateUrl('sword_statement', [
            'journal_uuid' => $journal->getUuid(),
            'deposit_uuid' => $deposit->getDepositUuid(),
        ], UrlGeneratorInterface::ABSOLUTE_URL));
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }
}

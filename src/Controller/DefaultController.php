<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Repository\Repository;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UserBundle\Entity\User;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Default controller.
 */
class DefaultController extends AbstractController implements PaginatorAwareInterface
{
    use PaginatorTrait;
    /**
     * The LOCKSS permision statement.
     */
    public const PERMISSION_STMT = 'LOCKSS system has permission to collect, preserve, and serve this Archival Unit.';

    /**
     * Home page action.
     *
     * @Route("/", name="homepage", methods={"GET"})
     */
    public function indexAction(): Response
    {
        $user = $this->getUser();
        if (! ($user instanceof User) || ! $user->hasRole('ROLE_USER')) {
            return $this->render('default/index_anon.html.twig');
        }

        $journalRepo = Repository::journal();
        $depositRepo = Repository::deposit();

        return $this->render('default/index_user.html.twig', [
            'journals_new' => $journalRepo->findNew(),
            'journal_summary' => $journalRepo->statusSummary(),
            'deposits_new' => $depositRepo->findNew(),
            'states' => $depositRepo->stateSummary(),
        ]);
    }

    /**
     * Browse deposits across all jouurnals by state.
     *
     * @Route("/browse/{state}", name="deposit_browse", methods={"GET"})
     * @Template
     * @return array<string,mixed>
     */
    public function browseAction(Request $request, string $state): array
    {
        $repo = Repository::deposit();
        $qb = $repo->createQueryBuilder('d');
        $qb->where('d.state = :state');
        $qb->setParameter('state', $state);
        $qb->orderBy('d.id');

        $deposits = $this->paginator?->paginate($qb->getQuery(), $request->query->getInt('page', 1), 25);
        $states = $repo->stateSummary();

        return [
            'deposits' => $deposits,
            'states' => $states,
            'state' => $state,
        ];
    }

    /**
     * Search for Deposit entities.
     *
     * This action lives in the default controller because the
     * deposit controller works with deposits from a single
     * journal. This search works across all deposits.
     *
     * @Route("/deposit_search", name="all_deposit_search", methods={"GET"})
     *
     * @Security("is_granted('ROLE_USER')")
     * @Template
     * @return array<string,mixed>
     */
    public function depositSearchAction(Request $request): array
    {
        $repo = Repository::deposit();
        $q = $request->query->get('q');

        if ($q) {
            $query = $repo->searchQuery($q);
            $deposits = $this->paginator?->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $deposits = $this->paginator?->paginate([], $request->query->getInt('page', 1), 25);
        }

        return [
            'deposits' => $deposits,
            'q' => $q,
        ];
    }
}

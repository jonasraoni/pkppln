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
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Deposit controller.
 *
 * @Security("is_granted('ROLE_USER')")
 * @Route("/journal/{journalId}/deposit")
 * @ParamConverter("journal", options={"id"="journalId"})
 */
class DepositController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * Lists all Deposit entities.
     *
     * @return array
     *
     * @Route("/", name="deposit_index", methods={"GET"})
     *
     * @Template()
     */
    public function indexAction(Request $request, Journal $journal) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Deposit::class, 'e')->where('e.journal = :journal')->orderBy('e.id', 'ASC')->setParameter('journal', $journal);
        $query = $qb->getQuery();

        $deposits = $this->paginator->paginate($query, $request->query->getint('page', 1), 25);

        return [
            'deposits' => $deposits,
            'journal' => $journal,
        ];
    }

    /**
     * Search for Deposit entities.
     *
     * This action lives in the default controller because the deposit
     * controller works with deposits from a single journal. This
     * search works across all deposits.
     *
     * @Route("/search", name="deposit_search", methods={"GET"})
     *
     * @Security("is_granted('ROLE_USER')")
     * @Template()
     */
    public function searchAction(Request $request, Journal $journal) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Deposit::class);
        $q = $request->query->get('q');

        if ($q) {
            $query = $repo->searchQuery($q, $journal);
            $deposits = $this->paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $deposits = $this->paginator->paginate([], $request->query->getInt('page', 1), 25);
        }

        return [
            'journal' => $journal,
            'deposits' => $deposits,
            'q' => $q,
        ];
    }

    /**
     * Finds and displays a Deposit entity.
     *
     * @return array
     *
     * @Route("/{id}", name="deposit_show", methods={"GET"})
     *
     * @Template()
     */
    public function showAction(Journal $journal, Deposit $deposit) {
        return [
            'journal' => $journal,
            'deposit' => $deposit,
        ];
    }
}

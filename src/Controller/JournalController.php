<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Journal;
use App\Services\BlackWhiteList;
use App\Services\Ping;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Journal controller.
 *
 * @Security("is_granted('ROLE_USER')")
 * @Route("/journal")
 */
class JournalController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * Lists all Journal entities.
     *
     * @return array
     *
     * @Route("/", name="journal_index", methods={"GET"})
     *
     * @Template()
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Journal::class, 'e')->orderBy('e.id', 'ASC');
        if ($request->query->get('status', null)) {
            $qb->andWhere('e.status = :status');
            $qb->setParameter('status', $request->query->get('status'));
        }
        $query = $qb->getQuery();

        $journals = $this->paginator->paginate($query, $request->query->getint('page', 1), 25);

        return [
            'journals' => $journals,
        ];
    }

    /**
     * Search for Journal entities.
     *
     * @Route("/search", name="journal_search", methods={"GET"})
     *
     * @Template()
     */
    public function searchAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(Journal::class);
        $q = $request->query->get('q');

        if ($q) {
            $query = $repo->searchQuery($q);
            $journals = $this->paginator->paginate($query, $request->query->getInt('page', 1), 25);
        } else {
            $journals = $this->paginator->paginate([], $request->query->getInt('page', 1), 25);
        }

        return [
            'journals' => $journals,
            'q' => $q,
        ];
    }

    /**
     * Finds and displays a Journal entity.
     *
     * @return array
     *
     * @Route("/{id}", name="journal_show", methods={"GET"})
     *
     * @Template()
     */
    public function showAction(Journal $journal, BlackWhiteList $list) {
        return [
            'journal' => $journal,
            'list' => $list,
        ];
    }

    /**
     * Pings a journal and displays the result.
     *
     * @return array
     *
     * @Route("/{id}/ping", name="journal_ping", methods={"GET"})
     *
     * @Template()
     */
    public function pingAction(Journal $journal, Ping $ping, EntityManagerInterface $em) {
        $result = $ping->ping($journal);
        $em->flush();

        return [
            'journal' => $journal,
            'result' => $result,
        ];
    }
}

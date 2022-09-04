<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\TermOfUse;
use App\Form\TermOfUseType;
use App\Repository\Repository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * TermOfUse controller.
 *
 * @Security("is_granted('ROLE_USER')")
 * @Route("/termofuse")
 */
class TermOfUseController extends AbstractController implements PaginatorAwareInterface
{
    use PaginatorTrait;

    /**
     * Lists all TermOfUse entities.
     *
     * @Route("/", name="termofuse_index", methods={"GET"})
     *
     * @Template
     * @return array<string,mixed>
     */
    public function indexAction(Request $request, EntityManagerInterface $em): array
    {
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(TermOfUse::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();

        $termOfUses = $this->paginator?->paginate($query, $request->query->getint('page', 1), 25);

        return [
            'termOfUses' => $termOfUses,
        ];
    }

    /**
     * Creates a new TermOfUse entity.
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/new", name="termofuse_new", methods={"GET", "POST"})
     *
     * @Template
     * @return array<string,mixed>|RedirectResponse
     */
    public function newAction(Request $request, EntityManagerInterface $em): array|RedirectResponse
    {
        $termOfUse = new TermOfUse();
        $form = $this->createForm(TermOfUseType::class, $termOfUse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($termOfUse);
            $em->flush();

            $this->addFlash('success', 'The new termOfUse was created.');

            return $this->redirectToRoute('termofuse_show', ['id' => $termOfUse->getId()]);
        }

        return [
            'termOfUse' => $termOfUse,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a TermOfUse entity.
     *
     * @Route("/{id}", name="termofuse_show", methods={"GET"})
     *
     * @Template
     * @return array<string,mixed>
     */
    public function showAction(TermOfUse $termOfUse): array
    {
        // This can't just be $termOfUse->getHistory() or something because there
        // is no foreign key relationship - the history is preserved when a term is deleted.
        $repo = Repository::termOfUseHistory();
        $history = $repo->findBy(['termId' => $termOfUse->getId()], ['id' => 'ASC']);

        return [
            'termOfUse' => $termOfUse,
            'history' => $history,
        ];
    }

    /**
     * Displays a form to edit an existing TermOfUse entity.
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="termofuse_edit", methods={"GET", "POST"})
     *
     * @Template
     * @return array<string,mixed>|RedirectResponse
     */
    public function editAction(Request $request, TermOfUse $termOfUse, EntityManagerInterface $em): array|RedirectResponse
    {
        $editForm = $this->createForm(TermOfUseType::class, $termOfUse);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->addFlash('success', 'The termOfUse has been updated.');

            return $this->redirectToRoute('termofuse_show', ['id' => $termOfUse->getId()]);
        }

        return [
            'termOfUse' => $termOfUse,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * Deletes a TermOfUse entity.
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/delete", name="termofuse_delete", methods={"GET"})
     * @return RedirectResponse
     */
    public function deleteAction(EntityManagerInterface $em, TermOfUse $termOfUse): RedirectResponse
    {
        $em->remove($termOfUse);
        $em->flush();
        $this->addFlash('success', 'The termOfUse was deleted.');

        return $this->redirectToRoute('termofuse_index');
    }
}

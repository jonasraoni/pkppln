<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Entity\Document;
use App\Form\DocumentType;
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
 * Document controller.
 *
 * @Security("is_granted('ROLE_USER')")
 * @Route("/document")
 */
class DocumentController extends AbstractController implements PaginatorAwareInterface
{
    use PaginatorTrait;

    /**
     * Lists all Document entities.
     *
     * @Route("/", name="document_index", methods={"GET"})
     *
     * @Template
     * @return array<string,mixed>
     */
    public function indexAction(Request $request, EntityManagerInterface $em): array
    {
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Document::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();

        $documents = $this->paginator?->paginate($query, $request->query->getint('page', 1), 25);

        return [
            'documents' => $documents,
        ];
    }

    /**
     * Creates a new Document entity.
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/new", name="document_new", methods={"GET", "POST"})
     *
     * @Template
     * @return array<string,mixed>|RedirectResponse
     */
    public function newAction(Request $request, EntityManagerInterface $em): array|RedirectResponse
    {
        $document = new Document();
        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($document);
            $em->flush();

            $this->addFlash('success', 'The new document was created.');

            return $this->redirectToRoute('document_show', ['id' => $document->getId()]);
        }

        return [
            'document' => $document,
            'form' => $form->createView(),
        ];
    }

    /**
     * Finds and displays a Document entity.
     *
     * @Route("/{id}", name="document_show", methods={"GET"})
     *
     * @Template
     * @return array<string,mixed>
     */
    public function showAction(Document $document): array
    {
        return [
            'document' => $document,
        ];
    }

    /**
     * Displays a form to edit an existing Document entity.
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="document_edit", methods={"GET", "POST"})
     *
     * @Template
     * @return array<string,mixed>|RedirectResponse
     */
    public function editAction(Request $request, Document $document, EntityManagerInterface $em): array|RedirectResponse
    {
        $editForm = $this->createForm(DocumentType::class, $document);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->addFlash('success', 'The document has been updated.');

            return $this->redirectToRoute('document_show', ['id' => $document->getId()]);
        }

        return [
            'document' => $document,
            'edit_form' => $editForm->createView(),
        ];
    }

    /**
     * Deletes a Document entity.
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/delete", name="document_delete", methods={"GET"})
     * @return RedirectResponse
     */
    public function deleteAction(EntityManagerInterface $em, Document $document): RedirectResponse
    {
        $em->remove($document);
        $em->flush();
        $this->addFlash('success', 'The document was deleted.');

        return $this->redirectToRoute('document_index');
    }
}

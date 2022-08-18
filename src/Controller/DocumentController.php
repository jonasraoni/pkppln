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
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Document controller.
 *
 * @Security("is_granted('ROLE_USER')")
 * @Route("/document")
 */
class DocumentController extends AbstractController implements PaginatorAwareInterface {
    use PaginatorTrait;

    /**
     * Lists all Document entities.
     *
     * @return array
     *
     * @Route("/", name="document_index", methods={"GET"})
     *
     * @Template()
     */
    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from(Document::class, 'e')->orderBy('e.id', 'ASC');
        $query = $qb->getQuery();

        $documents = $this->paginator->paginate($query, $request->query->getint('page', 1), 25);

        return [
            'documents' => $documents,
        ];
    }

    /**
     * Creates a new Document entity.
     *
     * @return array|RedirectResponse
     *                                Array data for the template processor or a redirect to the Document.
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/new", name="document_new", methods={"GET","POST"})
     *
     * @Template()
     */
    public function newAction(Request $request) {
        $document = new Document();
        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
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
     * @return array
     *
     * @Route("/{id}", name="document_show", methods={"GET"})
     *
     * @Template()
     */
    public function showAction(Document $document) {
        return [
            'document' => $document,
        ];
    }

    /**
     * Displays a form to edit an existing Document entity.
     *
     * @return array|RedirectResponse
     *                                Array data for the template processor or a redirect to the Document.
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/edit", name="document_edit", methods={"GET","POST"})
     *
     * @Template()
     */
    public function editAction(Request $request, Document $document) {
        $editForm = $this->createForm(DocumentType::class, $document);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
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
     * @return array|RedirectResponse
     *                                A redirect to the document_index.
     *
     * @Security("is_granted('ROLE_ADMIN')")
     * @Route("/{id}/delete", name="document_delete", methods={"GET"})
     */
    public function deleteAction(Request $request, Document $document) {
        $em = $this->getDoctrine()->getManager();
        $em->remove($document);
        $em->flush();
        $this->addFlash('success', 'The document was deleted.');

        return $this->redirectToRoute('document_index');
    }
}

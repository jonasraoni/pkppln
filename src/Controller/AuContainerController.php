<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Controller;

use App\Repository\Repository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\PaginatorBundle\Definition\PaginatorAwareInterface;
use Nines\UtilBundle\Controller\PaginatorTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * AuContainer controller. AuContainers are read-only.
 *
 * @Route("/aucontainer")
 */
class AuContainerController extends AbstractController implements PaginatorAwareInterface
{
    use PaginatorTrait;

    /**
     * Lists all AuContainer entities.
     *
     * @Route("/", name="aucontainer", methods={"GET"})
     * @Template
     * @return array<string,mixed>
     */
    public function indexAction(Request $request, EntityManagerInterface $em): array
    {
        $dql = 'SELECT e FROM App:AuContainer e ORDER BY e.id';
        $query = $em->createQuery($dql);

        $entities = $this->paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        $repo = Repository::auContainer();
        $openContainer = $repo->getOpenContainer();
        $sizes = $repo->getSizes();

        return [
            'entities' => $entities,
            'openContainer' => $openContainer,
            'sizes' => $sizes,
        ];
    }

    /**
     * Finds and displays a AuContainer entity.
     *
     * @Route("/{id}", name="aucontainer_show", methods={"GET"})
     * @Template
     * @return array<string,mixed>
     */
    public function showAction(string $id): array
    {
        $repo = Repository::auContainer();
        $entity = $repo->find($id);
        $openContainer = $repo->getOpenContainer();

        if (! $entity) {
            throw $this->createNotFoundException('Unable to find AuContainer entity.');
        }

        return [
            'entity' => $entity,
            'openContainer' => $openContainer,
        ];
    }
}

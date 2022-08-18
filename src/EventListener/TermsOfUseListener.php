<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\EventListener;

use App\Entity\TermOfUse;
use App\Entity\TermOfUseHistory;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\UnitOfWork;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Doctrine event listener to record term history.
 */
class TermsOfUseListener {
    /**
     * Token store to get the user making changes.
     *
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * Construct the listener.
     */
    public function __construct(TokenStorageInterface $tokenStorage) {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Get an array describing the changes.
     *
     * @param string $action
     *
     * @return array
     */
    protected function getChangeSet(UnitOfWork $unitOfWork, TermOfUse $entity, $action) {
        switch ($action) {
            case 'create':
                return [
                    'id' => [null, $entity->getId()],
                    'weight' => [null, $entity->getWeight()],
                    'keyCode' => [null, $entity->getKeyCode()],
                    'content' => [null, $entity->getContent()],
                    'created' => [null, $entity->getCreated()],
                    'updated' => [null, $entity->getUpdated()],
                ];

            case 'update':
                return $unitOfWork->getEntityChangeSet($entity);
            case 'delete':
                return [
                    'id' => [$entity->getId(), null],
                    'weight' => [$entity->getWeight(), null],
                    'keyCode' => [$entity->getKeyCode(), null],
                    'content' => [$entity->getContent(), null],
                    'created' => [$entity->getCreated(), null],
                    'updated' => [$entity->getUpdated(), null],
                ];
        }
    }

    /**
     * Save a history event for a term of use.
     *
     * @param string $action
     */
    protected function saveHistory(LifecycleEventArgs $args, $action) : void {
        $entity = $args->getEntity();
        if ( ! $entity instanceof TermOfUse) {
            return;
        }

        $em = $args->getEntityManager();
        $unitOfWork = $em->getUnitOfWork();
        $changeSet = $this->getChangeSet($unitOfWork, $entity, $action);

        $history = new TermOfUseHistory();
        $history->setTermId($entity->getId());
        $history->setAction($action);
        $history->setChangeSet($changeSet);
        $token = $this->tokenStorage->getToken();
        if ($token) {
            $history->setUser($token->getUsername());
        } else {
            $history->setUser('console');
        }
        $em->persist($history);
        // These are post-whatever events, after a flush.
        $em->flush();
    }

    /**
     * Called automatically after a term entity is persisted.
     */
    public function postPersist(LifecycleEventArgs $args) : void {
        $this->saveHistory($args, 'create');
    }

    /**
     * Called automatically after a term entity is updated.
     */
    public function postUpdate(LifecycleEventArgs $args) : void {
        $this->saveHistory($args, 'update');
    }

    /**
     * Called automatically before a term entity is removed.
     */
    public function preRemove(LifecycleEventArgs $args) : void {
        $this->saveHistory($args, 'delete');
    }
}

<?php

declare(strict_types=1);

/*
 * (c) 2020 Michael Joyce <mjoyce@sfu.ca>
 * This source file is subject to the GPL v2, bundled
 * with this source code in the file LICENSE.
 */

namespace App\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Application menu builder.
 */
class Builder implements ContainerAwareInterface {
    use ContainerAwareTrait;

    /**
     * U+25BE, black down-pointing small triangle.
     */
    public const CARET = ' ▾';

    /**
     * Item factory.
     */
    private FactoryInterface $factory;

    /**
     * Authorization checker for getting user roles.
     */
    private AuthorizationCheckerInterface $authChecker;

    /**
     * Login token storage.
     */
    private TokenStorageInterface $tokenStorage;

    /**
     * Build the menu builder.
     */
    public function __construct(FactoryInterface $factory, AuthorizationCheckerInterface $authChecker, TokenStorageInterface $tokenStorage) {
        $this->factory = $factory;
        $this->authChecker = $authChecker;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Check if the currently logged in user has a given role.
     */
    private function hasRole(string $role): bool {
        if ( ! $this->tokenStorage->getToken()) {
            return false;
        }

        return $this->authChecker->isGranted($role);
    }

    /**
     * Build the app's main navigation menu.
     */
    public function mainMenu(array $options): ItemInterface {
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttributes([
            'class' => 'nav navbar-nav',
        ]);

        $menu->addChild('home', [
            'label' => 'Home',
            'route' => 'homepage',
        ]);

        if ( ! $this->hasRole('ROLE_USER')) {
            return $menu;
        }

        $menu->addChild('terms', [
            'label' => 'Terms of Use',
            'route' => 'termofuse_index',
        ]);

        $journals = $menu->addChild('journals', [
            'uri' => '#',
            'label' => 'Journals ' . self::CARET,
        ]);
        $journals->setAttribute('dropdown', true);
        $journals->setLinkAttribute('class', 'dropdown-toggle');
        $journals->setLinkAttribute('data-toggle', 'dropdown');
        $journals->setChildrenAttribute('class', 'dropdown-menu');

        $journals->addChild('All Journals', ['route' => 'journal_index']);
        $journals->addChild('Search Journals', ['route' => 'journal_search']);
        $journals->addChild('Whitelist', ['route' => 'whitelist_index']);
        $journals->addChild('Blacklist', ['route' => 'blacklist_index']);

        $menu->addChild('Docs', ['route' => 'document_index']);

        return $menu;
    }
}

<?php
declare(strict_types=1);

/**
 * BEdita, API-first content management framework
 * Copyright 2020 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */
namespace Chialab\FrontendKit\Traits;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Psr\Http\Message\UriInterface;

/**
 * Auth trait for BEdita frontends.
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent|null $Authentication
 */
trait AuthTrait
{
    /**
     * Gets the request instance.
     *
     * @return \Cake\Http\ServerRequest
     */
    abstract public function getRequest(): ServerRequest;

    /**
     * Add a component to the controller's registry.
     *
     * @param string $name The name of the component to load.
     * @param array $config The config for the component.
     * @return \Cake\Controller\Component
     */
    abstract public function loadComponent(string $name, array $config = []): Component;

    /**
     * Redirects to given $url, after turning off $this->autoRender.
     *
     * @param \Psr\Http\Message\UriInterface|array|string $url A string, array-based URL or UriInterface instance.
     * @param int $status HTTP status code. Defaults to `302`.
     * @return \Cake\Http\Response|null
     */
    abstract public function redirect(string|array|UriInterface $url, int $status = 302): ?Response;

    /**
     * Return home route, where users will be redirected after logout or when they try to login to a non-staging site.
     *
     * @return array|string
     */
    abstract protected function getHomeRoute(): array|string;

    /**
     * {@inheritDoc}
     *
     * @return \Cake\Http\Response|null
     */
    public function beforeFilter(Event $event): ?Response
    {
        parent::beforeFilter($event);

        if (!Configure::read('StagingSite')) {
            return $this->redirect($this->getHomeRoute());
        }

        $this->Authentication->allowUnauthenticated(['login']);

        return null;
    }

    /**
     * Login action.
     *
     * @return \Cake\Http\Response|null
     */
    public function login(): ?Response
    {
        $result = $this->Authentication->getResult();
        if ($result->isValid()) {
            // Login succeeded.
            return $this->redirect($this->Authentication->getLoginRedirect() ?? $this->getHomeRoute());
        }

        if ($this->getRequest()->is('post') && !$result->isValid()) {
            $this->loadComponent('Flash');
            $this->Flash->error(__('Username and password mismatch'));
        }

        return null;
    }

    /**
     * Logout action.
     *
     * @return \Cake\Http\Response|null
     */
    public function logout(): ?Response
    {
        $this->Authentication->logout();

        return $this->redirect($this->getHomeRoute());
    }
}

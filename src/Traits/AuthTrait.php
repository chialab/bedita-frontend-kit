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

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\Response;

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
    abstract public function getRequest();

    /**
     * Add a component to the controller's registry.
     *
     * @param string $name The name of the component to load.
     * @param array $config The config for the component.
     * @return \Cake\Controller\Component
     */
    abstract public function loadComponent($name, array $config = []);

    /**
     * Redirects to given $url, after turning off $this->autoRender.
     *
     * @param string|array|\Psr\Http\Message\UriInterface $url A string, array-based URL or UriInterface instance.
     * @param int $status HTTP status code. Defaults to `302`.
     * @return \Cake\Http\Response|null
     */
    abstract public function redirect($url, $status = 302);

    /**
     * @inheritDoc
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
     * Return home route, where users will be redirected after logout or when they try to login to a non-staging site.
     *
     * @return array|string
     */
    protected function getHomeRoute()
    {
        return ['_name' => 'pages:home'];
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

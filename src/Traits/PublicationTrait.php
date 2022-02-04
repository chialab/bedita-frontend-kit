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

use Cake\Http\Response;
use Cake\Routing\Router;
use Chialab\FrontendKit\Routing\Route\ObjectRoute;

/**
 * Trait with BEdita tree navigation for controllers.
 *
 * @property \Chialab\FrontendKit\Controller\Component\ObjectsComponent $Objects
 * @property \Chialab\FrontendKit\Controller\Component\PublicationComponent $Publication
 */
trait PublicationTrait
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
     * Load objects and publication components into the controller.
     *
     * @param string|int $rootId The id of the root folder.
     * @param string[] $menuFolders List of folders in the menu.
     * @param array|null $config ObjectsLoader config.
     * @return void
     */
    public function loadPublication($rootId, array $menuFolders = [], ?array $config = null): void
    {
        $this->loadComponent('RequestHandler');

        $this->Objects = $this->loadComponent('Chialab/FrontendKit.Objects', $config ?? [
            'objectTypesConfig' => [
                'objects' => ['include' => 'poster'],
                'folders' => ['include' => 'children,parents,poster'],
            ],
            'autoHydrateAssociations' => [
                'parents' => 2,
                'children' => 3,
            ],
        ]);

        $this->Publication = $this->loadComponent('Chialab/FrontendKit.Publication', [
            'publication' => $rootId,
            'menuFolders' => $menuFolders,
        ]);
    }

    /**
     * Home page.
     *
     * @return void
     */
    public function home(): void
    {
    }

    /**
     * Generic objects route.
     *
     * @param string $id Object id
     * @return Response
     */
    public function objects(string $id): Response
    {
        $object = $this->Objects->loadObject($id);
        $object = $this->Objects->loadObject((string)$object->id, $object->type);
        $this->set(compact('object'));

        return $this->Publication->renderFirstTemplate($object->uname, $object->type, 'objects');
    }

    /**
     * Generic object route.
     *
     * @param string $uname Object `id` or `uname`.
     * @return \Cake\Http\Response
     */
    public function object(string $uname): Response
    {
        $object = $this->Objects->loadObject($uname);
        $currentRoute = $this->getRequest()->getParam('_matchedRoute');
        foreach (Router::routes() as $route) {
            if (!$route instanceof ObjectRoute || $currentRoute === $route->template) {
                continue;
            }

            $out = $route->match(['_entity' => $object] + $route->defaults, []);
            if ($out !== false) {
                return $this->redirect($out);
            }
        }
        $paths = $this->Publication->getViablePaths($object->id);
        if (!empty($paths)) {
            return $this->redirect(['action' => 'fallback', $paths[0]['path']]);
        }

        $object = $this->Objects->loadFullObject((string)$object->id, $object->type);
        $this->set(compact('object'));

        $types = collection($object->object_type->getFullInheritanceChain())
            ->extract('name')
            ->toList();

        return $this->Publication->renderFirstTemplate(...$types);
    }

    /**
     * Generic object view.
     *
     * @param string $path Object path.
     * @return \Cake\Http\Response
     */
    public function fallback(string $path): Response
    {
        return $this->Publication->genericTreeAction($path);
    }
}

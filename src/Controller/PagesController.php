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
namespace Chialab\FrontendKit\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Routing\Router;
use Chialab\FrontendKit\Routing\Route\ObjectRoute;

/**
 * Static & folder content controller
 *
 * This controller will render views from templates/Pages/
 *
 * @link https://book.cakephp.org/4/en/controllers/pages-controller.html
 *
 * @property \Chialab\FrontendKit\Controller\Component\ObjectsComponent $Objects
 * @property \Chialab\FrontendKit\Controller\Component\PublicationComponent $Publication
 */
class PagesController extends Controller
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('RequestHandler');
        $this->loadComponent('Flash');

        $this->loadComponent('Chialab/FrontendKit.Objects', Configure::read('ObjectsLoader', [
            'objectTypesConfig' => [
                'objects' => ['include' => 'poster'],
                'folders' => ['include' => 'children,parents,poster'],
            ],
            'autoHydrateAssociations' => [
                'parents' => 2,
                'children' => 3,
            ],
        ]));

        $this->loadComponent('Chialab/FrontendKit.Publication', [
            'publication' => Configure::read('Root'),
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
        $currentRoute = $this->request->getParam('_matchedRoute');
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

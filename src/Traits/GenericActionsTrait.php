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

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Routing\Router;
use Chialab\FrontendKit\Routing\Route\ObjectRoute;

/**
 * Trait with BEdita tree navigation for controllers.
 *
 * @property \Chialab\FrontendKit\Controller\Component\ObjectsComponent $Objects
 * @property \Chialab\FrontendKit\Controller\Component\PublicationComponent $Publication
 */
trait GenericActionsTrait
{
    use RenderTrait;

    /**
     * Gets the request instance.
     *
     * @return \Cake\Http\ServerRequest
     */
    abstract public function getRequest();

    /**
     * Handles pagination of records in Table objects.
     *
     * @param \Cake\ORM\Table|string|\Cake\ORM\Query|null $object Table to paginate
     * @param array $settings The settings/configuration used for pagination.
     * @return \Cake\ORM\ResultSet|\Cake\Datasource\ResultSetInterface Query results
     */
    abstract public function paginate($object = null, array $settings = []);

    /**
     * Load folder's children using paginations and query filters.
     *
     * @param int|string $id Folder id to load children.
     * @return \BEdita\Core\Model\Entity\ObjectEntity[] An array of children.
     */
    protected function loadFilteredChildren(string $id): array
    {
        $children = $this->Objects->loadRelatedObjects($id, 'folders', 'children', $this->Filters->fromQuery());

        return $this->paginate($children->order([], true), ['order' => ['Trees.tree_left']])->toList();
    }

    /**
     * Generic objects route.
     *
     * @param string $id Object id
     * @return Response
     */
    public function objects(string $id): Response
    {
        try {
            $object = $this->Objects->loadObject($id);
            $object = $this->Objects->loadFullObject((string)$object->id, $object->type);
            if ($object->type === 'folders') {
                $object['children'] = $this->loadFilteredChildren($object->uname);
            }
            $this->set(compact('object'));

            return $this->renderFirstTemplate($object->uname, $object->type, 'objects');
        } catch (RecordNotFoundException $e) {
            throw new NotFoundException(__('Page not found'), null, $e);
        }
    }

    /**
     * Generic object route.
     *
     * @param string $uname Object `id` or `uname`.
     * @return \Cake\Http\Response
     */
    public function object(string $uname): Response
    {
        try {
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
            if ($object->type === 'folders') {
                $object['children'] = $this->loadFilteredChildren($object->uname);
            }

            $this->set(compact('object'));

            $types = collection($object->object_type->getFullInheritanceChain())
                ->extract('name')
                ->toList();

            return $this->renderFirstTemplate(...$types);
        } catch (RecordNotFoundException $e) {
            throw new NotFoundException(__('Page not found'), null, $e);
        }
    }

    /**
     * Generic object view.
     *
     * @param string $path Object path.
     * @return \Cake\Http\Response
     */
    public function fallback(string $path): Response
    {
        try {
            $ancestors = $this->Publication->loadObjectPath($path)->toList();
            $object = array_pop($ancestors);
            $parent = end($ancestors) ?: null;

            if ($object->type === 'folders') {
                $children = $object['children'] = $this->loadFilteredChildren($object->uname);
                $this->set(compact('children'));
            }

            $this->set(compact('object', 'parent', 'ancestors'));

            return $this->renderFirstTemplate(...$this->getTemplatesToIterate($object, ...array_reverse($ancestors)));
        } catch (RecordNotFoundException $e) {
            throw new NotFoundException(__('Page not found'), null, $e);
        }
    }
}

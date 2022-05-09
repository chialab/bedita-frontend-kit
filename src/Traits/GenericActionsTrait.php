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
     * Dispatch before object load hook events.
     *
     * @param string $uname Object uname.
     * @param string $type Object type.
     * @return \Cake\Http\Response|null The resulting response of the events.
     */
    protected function dispatchBeforeLoadEvent(string $uname, string $type): ?Response
    {
        $event = $this->dispatchEvent(sprintf('Controller.beforeObjectLoad:%s', $uname), compact('uname', 'type'));
        if ($event->getResult() !== null) {
            return $event->getResult();
        }

        $event = $this->dispatchEvent(sprintf('Controller.beforeObjectLoad:%s', $type), compact('uname', 'type'));
        if ($event->getResult() !== null) {
            return $event->getResult();
        }

        $event = $this->dispatchEvent('Controller.beforeObjectLoad', compact('uname', 'type'));
        if ($event->getResult() !== null) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Dispatch after object load hook events.
     *
     * @param string $uname Object uname.
     * @param string $type Object type.
     * @param array $data Objects data to pass to callback.
     * @return \Cake\Http\Response|null The resulting response of the events.
     */
    protected function dispatchAfterLoadEvent(string $uname, string $type, array $data): ?Response
    {
        $event = $this->dispatchEvent(sprintf('Controller.afterObjectLoad:%s', $uname), $data);
        if ($event->getResult() !== null) {
            return $event->getResult();
        }

        $event = $this->dispatchEvent(sprintf('Controller.afterObjectLoad:%s', $type), $data);
        if ($event->getResult() !== null) {
            return $event->getResult();
        }

        $event = $this->dispatchEvent('Controller.afterObjectLoad', $data);
        if ($event->getResult() !== null) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Dispatch before object render hook events.
     *
     * @param string $uname Object uname.
     * @param string $type Object type.
     * @param array $data Objects data to pass to callback.
     * @return \Cake\Http\Response|null The resulting response of the events.
     */
    protected function dispatchBeforeRenderEvent(string $uname, string $type, array $data): ?Response
    {
        $event = $this->dispatchEvent(sprintf('Controller.beforeObjectRender:%s', $uname), $data);
        if ($event->getResult() !== null) {
            return $event->getResult();
        }

        $event = $this->dispatchEvent(sprintf('Controller.beforeObjectRender:%s', $type), $data);
        if ($event->getResult() !== null) {
            return $event->getResult();
        }

        return null;
    }

    /**
     * Generic objects route.
     *
     * @param string $uname Object id or uname.
     * @return Response
     */
    public function objects(string $uname): Response
    {
        $object = $this->Objects->loadObject($uname, 'objects', [], []);
        $result = $this->dispatchBeforeLoadEvent($object->uname, $object->type);
        if ($result !== null) {
            return $result;
        }

        $object = $this->Objects->loadFullObject((string)$object->id, $object->type);
        $result = $this->dispatchAfterLoadEvent($object->uname, $object->type, compact('object'));
        if ($result !== null) {
            return $result;
        }

        if ($object->type === 'folders' && !isset($object['children'])) {
            $object['children'] = $this->loadFilteredChildren($object->uname);
        }

        $this->set(compact('object'));
        if (isset($object['children'])) {
            $this->set('children', $object['children']);
        }

        $result = $this->dispatchBeforeRenderEvent($object->uname, $object->type, compact('object'));
        if ($result !== null) {
            return $result;
        }

        return $this->renderFirstTemplate(...$this->getTemplatesToIterate($object));
    }

    /**
     * Generic object route.
     *
     * @param string $uname Object `id` or `uname`.
     * @return \Cake\Http\Response
     */
    public function object(string $uname): Response
    {
        $object = $this->Objects->loadObject($uname, 'objects', [], []);
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

        $result = $this->dispatchBeforeLoadEvent($object->uname, $object->type);
        if ($result !== null) {
            return $result;
        }

        $object = $this->Objects->loadFullObject((string)$object->id, $object->type);
        $result = $this->dispatchAfterLoadEvent($object->uname, $object->type, compact('object'));
        if ($result !== null) {
            return $result;
        }

        if ($object->type === 'folders' && !isset($object['children'])) {
            $object['children'] = $this->loadFilteredChildren($object->uname);
        }
        $this->set(compact('object'));
        if (isset($object['children'])) {
            $this->set('children', $object['children']);
        }

        $result = $this->dispatchBeforeRenderEvent($object->uname, $object->type, compact('object'));
        if ($result !== null) {
            return $result;
        }

        return $this->renderFirstTemplate(...$this->getTemplatesToIterate($object));
    }

    /**
     * Generic object view.
     *
     * @param string $path Object path.
     * @return \Cake\Http\Response
     */
    public function fallback(string $path): Response
    {
        $ancestors = $this->Publication->loadObjectPath($path)->toList();
        $object = array_pop($ancestors);
        $parent = end($ancestors) ?: null;

        $result = $this->dispatchBeforeLoadEvent($object->uname, $object->type);
        if ($result !== null) {
            return $result;
        }

        $object = $this->loader->loadFullObject((string)$object->id, $object->type);
        $result = $this->dispatchAfterLoadEvent($object->uname, $object->type, compact('object'));
        if ($result !== null) {
            return $result;
        }

        if ($object->type === 'folders' && !isset($object['children'])) {
            $object['children'] = $this->loadFilteredChildren($object->uname);
        }

        $this->set(compact('object', 'parent', 'ancestors'));
        if (isset($object['children'])) {
            $this->set('children', $object['children']);
        }

        $result = $this->dispatchBeforeRenderEvent($object->uname, $object->type, compact('object'));
        if ($result !== null) {
            return $result;
        }

        return $this->renderFirstTemplate(...$this->getTemplatesToIterate($object, ...array_reverse($ancestors)));
    }
}

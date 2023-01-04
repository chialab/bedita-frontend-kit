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

use BEdita\Core\Model\Entity\Folder;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Chialab\FrontendKit\Routing\Route\ObjectRoute;
use UnexpectedValueException;

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
    abstract public function getRequest(): ServerRequest;

    /**
     * Handles pagination of records in Table objects.
     *
     * @param \Cake\ORM\Table|\Cake\ORM\Query|string|null $object Table to paginate
     * @param array $settings The settings/configuration used for pagination.
     * @return \Cake\ORM\ResultSet|\Cake\Datasource\ResultSetInterface Query results
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    abstract public function paginate($object = null, array $settings = []);

    /**
     * Load folder's children using paginations and query filters.
     *
     * @param string|int $id Folder id to load children.
     * @return array<\BEdita\Core\Model\Entity\ObjectEntity> An array of children.
     */
    protected function loadFilteredChildren(Folder $folder): array
    {
        $field = Hash::get($folder, 'custom_props.children_order') ?: 'position';
        $dir = 'ASC';
        if (str_starts_with($field, '-')) {
            $dir = 'DESC';
            $field = substr($field, 1);
        }

        $order = match ($field) {
            'position' => ['Trees.tree_left' => $dir],
            default => [$field => $dir],
        };

        $children = $this->Objects->loadRelatedObjects($folder['uname'], 'folders', 'children', $this->Filters->fromQuery());

        return $this->paginate($children, ['order' => $order])->toList();
    }

    /**
     * Dispatch before object load hook events.
     *
     * @param string $uname Object uname.
     * @param string $type Object type.
     * @return \BEdita\Core\Model\Entity\ObjectEntity|null The requested object entity.
     */
    protected function dispatchBeforeLoadEvent(string $uname, string $type): ObjectEntity|null
    {
        $event = $this->dispatchEvent('Controller.beforeObjectLoad', compact('uname', 'type'));
        $result = $event->getResult();
        if ($result !== null && !$result instanceof ObjectEntity) {
            throw new UnexpectedValueException('Controller.beforeObjectLoad event must return an ObjectEntity or null');
        }

        return $result;
    }

    /**
     * Dispatch after object load hook events.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $object Loaded object.
     * @return \BEdita\Core\Model\Entity\ObjectEntity|null The requested object entity.
     */
    protected function dispatchAfterLoadEvent(ObjectEntity $object): ObjectEntity|null
    {
        $event = $this->dispatchEvent('Controller.afterObjectLoad', compact('object'));
        $result = $event->getResult();
        if ($result !== null && !$result instanceof ObjectEntity) {
            throw new UnexpectedValueException('Controller.afterObjectLoad event must return an ObjectEntity or null');
        }

        return $result;
    }

    /**
     * Dispatch before object render hook events.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $object Loaded object.
     * @return \Cake\Http\Response|null The resulting response of the events.
     */
    protected function dispatchBeforeRenderEvent(ObjectEntity $object): Response|null
    {
        $event = $this->dispatchEvent('Controller.beforeObjectRender', compact('object'));
        $result = $event->getResult();
        if ($result !== null && !$result instanceof Response) {
            throw new UnexpectedValueException('Controller.beforeObjectRender event must return a valid Response or null');
        }

        return null;
    }

    /**
     * Generic objects route.
     *
     * @param string $uname Object id or uname.
     * @return \Cake\Http\Response|null
     */
    public function objects(string $uname): Response|null
    {
        $entity = $this->Objects->loadObject($uname, 'objects', [], []);
        $object = $this->dispatchBeforeLoadEvent($entity->uname, $entity->type);
        if ($object === null) {
            $object = $this->Objects->loadFullObject((string)$entity->id, $entity->type);
        }

        $object = $this->dispatchAfterLoadEvent($object) ?? $object;
        if ($object->type === 'folders' && !isset($object['children'])) {
            $object['children'] = $this->loadFilteredChildren($object);
        }

        $this->set(compact('object'));
        if (isset($object['children'])) {
            $this->set('children', $object['children']);
        }

        $result = $this->dispatchBeforeRenderEvent($object);
        if ($result !== null) {
            return $result;
        }

        if (!$this->viewBuilder()->getTemplate()) {
            return $this->renderFirstTemplate(...$this->getTemplatesToIterate($object));
        }

        return null;
    }

    /**
     * Generic object route.
     *
     * @param string $uname Object `id` or `uname`.
     * @return \Cake\Http\Response|null
     */
    public function object(string $uname): Response|null
    {
        $entity = $this->Objects->loadObject($uname, 'objects', [], []);
        $currentRoute = $this->getRequest()->getParam('_matchedRoute');
        $locale = $this->getRequest()->getParam('locale', null);
        $params = array_filter(compact('locale'));

        foreach (Router::routes() as $route) {
            if (!$route instanceof ObjectRoute || $currentRoute === $route->template) {
                continue;
            }

            $out = $route->match(['_entity' => $entity] + $route->defaults + $params, []);
            if ($out !== null) {
                return $this->redirect($out);
            }
        }

        $paths = $this->Publication->getViablePaths($entity->id);
        if (!empty($paths)) {
            return $this->redirect(['action' => 'fallback', $paths[0]['path']] + $params);
        }

        $object = $this->dispatchBeforeLoadEvent($entity->uname, $entity->type);
        if ($object === null) {
            $object = $this->Objects->loadFullObject((string)$entity->id, $entity->type);
        }

        $object = $this->dispatchAfterLoadEvent($object) ?? $object;
        if ($object->type === 'folders' && !isset($object['children'])) {
            $object['children'] = $this->loadFilteredChildren($object);
        }

        $this->set(compact('object'));
        if (isset($object['children'])) {
            $this->set('children', $object['children']);
        }

        $result = $this->dispatchBeforeRenderEvent($object);
        if ($result !== null) {
            return $result;
        }

        if (!$this->viewBuilder()->getTemplate()) {
            return $this->renderFirstTemplate(...$this->getTemplatesToIterate($object));
        }

        return null;
    }

    /**
     * Generic object view.
     *
     * @param string $path Object path.
     * @return \Cake\Http\Response|null
     */
    public function fallback(string $path): Response|null
    {
        $ancestors = $this->Publication->loadObjectPath($path)->toList();
        $leaf = array_pop($ancestors);
        $parent = end($ancestors) ?: null;

        $object = $this->dispatchBeforeLoadEvent($leaf->uname, $leaf->type);
        if ($object === null) {
            $object = $this->Objects->loadFullObject((string)$leaf->id, $leaf->type);
        }

        $objects = $this->dispatchAfterLoadEvent($object) ?? $object;
        if ($object->type === 'folders' && !isset($object['children'])) {
            $object['children'] = $this->loadFilteredChildren($object);
        }

        $this->set(compact('object', 'parent', 'ancestors'));
        if (isset($object['children'])) {
            $this->set('children', $object['children']);
        }

        $result = $this->dispatchBeforeRenderEvent($object);
        if ($result !== null) {
            return $result;
        }

        if (!$this->viewBuilder()->getTemplate()) {
            return $this->renderFirstTemplate(...$this->getTemplatesToIterate($object, ...array_reverse($ancestors)));
        }

        return null;
    }
}

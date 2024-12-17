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

use BEdita\Core\Filesystem\FilesystemRegistry;
use BEdita\Core\Model\Entity\Folder;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Http\Exception\InternalErrorException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\Routing\Router;
use Cake\Utility\Hash;
use Chialab\FrontendKit\Routing\Route\ObjectRoute;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\UriInterface;
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
     * Gets the response instance.
     *
     * @return \Cake\Http\Response
     */
    abstract public function getResponse(): Response;

    /**
     * Redirects to given URL.
     *
     * @param \Psr\Http\Message\UriInterface|array|string $url A string, array-based URL or UriInterface instance.
     * @param int $status HTTP status code. Defaults to `302`.
     * @return \Cake\Http\Response|null
     */
    abstract public function redirect(UriInterface|array|string $url, int $status = 302): Response|null;

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
        $order = Hash::get($folder, 'custom_props.children_order', null);
        if ($order) {
            $type = str_starts_with($order, '-') ? substr($order, 1) : $order;
            if ($type === 'position') {
                $type = 'Trees.tree_left';
            }
            $order = str_starts_with($order, '-') ? [$type => 'DESC'] : [$type => 'ASC'];
        } else {
            $order = ['Trees.tree_left' => 'ASC'];
        }

        $children = $this->Objects->loadRelatedObjects($folder['uname'], 'folders', 'children', $this->Filters->fromQuery());

        $settings = Hash::merge($this->paginate, [
            'order' => $order,
            'sortableFields' => array_filter(array_merge($this->paginate['sortableFields'] ?? (array)$this->request->getQuery('sort'), array_keys($order))),
        ]);
        if (isset($settings['Children'])) {
            $settings = Hash::merge($settings, [
                'Children' => [
                    'order' => $order,
                    'sortableFields' => array_filter(array_merge($this->paginate['Children']['sortableFields'] ?? (array)$this->request->getQuery('sort'), array_keys($order))),
                ],
            ]);
        }

        return $this->paginate($children->order([], true), $settings)->toList();
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

        return $this->renderObject($entity);
    }

    /**
     * Render object.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $entity Object entity.
     * @return \Cake\Http\Response|null
     */
    protected function renderObject(ObjectEntity $entity): Response|null
    {
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

        $object = $this->dispatchAfterLoadEvent($object) ?? $object;
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

    /**
     * Download a media given its `uname`.
     *
     * @param string $uname Media `uname`.
     * @param string|null $filename Original file name. If not provided or not updated, redirect to the correct URL.
     * @return \Cake\Http\Response
     */
    public function download(string $uname, string|null $filename = null): Response
    {
        /** @var \BEdita\Core\Model\Entity\Media $media */
        $media = $this->Objects->loadObject($uname, 'media', [], []);
        if (empty($media->streams)) {
            throw new NotFoundException();
        }

        $stream = collection($media->streams)->first();
        if ($filename === null || $filename !== $stream->file_name) {
            return $this->redirect(['action' => 'download', $media->uname, $stream->file_name]);
        }

        $fh = FilesystemRegistry::getMountManager()->readStream($stream->uri);
        if ($fh === false) {
            throw new InternalErrorException('Cannot open stream');
        }

        return $this->getResponse()
            ->withHeader('Content-Type', $stream->mime_type)
            ->withHeader('Content-Disposition', sprintf('attachment; filename="%s"', $stream->file_name))
            ->withBody(new Stream($fh));
    }
}

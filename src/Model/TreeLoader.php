<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Model;

use BEdita\Core\Model\Entity\Folder;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Collection\CollectionInterface;
use Cake\Database\Expression\ComparisonExpression;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Datasource\ModelAwareTrait;
use Cake\ORM\Query;

/**
 * Class TreeLoader
 *
 * @package Chialab\FrontendKit\Model
 * @property \BEdita\Core\Model\Table\TreesTable $Trees
 */
class TreeLoader
{
    use ModelAwareTrait;

    /**
     * Objects loader instance.
     *
     * @var \Chialab\FrontendKit\Model\ObjectsLoader
     */
    protected ObjectsLoader $loader;

    /**
     * Tree loader constructor.
     *
     * @param \Chialab\FrontendKit\Model\ObjectsLoader $loader Objects loader instance.
     */
    public function __construct(ObjectsLoader $loader)
    {
        $this->loader = $loader;

        $this->loadModel('Trees');
    }

    /**
     * Load all objects in a path.
     *
     * @param string $path Path.
     * @param int|null $relativeTo ID of parent relative to which compute paths.
     * @return \Cake\Collection\CollectionInterface|array<\BEdita\Core\Model\Entity\ObjectEntity> List of objects, the root element in the path being the first in the list, the leaf being the latter.
     */
    public function loadObjectPath(string $path, ?int $relativeTo = null): CollectionInterface
    {
        $parts = array_filter(explode('/', $path));
        $path = implode('/', $parts);
        $leaf = $this->loader->loadObject(array_pop($parts), 'objects', [], []);

        $found = $this->getObjectPaths($leaf->id, $relativeTo)
            ->having(compact('path'), ['path' => 'string'])
            ->firstOrFail();

        $ids = explode(',', $found['path_ids']);
        array_pop($ids);

        if (empty($ids)) {
            return collection([$leaf]);
        }

        return $this->loader->loadObjects(['id' => $ids], 'folders')
            ->sortBy(function (Folder $folder) use ($ids): int {
                return array_search($folder->id, $ids);
            }, SORT_ASC)
            ->append([$leaf]);
    }

    /**
     * Get list of viable paths for an object in the current context.
     *
     * @param int $id Object ID.
     * @param int|null $relativeTo ID of parent relative to which compute paths.
     * @param int|null $via ID of requested parent.
     * @return array<array>
     */
    public function getViablePaths(int $id, ?int $relativeTo, ?int $via = null): array
    {
        $query = $this->getObjectPaths($id, $relativeTo);
        if ($via !== null) {
            $query = $query->having(new FunctionExpression('BIT_OR', [
                new ComparisonExpression($this->Trees->ParentNode->aliasField('object_id'), $via, 'integer', '='),
            ]));
        }

        return $query->toList();
    }

    /**
     * Prepare query to find all tree paths given an object ID, optionally computing relative paths to another object.
     *
     * @param int $id Object ID.
     * @param int|null $relativeTo Object ID relative to which paths should be computed.
     * @return \Cake\ORM\Query
     */
    protected function getObjectPaths(int $id, ?int $relativeTo = null): Query
    {
        $query = $this->Trees->find()
            ->select([$this->Trees->aliasField('canonical')])
            ->where([$this->Trees->aliasField('object_id') => $id])
            ->group([$this->Trees->aliasField('id'), $this->Trees->aliasField('canonical')])
            ->orderDesc($this->Trees->aliasField('canonical'));

        // Join with parent nodes, up to the requested node.
        $exp = $query->newExpr()
            ->eq(
                new IdentifierExpression($this->Trees->ParentNode->aliasField('root_id')),
                new IdentifierExpression($this->Trees->aliasField('root_id'))
            )
            ->lte(
                new IdentifierExpression($this->Trees->ParentNode->aliasField('tree_left')),
                new IdentifierExpression($this->Trees->aliasField('tree_left'))
            )
            ->gte(
                new IdentifierExpression($this->Trees->ParentNode->aliasField('tree_right')),
                new IdentifierExpression($this->Trees->aliasField('tree_right'))
            );
        if ($relativeTo !== null) {
            $exp = $exp
                ->gt(
                    new IdentifierExpression($this->Trees->ParentNode->aliasField('tree_left')),
                    $this->Trees->find()
                        ->select([$this->Trees->aliasField('tree_left')])
                        ->where([$this->Trees->aliasField('object_id') => $relativeTo])
                )
                ->lt(
                    new IdentifierExpression($this->Trees->ParentNode->aliasField('tree_right')),
                    $this->Trees->find()
                        ->select([$this->Trees->aliasField('tree_right')])
                        ->where([$this->Trees->aliasField('object_id') => $relativeTo])
                );
        }
        $query = $query
            ->select([
                // TODO: use QueryExpressions for this:
                'path_ids' => 'GROUP_CONCAT(ParentNode.object_id ORDER BY ParentNode.tree_left SEPARATOR \',\')',
            ])
            ->innerJoin(
                [$this->Trees->ParentNode->getName() => $this->Trees->ParentNode->getTable()],
                $exp
            );

        // Join with objects to get path unames.
        $query = $query
            ->select([
                // TODO: use QueryExpressions for this:
                'path' => 'GROUP_CONCAT(Objects.uname ORDER BY ParentNode.tree_left SEPARATOR \'/\')',
            ])
            ->innerJoin(
                [$this->Trees->ParentNode->Objects->getName() => $this->Trees->ParentNode->Objects->getTable()],
                $query->newExpr()
                    ->eq(
                        new IdentifierExpression($this->Trees->ParentNode->Objects->aliasField('id')),
                        new IdentifierExpression($this->Trees->ParentNode->aliasField('object_id'))
                    )
            );

        return $query->disableHydration();
    }

    /**
     * Load menu children.
     *
     * @param string|int $id The id or uname of the parent folder.
     * @param array|null $options Additional options (e.g.: `['include' => 'poster']`).
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['poster' => 2]`).
     * @return \Cake\ORM\Query
     */
    public function loadMenuChildren(string $id, ?array $options = null, ?array $hydrate = null): Query
    {
        return $this->loader->loadRelatedObjects($id, 'folders', 'children', [], $options, $hydrate)
            ->where([$this->Trees->aliasField('menu') => true])
            ->order([$this->Trees->aliasField('tree_left') => 'ASC'], true);
    }

    /**
     * Load a menu graph of folders with param `menu = true`.
     *
     * @param string $id The id or uname of the parent folder.
     * @param array|null $options Additional options (e.g.: `['include' => 'poster']`).
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['poster' => 2]`).
     * @param int $depth The depth of the menu for recursive loading.
     * @return \BEdita\Core\Model\Entity\Folder The root menu folder.
     */
    public function loadMenu(string $id, ?array $options = null, ?array $hydrate = null, ?int $depth = 3): Folder
    {
        $folder = $this->loader->loadObject($id, 'folders', $options, $hydrate);
        $folder['children'] = $this->loadChildrenRecursively($folder, $depth, $options, $hydrate);

        return $folder;
    }

    /**
     * Load menu children of a folder.
     *
     * @param \BEdita\Core\Model\Entity\Folder $folder The folder entity.
     * @param int $depth The depth of the menu for recursive loading.
     * @param array|null $options Additional options (e.g.: `['include' => 'poster']`).
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['poster' => 2]`).
     * @return array<\BEdita\Core\Model\Entity\ObjectEntity>|null A list of children entities.
     */
    protected function loadChildrenRecursively(Folder $folder, int $depth, ?array $options = null, ?array $hydrate = null): ?array
    {
        if ($depth === 0) {
            return null;
        }

        return collection($this->loadMenuChildren($folder->uname, $options, $hydrate))
            ->map(function (ObjectEntity $obj) use ($depth, $options, $hydrate) {
                if ($obj->type !== 'folders') {
                    return $obj;
                }

                $obj['children'] = $this->loadChildrenRecursively($obj, $depth - 1, $options, $hydrate);

                return $obj;
            })->toList();
    }
}

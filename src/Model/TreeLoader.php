<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Model;

use BEdita\Core\Model\Entity\Folder;
use BEdita\Core\Model\Entity\ObjectEntity;
use BEdita\Core\Model\Table\TreesTable;
use Cake\Collection\CollectionInterface;
use Cake\Database\Expression\CommonTableExpression;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\Database\Query\SelectQuery;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query;

/**
 * Class TreeLoader.
 */
class TreeLoader
{
    use LocatorAwareTrait;

    /**
     * Objects loader instance.
     *
     * @var \Chialab\FrontendKit\Model\ObjectsLoader
     */
    protected ObjectsLoader $loader;

    /**
     * Trees table.
     *
     * @var \BEdita\Core\Model\Table\TreesTable
     */
    public TreesTable $Trees;

    /**
     * Tree loader constructor.
     *
     * @param \Chialab\FrontendKit\Model\ObjectsLoader $loader Objects loader instance.
     */
    public function __construct(ObjectsLoader $loader)
    {
        $this->loader = $loader;

        $this->Trees = $this->fetchTable('Trees');
    }

    /**
     * Load all objects in a path.
     *
     * @param string $path Path.
     * @param int|null $relativeTo ID of parent relative to which compute paths.
     * @return \Cake\Collection\CollectionInterface|array<\BEdita\Core\Model\Entity\ObjectEntity> List of objects, the root element in the path being the first in the list, the leaf being the latter.
     */
    public function loadObjectPath(string $path, int|null $relativeTo = null): CollectionInterface
    {
        $parts = array_filter(explode('/', $path));
        $path = implode('/', $parts);
        $leaf = $this->loader->loadObject(array_pop($parts), 'objects', [], []);

        $found = $this->getObjectPaths($leaf->id, $relativeTo)
            ->andWhere(['paths.path' => $path]);

        $found = $found->firstOrFail();

        $ids = array_reverse(json_decode($found['reverse_path_ids']));
        array_pop($ids);

        if (empty($ids)) {
            return collection([$leaf]);
        }

        return $this->loader->loadObjects(['id' => $ids], 'folders')
            ->all()
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
    public function getViablePaths(int $id, int|null $relativeTo, int|null $via = null): array
    {
        $query = $this->getObjectPaths($id, $relativeTo);
        if ($via !== null) {
            $query = $query->andWhere(
                fn(QueryExpression $exp): QueryExpression => $exp->add(new FunctionExpression(
                    'JSON_CONTAINS',
                    [
                        new IdentifierExpression('paths.reverse_path_ids'),
                        $via,
                        '$',
                    ],
                    ['json', 'json', 'string'],
                )),
            );
        }

        return $query->all()->toList();
    }

    /**
     * Prepare query to find all tree paths given an object ID, optionally computing relative paths to another object.
     *
     * @param int $id Object ID.
     * @param int|null $relativeTo Object ID relative to which paths should be computed.
     * @return \Cake\ORM\Query
     */
    protected function getObjectPaths(int $id, int|null $relativeTo = null): Query\SelectQuery
    {
        return $this->Trees->find()
            ->useReadRole()
            ->disableHydration()
            ->with(
                fn(CommonTableExpression $cte, SelectQuery $query): CommonTableExpression => $cte
                    ->recursive()
                    ->name('paths')
                    ->field(['canonical', 'reverse_path_ids', 'path', 'parent_id'])
                    ->query(
                        $this->Trees->find()
                            ->select([
                                $this->Trees->aliasField('canonical'),
                                new FunctionExpression('JSON_ARRAY', [
                                    new IdentifierExpression($this->Trees->aliasField('object_id')),
                                ]),
                                $this->Trees->Objects->aliasField('uname'),
                                $this->Trees->aliasField('parent_id'),
                            ])
                            ->innerJoinWith($this->Trees->Objects->getName())
                            ->where([$this->Trees->aliasField('object_id') => $id])

                            ->unionAll(
                                $this->Trees->find()
                                    ->select([
                                        'paths.canonical',
                                        new FunctionExpression('JSON_ARRAY_APPEND', [
                                            new IdentifierExpression('paths.reverse_path_ids'),
                                            '$',
                                            new IdentifierExpression($this->Trees->aliasField('object_id')),
                                        ]),
                                        $query->func()->concat([
                                            new IdentifierExpression($this->Trees->Objects->aliasField('uname')),
                                            '/',
                                            new IdentifierExpression('paths.path'),
                                        ]),
                                        $this->Trees->aliasField('parent_id'),
                                    ])
                                    ->innerJoinWith($this->Trees->Objects->getName())
                                    ->innerJoin(
                                        'paths',
                                        fn(QueryExpression $exp): QueryExpression => $exp
                                            ->equalFields('paths.parent_id', $this->Trees->aliasField('object_id')),
                                    ),
                            ),
                    ),
            )
            ->select([
                'canonical' => 'paths.canonical',
                'reverse_path_ids' => 'paths.reverse_path_ids',
                'path' => 'paths.path',
            ])
            ->from('paths')
            ->where(
                fn(QueryExpression $exp): QueryExpression => $relativeTo
                    ? $exp->eq('paths.parent_id', $relativeTo)
                    : $exp->isNull('paths.parent_id'),
            )
            ->orderDesc('paths.canonical');
    }

    /**
     * Load menu children.
     *
     * @param string|int $id The id or uname of the parent folder.
     * @param array|null $options Additional options (e.g.: `['include' => 'poster']`).
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['poster' => 2]`).
     * @return \Cake\ORM\Query
     */
    public function loadMenuChildren(string $id, array|null $options = null, array|null $hydrate = null): Query
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
    public function loadMenu(string $id, array|null $options = null, array|null $hydrate = null, int|null $depth = 3): Folder
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
    protected function loadChildrenRecursively(Folder $folder, int $depth, array|null $options = null, array|null $hydrate = null): array|null
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

<?php
namespace Chialab\Frontend\Controller\Component;

use BEdita\Core\Model\Action\GetObjectAction;
use BEdita\Core\Model\Action\ListObjectsAction;
use BEdita\Core\Model\Entity\ObjectEntity;
use BEdita\Core\Model\Entity\ObjectType;
use BEdita\I18n\Core\I18nTrait;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Controller\Component;
use Cake\Datasource\ModelAwareTrait;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Iterator;

/**
 * Objects component
 *
 * @property-read \BEdita\Core\Model\Table\ObjectsTable $Objects
 * @property-read \BEdita\Core\Model\Table\ObjectTypesTable $ObjectTypes
 * @property-read \Cake\Controller\Component\PaginatorComponent $Paginator
 */
class ObjectsComponent extends Component
{

    use I18nTrait;
    use ModelAwareTrait;

    /** {@inheritDoc} */
    public $components = ['Paginator'];

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'objectTypesConfig' => [],
        'autoHydrateAssociations' => [
            // property name => max depth,
            'children' => 2,
        ],
    ];

    /** {@inheritDoc} */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->loadModel('Objects');
        $this->loadModel('ObjectTypes');
    }

    /**
     * Fetch an object by its ID or uname.
     *
     * @param string|int $id Object ID or uname.
     * @param string $type Object type name.
     * @param array|null $options Additional options (e.g.: `['include' => 'children']`).
     * @return \BEdita\Core\Model\Entity\ObjectEntity
     */
    public function loadObject(string $id, string $type = 'objects', ?array $options = null): ObjectEntity
    {
        // Normalize ID, get type.
        $id = $this->Objects->getId($id);
        $objectType = $this->ObjectTypes->get($type);

        return $this->loadSingle($id, $objectType, $options);
    }

    /**
     * Fetch multiple objects.
     *
     * @param array $filter Filters.
     * @param string $type Object type name.
     * @param array|null $options Additional options (e.g.: `['include' => 'children']`)
     * @return \Cake\Collection\CollectionInterface|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    public function loadObjects(array $filter, string $type = 'objects', ?array $options = null): CollectionInterface
    {
        // Get type.
        $objectType = $this->ObjectTypes->get($type);

        return $this->loadMulti($objectType, $filter, $options);
    }

    /**
     * Hydrate an heterogeneous list of objects to their type-specific properties and relations.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity[] $items List of objects.
     * @return \Cake\Collection\CollectionInterface|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    public function hydrateObjects(array $objects): CollectionInterface
    {
        return $this->toConcreteTypes($objects, 1);
    }

    /**
     * Load a single object knowing its ID and object type.
     *
     * @param int $id Object ID.
     * @param \BEdita\Core\Model\Entity\ObjectType $objectType Object type.
     * @param array|null $options Options.
     * @param int $depth Depth level.
     * @return \BEdita\Core\Model\Entity\ObjectEntity
     */
    protected function loadSingle(int $primaryKey, ObjectType $objectType, ?array $options, int $depth = 1): ObjectEntity
    {
        // Fetch default options.
        if ($options === null) {
            $options = $this->getDefaultOptions($objectType);
        }

        $table = TableRegistry::getTableLocator()->get($objectType->alias);
        $lang = $this->getLang();
        $contain = static::prepareContains(Hash::get($options, 'include', ''));

        $action = new GetObjectAction(compact('objectType', 'table'));
        /** @var \BEdita\Core\Model\Entity\ObjectEntity $object */
        $object = $action(compact('primaryKey', 'lang', 'contain'));

        return $this->autoHydrateAssociations([$object], $depth)->first();
    }

    /**
     * Load multiple object of a single type applying some filters.
     *
     * @param \BEdita\Core\Model\Entity\ObjectType $objectType Object type.
     * @param array $filter Filters.
     * @param array|null $options Options.
     * @param int $depth Depth level.
     * @return \Cake\Collection\CollectionInterface|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    protected function loadMulti(ObjectType $objectType, array $filter, ?array $options, int $depth = 1): CollectionInterface
    {
        // Fetch default options.
        if ($options === null) {
            $options = $this->getDefaultOptions($objectType);
        }
        $filter += Hash::get($options, 'filter', []);

        $table = TableRegistry::getTableLocator()->get($objectType->alias);
        $lang = $this->getLang();
        $contain = static::prepareContains(Hash::get($options, 'include', ''));

        $action = new ListObjectsAction(compact('objectType', 'table'));
        /** @var \Cake\ORM\Query $query */
        $query = $action(compact('filter', 'lang', 'contain'));

        return $this->autoHydrateAssociations($query->all(), $depth);
    }

    /**
     * Given a set of objects, re-map them to their concrete type implementation.
     *
     * @param iterable|\BEdita\Core\Model\Entity\ObjectEntity[] $objects Objects to re-map to their concrete types.
     * @param int $depth Depth level.
     * @return \Cake\Collection\CollectionInterface|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    protected function toConcreteTypes(iterable $objects, int $depth): CollectionInterface
    {
        $objects = new Collection($objects);
        $sortedIds = $objects->extract('id')->toList();

        return $objects
            ->combine(
                'id',
                function (ObjectEntity $object): ObjectEntity {
                    return $object;
                },
                'type'
            )
            ->unfold(function (iterable $items, string $type) use ($depth): Iterator {
                $objectType = $this->ObjectTypes->get($type);
                $filter = [
                    'id' => array_unique((new Collection($items))->extract('id')->toList()),
                ];

                yield from $this->loadMulti($objectType, $filter, null, $depth);
            })
            ->sortBy(function (ObjectEntity $object) use ($sortedIds): int {
                return array_search($object->id, $sortedIds);
            }, SORT_ASC)
            ->compile();
    }

    /**
     * Automatically hydrate related objects, up to the configured maximum depth.
     *
     * @param iterable|\BEdita\Core\Model\Entity\ObjectEntity[] $objects Objects whose related resources must be hydrated.
     * @param int $depth Maximum depth.
     * @return \Cake\Collection\CollectionInterface|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    protected function autoHydrateAssociations(iterable $objects, int $depth): CollectionInterface
    {
        $objects = new Collection($objects);
        $associations = array_keys(
            array_filter(
                $this->getConfig('autoHydrateAssociations', []),
                function (int $maxDepth) use ($depth): bool {
                    return $maxDepth === -1 || $maxDepth > $depth;
                }
            )
        );
        if (empty($associations)) {
            return $objects;
        }

        return $objects
            ->each(function (ObjectEntity $object) use ($associations, $depth): void {
                foreach ($associations as $prop) {
                    if (!$object->has($prop) || $object->isEmpty($prop)) {
                        continue;
                    }

                    $related = $object->get($prop);
                    if ($related instanceof ObjectEntity) {
                        $original = $related;

                        $objectType = $this->ObjectTypes->get($related->type);
                        $related = $this->loadSingle($related->id, $objectType, null, $depth + 1);
                        if (!$original->isEmpty('_joinData')) {
                            $related->set('relation', $original->get('_joinData'));
                            $related->clean();
                        }

                        $object->set($prop, $related);

                        continue;
                    }

                    $original = (new Collection($related))->indexBy('id')->toArray();

                    $related = $this->toConcreteTypes($related, $depth + 1)
                        ->each(function (ObjectEntity $rel) use ($original): void {
                            $orig = Hash::get($original, $rel->id);
                            if ($orig === null || $orig->isEmpty('_joinData')) {
                                return;
                            }

                            $rel->set('relation', $orig->get('_joinData'));
                            $rel->clean();
                        });
                    $object->set($prop, $related);
                }

                $object->clean();
            });
    }

    /**
     * Get default options for an object type. If no options are set for the type,
     * options for the parent types (abstract types) are checked.
     *
     * @param \BEdita\Core\Model\Entity\ObjectType $objectType Object type.
     * @return array
     */
    protected function getDefaultOptions(ObjectType $objectType): array
    {
        $options = $this->getConfig(sprintf('objectTypesConfig.%s', $objectType->name));
        if ($options !== null) {
            return $options;
        }
        if ($objectType->parent_id === null) {
            return [];
        }

        $parent = $this->ObjectTypes->get($objectType->parent_id);

        return $this->getDefaultOptions($parent);
    }

    /**
     * Parse include comma-delimited string into Cake-compatible contains.
     *
     * @param string $include Included associations.
     * @return string[]
     */
    protected static function prepareContains(string $include): array
    {
        $contains = explode(',', $include);

        return array_filter(array_map(function (string $assoc): string {
            return Inflector::camelize(trim($assoc));
        }, $contains));
    }
}

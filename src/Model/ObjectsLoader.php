<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Model;

use BEdita\Core\Model\Action\GetObjectAction;
use BEdita\Core\Model\Action\ListObjectsAction;
use BEdita\Core\Model\Entity\ObjectEntity;
use BEdita\Core\Model\Entity\ObjectType;
use BEdita\I18n\Core\I18nTrait;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\ModelAwareTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Iterator;

/**
 * Objects loader.
 *
 * @package Chialab\FrontendKit\Model
 *
 * @property \BEdita\Core\Model\Table\ObjectTypesTable $ObjectTypes
 * @property \BEdita\Core\Model\Table\ObjectsTable $Objects
 */
class ObjectsLoader
{
    use I18nTrait;
    use LocatorAwareTrait;
    use ModelAwareTrait;

    /**
     * Loading configuration on a per-object type basis.
     *
     * @var array[]
     */
    protected $objectTypesConfig = [];

    /**
     * Map of associations that need to be hydrated to the actual object types
     * every time they are fetched, to the maximum depth for this auto-hydration.
     *
     * @var int[]
     */
    protected $autoHydrateAssociations = [];

    /**
     * Objects loader constructor.
     *
     * @param array $objectTypesConfig Loading configuration on a per-object type basis.
     * @param array $autoHydrateAssociations Map of associations to be hydrated on each load.
     */
    public function __construct(array $objectTypesConfig = [], array $autoHydrateAssociations = [])
    {
        $this->objectTypesConfig = $objectTypesConfig;
        $this->autoHydrateAssociations = $autoHydrateAssociations;

        $this->loadModel('ObjectTypes');
        $this->loadModel('Objects');
    }

    /**
     * Fetch an object by its ID or uname.
     *
     * @param string|int $id Object ID or uname.
     * @param string $type Object type name.
     * @param array|null $options Additional options (e.g.: `['include' => 'children']`).
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['children' => 2]`).
     * @return \BEdita\Core\Model\Entity\ObjectEntity
     */
    public function loadObject(string $id, string $type = 'objects', ?array $options = null, ?array $hydrate = null): ObjectEntity
    {
        // Normalize ID, get type.
        $id = $this->Objects->getId($id);
        $objectType = $this->ObjectTypes->get($type);

        return $this->loadSingle($id, $objectType, $options, $hydrate);
    }

    /**
     * Fetch multiple objects.
     *
     * @param array $filter Filters.
     * @param string $type Object type name.
     * @param array|null $options Additional options (e.g.: `['include' => 'children']`).
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['children' => 2]`).
     * @return \Cake\ORM\Query|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    public function loadObjects(array $filter, string $type = 'objects', ?array $options = null, ?array $hydrate = null): Query
    {
        // Get type.
        $objectType = $this->ObjectTypes->get($type);

        return $this->loadMulti($objectType, $filter, $options, $hydrate);
    }

    /**
     * Hydrate an heterogeneous list of objects to their type-specific properties and relations.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity[] $objects List of objects.
     * @param array|null $hydrate Auto-hydrate options (e.g.: `['children' => []]`).
     * @return \Cake\Collection\CollectionInterface|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    public function hydrateObjects(array $objects, ?array $hydrate): CollectionInterface
    {
        return $this->toConcreteTypes($objects, $hydrate);
    }

    /**
     * Load a single object knowing its ID and object type.
     *
     * @param int $primaryKey Object ID.
     * @param \BEdita\Core\Model\Entity\ObjectType $objectType Object type.
     * @param array|null $options Options.
     * @param array|null $hydrate Auto-hydrate options (e.g.: `['children' => []]`).
     * @return \BEdita\Core\Model\Entity\ObjectEntity
     */
    protected function loadSingle(int $primaryKey, ObjectType $objectType, ?array $options, ?array $hydrate = null): ObjectEntity
    {
        // Fetch default options.
        if ($options === null) {
            $options = $this->getDefaultOptions($objectType);
        }

        $table = $this->getTableLocator()->get($objectType->alias);
        $lang = $this->getLang();
        $contain = static::prepareContains(Hash::get($options, 'include', ''));

        $action = new GetObjectAction(compact('objectType', 'table'));
        /** @var \BEdita\Core\Model\Entity\ObjectEntity $object */
        $object = $action(compact('primaryKey', 'lang', 'contain'));

        return $this->autoHydrateAssociations($this->setJoinData([$object], $contain), $hydrate)->first();
    }

    /**
     * Load multiple object of a single type applying some filters.
     *
     * @param \BEdita\Core\Model\Entity\ObjectType $objectType Object type.
     * @param array $filter Filters.
     * @param array|null $options Options.
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['children' => 2]`).
     * @return \Cake\ORM\Query|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    protected function loadMulti(ObjectType $objectType, array $filter, ?array $options, ?array $hydrate = null): Query
    {
        // Fetch default options.
        if ($options === null) {
            $options = $this->getDefaultOptions($objectType);
        }
        $filter += Hash::get($options, 'filter', []);

        $table = $this->getTableLocator()->get($objectType->alias);
        $lang = $this->getLang();
        $contain = static::prepareContains(Hash::get($options, 'include', ''), false);
        $lateContain = static::prepareContains(Hash::get($options, 'include', ''), true);

        $action = new ListObjectsAction(compact('objectType', 'table'));
        /** @var \Cake\ORM\Query $query */
        $query = $action(compact('filter', 'lang', 'contain'));
        /** @var \Cake\ORM\Table */
        $table = $query->getRepository();

        return $query->formatResults(function (iterable $results) use ($contain, $hydrate, $lateContain, $table): iterable {
            if (!empty($lateContain)) {
                $results = collection($results)->each(function (ObjectEntity $object) use ($lateContain, $table): void {
                    $table->loadInto($object, $lateContain);
                });
            }

            return $this->autoHydrateAssociations($this->setJoinData($results, array_merge($contain, $lateContain)), $hydrate);
        });
    }

    /**
     * Set `relation` property for contained entities, using what is stored in `_joinData`, if present.
     *
     * @param iterable|\BEdita\Core\Model\Entity\ObjectEntity[] $objects List of objects.
     * @param array $containedAssociations List of contained associations.
     * @return \Cake\Collection\CollectionInterface|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    protected function setJoinData(iterable $objects, array $containedAssociations): CollectionInterface
    {
        $associations = array_keys(Hash::normalize($containedAssociations));
        $fix = function (ObjectEntity $e): void {
            if ($e->isEmpty('_joinData')) {
                return;
            }
            $e->set('relation', $e->get('_joinData'));
            $e->setDirty('relation', false);
        };

        return collection($objects)
            ->each(function (ObjectEntity $object) use ($associations, $fix): void {
                $table = $this->getTableLocator()->get($object->getSource());
                foreach ($associations as $name) {
                    if (!$table->associations()->has($name)) {
                        continue;
                    }
                    $prop = $table->getAssociation($name)->getProperty();

                    if (!$object->has($prop) || $object->isEmpty($prop)) {
                        continue;
                    }
                    $related = $object->get($prop);
                    if ($related instanceof ObjectEntity) {
                        $fix($related);

                        return;
                    }

                    (new Collection($related))->each($fix);
                }
            });
    }

    /**
     * Given a set of objects, re-map them to their concrete type implementation.
     *
     * @param iterable|\BEdita\Core\Model\Entity\ObjectEntity[] $objects Objects to re-map to their concrete types.
     * @param array|null $hydrate Auto-hydrate options (e.g.: `['children' => []]`).
     * @return \Cake\Collection\CollectionInterface|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    protected function toConcreteTypes(iterable $objects, ?array $hydrate): CollectionInterface
    {
        $objects = new Collection($objects);
        $sortedIds = $objects->extract('id')->toList();

        return $objects
            ->combine('id', fn (ObjectEntity $object): ObjectEntity => $object, 'type')
            ->unfold(function (iterable $items, string $type) use ($hydrate): Iterator {
                $objectType = $this->ObjectTypes->get($type);
                $filter = [
                    'id' => array_unique((new Collection($items))->extract('id')->toList()),
                ];

                yield from $this->loadMulti($objectType, $filter, null, $hydrate);
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
     * @param array|null $hydrate Auto-hydrate options (e.g.: `['children' => 2]`).
     * @return \Cake\Collection\CollectionInterface|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    protected function autoHydrateAssociations(iterable $objects, ?array $hydrate = null): CollectionInterface
    {
        if (!($objects instanceof CollectionInterface)) {
            $objects = new Collection($objects);
        }

        if ($hydrate === null) {
            $hydrate = $this->autoHydrateAssociations;
        }

        if ($hydrate == null) {
            return $objects;
        }

        return $objects
            ->each(function (ObjectEntity $object) use ($hydrate): void {
                foreach ($hydrate as $prop => $deepHydrate) {
                    if (!$object->has($prop) || $object->isEmpty($prop)) {
                        continue;
                    }

                    $related = $object->get($prop);
                    if ($related instanceof ObjectEntity) {
                        $original = $related;

                        $objectType = $this->ObjectTypes->get($related->type);
                        $related = $this->loadSingle($related->id, $objectType, $deepHydrate);
                        if (!$original->isEmpty('_joinData')) {
                            $related->set('relation', $original->get('_joinData'));
                            $related->clean();
                        }

                        $object->set($prop, $related);

                        continue;
                    }

                    $original = (new Collection($related))->indexBy('id')->toArray();

                    $related = $this->toConcreteTypes($related, $deepHydrate)
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
        if (isset($this->objectTypesConfig[$objectType->name])) {
            return $this->objectTypesConfig[$objectType->name];
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
     * @param bool|null $limited Include only associations with or without a limit.
     * @return array
     */
    protected static function prepareContains(string $include, ?bool $limited = null): array
    {
        $contains = explode(',', $include);

        return array_reduce($contains, function (array $contains, string $spec) use ($limited): array {
            if (empty($spec)) {
                return $contains;
            }

            [$assoc, $limit] = explode('|', $spec, 2) + [null, null];
            $assoc = Inflector::camelize(trim($assoc));

            if (($limited === true && empty($limit)) || ($limited === false && !empty($limit))) {
                // Not required.
                return $contains;
            }

            if (empty($limit)) {
                $contains[] = $assoc;
                return $contains;
            }

            $contains[$assoc] = fn(Query $query): Query => $query->limit($limit);

            return $contains;
        }, []);
    }
}

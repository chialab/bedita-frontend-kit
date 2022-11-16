<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Model;

use BEdita\Core\Model\Action\GetObjectAction;
use BEdita\Core\Model\Action\ListObjectsAction;
use BEdita\Core\Model\Action\ListRelatedObjectsAction;
use BEdita\Core\Model\Entity\ObjectEntity;
use BEdita\Core\Model\Entity\ObjectType;
use BEdita\Core\Model\Entity\Translation;
use BEdita\I18n\Core\I18nTrait;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Datasource\ModelAwareTrait;
use Cake\ORM\Entity;
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

        $this->loadModel('BEdita/Core.ObjectTypes');
        $this->loadModel('BEdita/Core.Objects');
    }

    /**
     * Fetch an object by its ID or uname.
     *
     * @param string|int $id Object ID or uname.
     * @param string $type Object type name.
     * @param array|null $options Additional options (e.g.: `['include' => 'poster']`).
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['poster' => 2]`).
     * @return \BEdita\Core\Model\Entity\ObjectEntity
     */
    public function loadObject(string $id, string $type = 'objects', ?array $options = null, ?array $hydrate = null): ObjectEntity
    {
        // Normalize ID, get type.
        $id = $this->Objects->getId($id);
        $objectType = $this->ObjectTypes->get($type);

        return $this->loadSingle($id, $objectType, $options, 1, $hydrate);
    }

    /**
     * Fetch an object by its ID or uname and hydrate all its relations.
     *
     * @param string|int $id Object ID or uname.
     * @param string|null $type Object type name.
     * @param array|null $options Additional options (e.g.: `['include' => 'poster']`).
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['poster' => 2]`).
     * @return \BEdita\Core\Model\Entity\ObjectEntity
     */
    public function loadFullObject(string $id, ?string $type = null, ?array $options = null, ?array $hydrate = null): ObjectEntity
    {
        // Normalize ID, get type.
        $id = $this->Objects->getId($id);
        if ($type === null) {
            $type = $this->Objects->get($id)->type;
        }
        $objectType = $this->ObjectTypes->get($type);

        if ($options === null) {
            $options = [];
        }

        if (!isset($options['include'])) {
            $exclude = explode(',', Hash::get($this->getDefaultOptions($objectType), 'exclude', ''));

            $relations = array_merge($objectType->relations, ['parents', 'translations']);
            $relations = array_diff($relations, $exclude);
            $options['include'] = implode(',', $relations);
        } else {
            $relations = explode(',', $options['include']);
        }

        if ($hydrate === null) {
            $hydrate = array_reduce($relations, fn ($acc, $rel) => $acc + [$rel => 2], []);
        }

        return $this->loadObject((string)$id, $type, $options, $hydrate);
    }

    /**
     * Fetch multiple objects.
     *
     * @param array $filter Filters.
     * @param string $type Object type name.
     * @param array|null $options Additional options (e.g.: `['include' => 'poster']`).
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['poster' => 2]`).
     * @return \Cake\ORM\Query|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    public function loadObjects(array $filter, string $type = 'objects', ?array $options = null, ?array $hydrate = null): Query
    {
        // Get type.
        $objectType = $this->ObjectTypes->get($type);

        return $this->loadMulti($objectType, $filter, $options, 1, $hydrate);
    }

    /**
     * Fetch related objects.
     *
     * @param string|int $id Object ID or uname.
     * @param string $type Object type name.
     * @param string $relation The relation name.
     * @param array|null $filter Relation objects filter (e.g. `['query' => 'doc']`).
     * @param array|null $options Additional options (e.g.: `['include' => 'poster']`).
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['poster' => 2]`).
     * @return \Cake\ORM\Query|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    public function loadRelatedObjects(string $id, string $type, string $relation, ?array $filter = null, ?array $options = null, ?array $hydrate = null): Query
    {
        // Normalize ID, get type.
        $id = $this->Objects->getId($id);
        $objectType = $this->ObjectTypes->get($type);

        return $this->loadRelated($id, $objectType, $relation, $filter, $options, 1, $hydrate);
    }

    /**
     * Hydrate an heterogeneous list of objects to their type-specific properties and relations.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity[] $objects List of objects.
     * @return \Cake\Collection\CollectionInterface|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    public function hydrateObjects(array $objects): CollectionInterface
    {
        return $this->toConcreteTypes($objects, 1);
    }

    /**
     * Load a single object knowing its ID and object type.
     *
     * @param int $primaryKey Object ID.
     * @param \BEdita\Core\Model\Entity\ObjectType $objectType Object type.
     * @param array|null $options Options.
     * @param int $depth Depth level.
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['poster' => 2]`).
     * @return \BEdita\Core\Model\Entity\ObjectEntity
     */
    protected function loadSingle(int $primaryKey, ObjectType $objectType, ?array $options, int $depth = 1, ?array $hydrate = null): ObjectEntity
    {
        // Fetch default options.
        if ($options === null) {
            $options = $this->getDefaultOptions($objectType);
        }

        $table = $this->getTableLocator()->get($objectType->alias);
        $lang = Hash::get($options, 'lang', $this->getLang());
        $contain = static::prepareContains(Hash::get($options, 'include', ''));

        $action = new GetObjectAction(compact('objectType', 'table'));
        /** @var \BEdita\Core\Model\Entity\ObjectEntity $object */
        $object = $action(static::includeTranslations($contain) ? compact('primaryKey', 'contain') : compact('primaryKey', 'lang', 'contain'));

        return $this->autoHydrateAssociations($this->setJoinData([$object], $contain), $depth, $hydrate)
            ->map(fn (ObjectEntity $object): ObjectEntity => $this->dangerouslyTranslateFields($object, $lang))
            ->first();
    }

    /**
     * Load multiple object of a single type applying some filters.
     *
     * @param \BEdita\Core\Model\Entity\ObjectType $objectType Object type.
     * @param array $filter Filters.
     * @param array|null $options Options.
     * @param int $depth Depth level.
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['poster' => 2]`).
     * @return \Cake\ORM\Query|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    protected function loadMulti(ObjectType $objectType, array $filter, ?array $options, int $depth = 1, ?array $hydrate = null): Query
    {
        // Fetch default options.
        if ($options === null) {
            $options = $this->getDefaultOptions($objectType);
        }

        $lang = Hash::get($options, 'lang', $this->getLang());
        $filter += Hash::get($options, 'filter', []);
        $contain = static::prepareContains(Hash::get($options, 'include', ''), false);
        $lateContain = static::prepareContains(Hash::get($options, 'include', ''), true);
        $table = $this->getTableLocator()->get($objectType->alias);

        $action = new ListObjectsAction(compact('objectType', 'table'));
        /** @var \Cake\ORM\Query $query */
        $query = $action(static::includeTranslations($contain) ? compact('filter', 'contain') : compact('filter', 'lang', 'contain'));
        /** @var \Cake\ORM\Table */
        $table = $query->getRepository();

        return $query->formatResults(function (iterable $results) use ($contain, $depth, $hydrate, $lateContain, $table, $lang): iterable {
            if (!empty($lateContain)) {
                $results = collection($results)->each(function (ObjectEntity $object) use ($lateContain, $table): void {
                    $table->loadInto($object, $lateContain);
                });
            }

            return $this->autoHydrateAssociations($this->setJoinData($results, array_merge($contain, $lateContain)), $depth, $hydrate)
                ->map(fn (ObjectEntity $object): ObjectEntity => $this->dangerouslyTranslateFields($object, $lang));
        });
    }

    /**
     * Load and hydrate related objects.
     *
     * @param int $primaryKey Object ID.
     * @param \BEdita\Core\Model\Entity\ObjectType $objectType Object type.
     * @param string $relation The relation name to load.
     * @param array|null $filter Filters.
     * @param array|null $options Options.
     * @param int $depth Depth level.
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['poster' => 2]`).
     * @return \Cake\ORM\Query
     */
    protected function loadRelated(int $primaryKey, ObjectType $objectType, string $relation, ?array $filter, ?array $options, int $depth = 1, ?array $hydrate = null): Query
    {
        // Fetch default options.
        if ($options === null) {
            $options = $this->getDefaultOptions($objectType);
        }

        $lang = Hash::get($options, 'lang', $this->getLang());
        $contain = static::prepareContains(Hash::get($options, 'include', ''), false);
        $table = $this->getTableLocator()->get($objectType->alias);
        $association = $table->getAssociation($relation);
        $action = new ListRelatedObjectsAction(compact('association'));

        /** @var \Cake\ORM\Query $query */
        $query = $action(compact('primaryKey', 'filter', 'lang'));

        return $query->formatResults(function (iterable $results) use ($contain, $depth, $hydrate, $table, $lang): iterable {
            $objects = $this->toConcreteTypes($results, $depth + 1);
            if (!empty($contain)) {
                $objects = collection($objects)->each(function (ObjectEntity $object) use ($contain, $table): void {
                    $table->loadInto($object, $contain);
                });
            }

            return $this->autoHydrateAssociations($this->setJoinData($objects, $contain), $depth, $hydrate)
                ->map(function (ObjectEntity $object) use ($results, $lang): ObjectEntity {
                    $original = collection($results)->filter(fn (ObjectEntity $orig): bool => $orig->id === $object->id)->first();
                    if (!$original->isEmpty('_joinData')) {
                        $object->set('relation', $original->get('_joinData'));
                        $object->clean();
                    }

                    return $this->dangerouslyTranslateFields($object, $lang);
                });
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

                    if ($related instanceof Entity) {
                        // related entity is not a BEdita object
                        return;
                    }

                    (new Collection($related))->each(function (Entity $e) use ($fix) {
                        // related entity (such as a Translation) may not be an ObjectEntity.
                        if ($e instanceof ObjectEntity) {
                            $fix($e);
                        }
                    });
                }
            });
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
            ->combine('id', fn (Entity $object): Entity => $object, 'type')
            ->unfold(function (iterable $items, string $type) use ($depth): Iterator {
                if (!$type) {
                    yield from $items;

                    return;
                }

                $objectType = $this->ObjectTypes->get($type);
                $filter = [
                    'id' => array_unique((new Collection($items))->extract('id')->toList()),
                ];

                yield from $this->loadMulti($objectType, $filter, null, $depth);
            })
            ->sortBy(function (Entity $object) use ($sortedIds): int {
                return array_search($object->id, $sortedIds);
            }, SORT_ASC)
            ->compile();
    }

    /**
     * Dangerous processor to set fields to their translated counterparts.
     *
     * **WARNING**: do NOT save entities that have been processed by this processor.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity $object Object to process.
     * @param string|null $lang Language code to use.
     * @return \BEdita\Core\Model\Entity\ObjectEntity
     */
    protected function dangerouslyTranslateFields(ObjectEntity $object, ?string $lang): ObjectEntity
    {
        if ($lang === null || $lang === $object->lang) {
            return $object;
        }

        /** @var \BEdita\Core\Model\Entity\Translation|null $requestedTranslation */
        $requestedTranslation = collection($object->translations ?? [])
            ->filter(fn (Translation $tr): bool => $tr->lang === $lang)
            ->first();
        if ($requestedTranslation === null) {
            return $object;
        }

        $originalFields = [
            'lang' => $object->lang,
        ];
        $object->lang = $requestedTranslation->lang;
        $object->setDirty('lang', false);

        foreach ($requestedTranslation->translated_fields as $field => $value) {
            if (empty($value)) {
                continue;
            }

            $originalFields[$field] = $object->get($field);
            $object->set($field, $value);
            $object->setDirty($field, false);
        }

        $object->set('_originalFields', $originalFields);
        $object->setDirty('_originalFields', false);

        return $object;
    }

    /**
     * Automatically hydrate related objects, up to the configured maximum depth.
     *
     * @param iterable|\BEdita\Core\Model\Entity\ObjectEntity[] $objects Objects whose related resources must be hydrated.
     * @param int $depth Maximum depth.
     * @param array|null $options Override auto-hydrate options (e.g.: `['poster' => 2]`).
     * @return \Cake\Collection\CollectionInterface|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    protected function autoHydrateAssociations(iterable $objects, int $depth, ?array $options = null): CollectionInterface
    {
        if (!($objects instanceof CollectionInterface)) {
            $objects = new Collection($objects);
        }

        $associations = $this->getAssociationsToHydrate($depth, $options);
        if (empty($associations)) {
            return $objects;
        }

        return $objects
            ->each(function (ObjectEntity $object) use ($associations, $depth, $options): void {
                foreach ($associations as $prop) {
                    if (!$object->has($prop) || $object->isEmpty($prop)) {
                        continue;
                    }

                    $related = $object->get($prop);
                    if ($related instanceof ObjectEntity) {
                        $original = $related;

                        $objectType = $this->ObjectTypes->get($related->type);
                        $related = $this->loadSingle($related->id, $objectType, null, $depth + 1, $options);
                        if (!$original->isEmpty('_joinData')) {
                            $related->set('relation', $original->get('_joinData'));
                            $related->clean();
                        }

                        $object->set($prop, $related);

                        continue;
                    }

                    $original = (new Collection($related))->indexBy('id')->toArray();

                    $related = $this->toConcreteTypes($related, $depth + 1)
                        ->each(function (Entity $rel) use ($original): void {
                            if (!$rel instanceof ObjectEntity) {
                                return;
                            }

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
            if (isset($this->objectTypesConfig['objects'])) {
                return $this->objectTypesConfig['objects'];
            }

            return [];
        }

        $parent = $this->ObjectTypes->get($objectType->parent_id);

        return $this->getDefaultOptions($parent);
    }

    /**
     * Get names of associations for which related objects need to be hydrated.
     *
     * @param int $depth Depth level.
     * @param array|null $options Override auto-hydrate options (e.g.: `['poster' => 2]`).
     * @return string[]
     */
    protected function getAssociationsToHydrate(int $depth, ?array $options = null): array
    {
        $hydrateAssociations = $this->autoHydrateAssociations;
        if ($options !== null) {
            $hydrateAssociations = $options;
        }

        return array_keys(
            array_filter(
                $hydrateAssociations,
                function (int $maxDepth) use ($depth): bool {
                    return $maxDepth === -1 || $maxDepth > $depth;
                }
            )
        );
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

    /**
     * Check if translations association is in the contain list.
     *
     * @param array $contains Contain list.
     * @return bool
     */
    protected static function includeTranslations(array $contain): bool
    {
        return in_array('Translations', $contain);
    }
}

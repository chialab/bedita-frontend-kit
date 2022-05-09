<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Controller\Component;

use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Collection\CollectionInterface;
use Cake\Controller\Component;
use Cake\ORM\Query;
use Chialab\FrontendKit\Model\ObjectsLoader;

/**
 * Objects component
 *
 * @property-read \BEdita\Core\Model\Table\ObjectsTable $Objects
 * @property-read \BEdita\Core\Model\Table\ObjectTypesTable $ObjectTypes
 */
class ObjectsComponent extends Component
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'objectTypesConfig' => [
            'objects' => ['include' => 'poster'],
        ],
        'autoHydrateAssociations' => [
            'children' => 3,
        ],
    ];

    /**
     * Objects loader instance.
     *
     * @var \Chialab\FrontendKit\Model\ObjectsLoader
     */
    protected $loader;

    /**
     * @inheritdoc
     */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->loader = new ObjectsLoader(
            $this->getConfig('objectTypesConfig', []),
            $this->getConfig('autoHydrateAssociations', [])
        );
    }

    /**
     * Return objects loader instance used by this component.
     *
     * @return \Chialab\FrontendKit\Model\ObjectsLoader
     */
    public function getLoader(): ObjectsLoader
    {
        return $this->loader;
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
        return $this->loader->loadObject($id, $type, $options, $hydrate);
    }

    /**
     * Fetch an object by its ID or uname and all its relations.
     *
     * @param string|int $id Object ID or uname.
     * @param array|null $options Additional options (e.g.: `['include' => 'children']`).
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['children' => 2]`).
     * @return \BEdita\Core\Model\Entity\ObjectEntity
     */
    public function loadFullObject(string $id, ?array $options = null, ?array $hydrate = null): ObjectEntity
    {
        return $this->loader->loadFullObject($id, $options, $hydrate);
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
        return $this->loader->loadObjects($filter, $type, $options, $hydrate);
    }

    /**
     * Hydrate an heterogeneous list of objects to their type-specific properties and relations.
     *
     * @param \BEdita\Core\Model\Entity\ObjectEntity[] $objects List of objects.
     * @return \Cake\Collection\CollectionInterface|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    public function hydrateObjects(array $objects): CollectionInterface
    {
        return $this->loader->hydrateObjects($objects);
    }

    /**
     * Fetch related objects.
     *
     * @param string|int $id Object ID or uname.
     * @param string $type Object type name.
     * @param string $relation The relation name.
     * @param null $filter Relation objects filter (e.g. `['query' => 'doc']`).
     * @param array|null $options Additional options (e.g.: `['include' => 'children']`).
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['children' => 2]`).
     * @return \Cake\ORM\Query|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    public function loadRelatedObjects(string $id, string $type, string $relation, ?array $filter = null, ?array $options = null, ?array $hydrate = null): Query
    {
        return $this->loader->loadRelatedObjects($id, $type, $relation, $filter, $options, $hydrate);
    }
}

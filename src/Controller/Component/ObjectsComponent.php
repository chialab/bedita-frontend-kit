<?php
namespace Chialab\FrontendKit\Controller\Component;

use BEdita\Core\Model\Entity\ObjectEntity;
use BEdita\Core\Model\Entity\ObjectType;
use Cake\Collection\CollectionInterface;
use Cake\Controller\Component;
use Cake\Datasource\ModelAwareTrait;
use Cake\ORM\Query;
use Chialab\FrontendKit\Model\ObjectsAwareTrait;

/**
 * Objects component
 *
 * @property-read \BEdita\Core\Model\Table\ObjectsTable $Objects
 * @property-read \BEdita\Core\Model\Table\ObjectTypesTable $ObjectTypes
 */
class ObjectsComponent extends Component
{
    use ModelAwareTrait;
    use ObjectsAwareTrait;

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
     * @return \Cake\ORM\Query|\BEdita\Core\Model\Entity\ObjectEntity[]
     */
    public function loadObjects(array $filter, string $type = 'objects', ?array $options = null): Query
    {
        // Get type.
        $objectType = $this->ObjectTypes->get($type);

        return $this->loadMulti($objectType, $filter, $options);
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
     * @inheritdoc
     */
    protected function getOptionsForObjectType(string $objectType): ?array
    {
        return $this->getConfig(sprintf('objectTypesConfig.%s', $objectType));
    }

    /**
     * @inheritdoc
     */
    protected function getAssociationsToHydrate(int $depth): array
    {
        return array_keys(
            array_filter(
                $this->getConfig('autoHydrateAssociations', []),
                function (int $maxDepth) use ($depth): bool {
                    return $maxDepth === -1 || $maxDepth > $depth;
                }
            )
        );
    }
}

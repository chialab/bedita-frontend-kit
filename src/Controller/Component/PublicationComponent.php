<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Controller\Component;

use BEdita\Core\Model\Entity\Folder;
use BEdita\Core\Model\Table\TreesTable;
use Cake\Collection\CollectionInterface;
use Cake\Controller\Component;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\Locator\LocatorAwareTrait;
use Chialab\FrontendKit\Model\ObjectsLoader;
use Chialab\FrontendKit\Model\TreeLoader;
use InvalidArgumentException;

/**
 * Publication component
 *
 * @property-read \Chialab\FrontendKit\Controller\Component\ObjectsComponent $Objects
 */
class PublicationComponent extends Component
{
    use LocatorAwareTrait;

    /**
     * @inheritDoc
     */
    public $components = ['Chialab/FrontendKit.Objects'];

    /**
     * @inheritDoc
     */
    protected $_defaultConfig = [
        'publication' => null,
        'publicationLoader' => [
            'objectTypesConfig' => [],
            'autoHydrateAssociations' => [],
        ],
    ];

    /**
     * Tree loader instance.
     *
     * @var \Chialab\FrontendKit\Model\TreeLoader
     */
    protected TreeLoader $loader;

    /**
     * Current publication
     *
     * @var \BEdita\Core\Model\Entity\Folder
     */
    protected Folder $publication;

    /**
     * Trees table.
     *
     * @var \BEdita\Core\Model\Table\TreesTable
     */
    public TreesTable $Trees;

    /**
     * Initialization hook method.
     *
     * @param array $config Configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->Trees = $this->fetchTable('BEdita/Core.Trees');

        $publicationUname = $this->getConfig('publication');
        if (empty($publicationUname)) {
            throw new InvalidArgumentException('Missing configuration for root folder');
        }

        $this->loader = new TreeLoader($this->Objects->getLoader());

        $publicationLoader = new ObjectsLoader(
            $this->getConfig('publicationLoader.objectTypesConfig', []),
            $this->getConfig('publicationLoader.autoHydrateAssociations', []),
        );

        try {
            $publication = $publicationLoader->loadFullObject($publicationUname, 'folders');
        } catch (RecordNotFoundException $e) {
            throw new NotFoundException(__('Root folder does not exist: {0}', $publicationUname), null, $e);
        }

        $this->publication = $publication;
        $this->getController()->set('publication', $this->publication);
    }

    /**
     * Getter for publication.
     *
     * @return \BEdita\Core\Model\Entity\Folder
     */
    public function getPublication(): Folder
    {
        return $this->publication;
    }

    /**
     * Load all objects in a path.
     *
     * @param string $path Path.
     * @return \Cake\Collection\CollectionInterface|array<\BEdita\Core\Model\Entity\ObjectEntity> List of objects, the root element in the path being the first in the list, the leaf being the latter.
     */
    public function loadObjectPath(string $path): CollectionInterface
    {
        return $this->loader->loadObjectPath($path, $this->getPublication()->id);
    }

    /**
     * Get viable paths for an object ID, optionally restricting to paths that include the requested parent.
     *
     * @param int $id Object ID.
     * @param int|null $via If set, restrict paths that include this parent ID.
     * @return array
     */
    public function getViablePaths(int $id, int|null $via = null): array
    {
        return $this->loader->getViablePaths($id, $this->getPublication()->id, $via);
    }
}

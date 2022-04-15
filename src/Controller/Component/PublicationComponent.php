<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Controller\Component;

use BEdita\Core\Model\Entity\Folder;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Collection\CollectionInterface;
use Cake\Controller\Component;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\ModelAwareTrait;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Chialab\FrontendKit\Model\ObjectsLoader;
use Chialab\FrontendKit\Model\TreeLoader;
use Chialab\FrontendKit\Traits\RenderTrait;
use InvalidArgumentException;

/**
 * Publication component
 *
 * @property-read \Chialab\FrontendKit\Controller\Component\ObjectsComponent $Objects
 * @property-read \BEdita\Core\Model\Table\TreesTable $Trees
 */
class PublicationComponent extends Component
{
    use ModelAwareTrait;
    use RenderTrait;

    /**
     * {@inheritDoc}
     */
    public $components = ['Chialab/FrontendKit.Objects'];

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'menuFolders' => [],
        'publication' => null,
        'publicationLoader' => null,
    ];

    /**
     * Tree loader instance.
     *
     * @var \Chialab\FrontendKit\Model\TreeLoader
     */
    protected $loader;

    /**
     * Current publication
     *
     * @var \BEdita\Core\Model\Entity\Folder
     */
    protected $publication;

    /**
     * Initialization hook method.
     *
     * @param array $config Configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->loadModel('BEdita/Core.Trees');

        $publicationUname = $this->getConfig('publication');
        if (empty($publicationUname)) {
            throw new InvalidArgumentException('Missing configuration for root folder');
        }

        $publicationLoader = $this->Objects->getLoader();
        if ($this->getConfig('publicationLoader') !== null) {
            $publicationLoader = new ObjectsLoader(
                $this->getConfig('publicationLoader.objectTypesConfig', []),
                $this->getConfig('publicationLoader.autoHydrateAssociations', []),
                $this->getConfig('publicationLoader.extraRelations', [])
            );
        }
        $this->loader = new TreeLoader($publicationLoader);

        try {
            $publication = $publicationLoader->loadFullObject($publicationUname, 'folders');
        } catch (RecordNotFoundException $e) {
            throw new NotFoundException(__('Root folder does not exist: {0}', $publicationUname), null, $e);
        }

        $this->publication = $publication;
        $this->getController()->set('publication', $this->publication);

        $menuFoldersConfig = $this->getConfig('menuFolders', []);
        if (empty($menuFoldersConfig)) {
            return;
        }

        $menuFolders = $publicationLoader->loadObjects(['uname' => array_values($menuFoldersConfig)], 'folders')
            ->indexBy(fn (Folder $folder): string => array_search($folder->uname, $menuFoldersConfig))
            ->toArray();

        $this->getController()->set('menuFolders', $menuFolders);
    }

    /**
     * Render a view using the bound controller.
     */
    public function render($view = null, $layout = null)
    {
        return $this->getController()->render($view, $layout);
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
     * Load paginated children.
     *
     * @param \BEdita\Core\Model\Entity\Folder $folder Folder.
     * @param array|null $filter Children filter (e.g. `['query' => 'doc']`).
     * @return \Cake\Collection\CollectionInterface
     */
    protected function loadChildren(Folder $folder, ?array $filter): CollectionInterface
    {
        return $this->getController()->paginate(
            $this->Objects->loadRelatedObjects($folder->uname, 'folders', 'children', $filter)->order([], true),
            [
                'order' => ['Trees.tree_left'],
            ],
        );
    }

    /**
     * Handle specific views/methods according to object tree structure
     *
     * @param string $path Full object path, relative to current publication.
     * @param array $childrenFilters Children filters.
     * @return \Cake\Http\Response
     */
    public function genericTreeAction(string $path = '', array $childrenFilters = []): Response
    {
        $items = $this->loadObjectPath($path)->toList();

        $object = $items[count($items) - 1];
        $ancestors = array_slice($items, 0, -1);
        $parent = end($ancestors) ?: null;

        $this->getController()->set(compact('object', 'parent', 'ancestors'));
        if ($object->type === 'folders') {
            $children = $this->loadChildren($object, $childrenFilters);
            $object['children'] = $children;

            $this->getController()->set(compact('children'));
        }


        return $this->renderFirstTemplate(...$this->getTemplatesToIterate($object, ...array_reverse($ancestors)));
    }

    /**
     * Load all objects in a path.
     *
     * @param string $path Path.
     * @return \Cake\Collection\CollectionInterface|\BEdita\Core\Model\Entity\ObjectEntity[] List of objects, the root element in the path being the first in the list, the leaf being the latter.
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
    public function getViablePaths(int $id, ?int $via = null): array
    {
        return $this->loader->getViablePaths($id, $this->getPublication()->id, $via);
    }
}

<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Controller\Component;

use BEdita\Core\Model\Entity\Folder;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Collection\CollectionInterface;
use Cake\Controller\Component;
use Cake\Datasource\ModelAwareTrait;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\View\Exception\MissingTemplateException;
use Chialab\FrontendKit\Model\TreeLoader;
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

    /** {@inheritDoc} */
    public $components = ['Chialab/FrontendKit.Objects'];

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'menuFolders' => [],
        'publication' => null,
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
     * Initialization hook method.
     *
     * @param array $config Configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->loader = new TreeLoader($this->Objects->getLoader());

        $this->loadModel('Trees');

        $publicationUname = $this->getConfig('publication');
        if (empty($publicationUname)) {
            throw new InvalidArgumentException('Missing configuration for root folder');
        }

        $menuFolders = $this->getConfig('menuFolders', []);
        $uname = array_merge([$publicationUname], array_values($menuFolders));
        $folders = $this->Objects->loadObjects(compact('uname'), 'folders')
            ->indexBy('uname')
            ->toArray();

        if (!isset($folders[$publicationUname])) {
            throw new NotFoundException(__('Root folder does not exist: {0}', $publicationUname));
        }
        $this->publication = $folders[$publicationUname];

        $menuFolders = array_combine(
            array_keys($menuFolders),
            array_map(function (string $uname) use ($folders): ?Folder {
                return $folders[$uname] ?? null;
            }, array_values($menuFolders))
        );

        $this->getController()->set('publication', $this->publication);
        $this->getController()->set('menuFolders', $menuFolders);
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
     * Handle specific views/methods according to object tree structure
     *
     * @param string $path Full object path, relative to current publication.
     * @return \Cake\Http\Response
     */
    public function genericTreeAction(string $path = ''): Response
    {
        $items = $this->loadObjectPath($path)->toList();

        $object = $items[count($items) - 1];
        $ancestors = array_slice($items, 0, -1);
        $parent = end($ancestors) ?: null;

        $this->getController()->set(compact('object', 'parent', 'ancestors'));
        if (!empty($object['children'])) {
            $this->getController()->set(['children' => $object['children'], 'childrenByType' => $object['childrenByType']]);
        }

        // Search template by uname, type or ancestors' uname
        $templates = array_merge(
            empty($path) ? ['home'] : [],
            [$object['uname'], $object['type']],
            array_map(
                function (ObjectEntity $object): string {
                    return $object['uname'];
                },
                array_reverse($ancestors)
            ),
            ['objects']
        );

        return $this->renderFirstTemplate(...$templates);
    }

    /**
     * Render first found template.
     *
     * @param string ...$templates Templates to search.
     * @return \Cake\Http\Response
     */
    public function renderFirstTemplate(string ...$templates): Response
    {
        foreach ($templates as $template) {
            try {
                return $this->getController()->render($template);
            } catch (MissingTemplateException $e) {
                continue;
            }
        }

        throw new MissingTemplateException(__('None of the searched templates was found'));
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

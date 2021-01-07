<?php
namespace Chialab\FrontendKit\Controller\Component;

use BEdita\Core\Model\Entity\Folder;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Collection\CollectionInterface;
use Cake\Controller\Component;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Cake\View\Exception\MissingTemplateException;
use InvalidArgumentException;

/**
 * Publication component
 *
 * @property-read \Chialab\FrontendKit\Controller\Component\ObjectsComponent $Objects
 */
class PublicationComponent extends Component
{

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
        $this->publication->set('uname_path', sprintf('/%s', $this->publication->get('uname')));
        $this->publication->clean();

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
     * @return \Cake\Http\Response
     */
    public function genericTreeAction(string $path = ''): Response
    {
        $path = sprintf('%s/%s', $this->publication->get('uname_path'), $path);
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
            $object['type'] !== 'folders' ? ['object'] : [],
            ['objects']
        );

        return $this->renderFirstTemplate(...$templates);
    }

    /**
     * Load all objects in a path.
     *
     * @param string $path Path.
     * @return \Cake\Collection\CollectionInterface|\BEdita\Core\Model\Entity\ObjectEntity[] List of objects, the root element in the path being the first in the list, the leaf being the latter.
     */
    public function loadObjectPath(string $path): CollectionInterface
    {
        $parts = array_filter(explode('/', $path));
        $leaf = $this->Objects->loadObject(array_pop($parts));
        $leaf = $this->Objects->loadObject($leaf->id, $leaf->type);

        $Trees = TableRegistry::getTableLocator()->get('Trees');
        $found = $Trees->find()
            ->select([
                'Trees.object_id',
                'Trees.parent_id',
                'parent_path' => 'GROUP_CONCAT(Objects.uname ORDER BY ParentNode.tree_left SEPARATOR \'/\')',
                'parent_path_ids' => 'GROUP_CONCAT(ParentNode.object_id ORDER BY ParentNode.tree_left SEPARATOR \',\')',
            ])
            ->innerJoin(['ParentNode' => 'trees'], [
                'ParentNode.root_id = Trees.root_id',
                'ParentNode.tree_left < Trees.tree_left',
                'ParentNode.tree_right > Trees.tree_right',
            ])
            ->innerJoin(['Objects' => 'objects'], ['Objects.id = ParentNode.object_id'])
            ->where([
                'Trees.object_id' => $leaf->id,
            ])
            ->group(['Trees.object_id', 'Trees.parent_id'])
            ->having(['parent_path' => implode('/', $parts)])
            ->firstOrFail();

        $ids = explode(',', $found['parent_path_ids']);

        return $this->Objects->loadObjects(['id' => $ids], 'folders')
            ->sortBy(function (Folder $folder) use ($ids): int {
                return array_search($folder->id, $ids);
            }, SORT_ASC)
            ->append([$leaf]);
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
}

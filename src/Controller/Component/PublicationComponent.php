<?php
namespace Chialab\Frontend\Controller\Component;

use BEdita\Core\Model\Entity\Folder;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Controller\Component;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Cake\View\Exception\MissingTemplateException;
use InvalidArgumentException;

/**
 * Publication component
 *
 * @property-read \Chialab\Frontend\Controller\Component\ObjectsComponent $Objects
 */
class PublicationComponent extends Component
{

    /** {@inheritDoc} */
    public $components = ['Chialab/Frontend.Objects'];

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

        $uname = array_merge([$publicationUname], $this->getConfig('menuFolders', []));
        $menuFolders = $this->Objects->loadObjects(compact('uname'), 'folders')
            ->indexBy('uname')
            ->toArray();

        if (!isset($menuFolders[$publicationUname])) {
            throw new NotFoundException(__('Root folder does not exist: {0}', $publicationUname));
        }
        $this->publication = $menuFolders[$publicationUname];
        $this->publication->set('uname_path', sprintf('/%s', $this->publication->get('uname')));
        $this->publication->clean();
        unset($menuFolders[$publicationUname]);

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
        $items = $this->loadObjectPath($path);

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
     * Load all objects in a path.
     *
     * @param string $path Path.
     * @return \BEdita\Core\Model\Entity\ObjectEntity[] List of objects, the root element in the path being the first in the list, the leaf being the latter.
     */
    public function loadObjectPath(string $path): array
    {
        $parts = array_filter(explode('/', $path));
        $Objects = TableRegistry::getTableLocator()->get('Objects');
        $leaf = $Objects->find()
            ->where([
                $Objects->aliasField('deleted') => false,
                'status IN' => ['on', 'draft'],
                $Objects->aliasField('uname') => array_pop($parts),
            ])
            ->firstOrFail();

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
        $Folders = TableRegistry::getTableLocator()->get('Folders');
        $ancestors = $Folders->find()
            ->where([
                $Folders->aliasField('deleted') => false,
                'status IN' => ['on', 'draft'],
                $Folders->aliasField('id') . ' IN' => $ids,
            ])
            ->toArray();

        usort($ancestors, function (Folder $a, Folder $b) use ($ids): int {
            return array_search($a->id, $ids) <=> array_search($b->id, $ids);
        });
        $ancestors[] = $leaf;

        return $ancestors;
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

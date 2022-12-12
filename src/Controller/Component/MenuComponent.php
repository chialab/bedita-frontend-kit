<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Controller\Component;

use BEdita\Core\Model\Entity\Folder;
use Cake\Controller\Component;
use Cake\Datasource\ModelAwareTrait;
use Chialab\FrontendKit\Model\ObjectsLoader;
use Chialab\FrontendKit\Model\TreeLoader;

/**
 * Menu component
 *
 * @property-read \BEdita\Core\Model\Table\Trees $Trees
 */
class MenuComponent extends Component
{
    use ModelAwareTrait;

    /**
     * Objects loader instance.
     *
     * @var \Chialab\FrontendKit\Model\TreeLoader
     */
    protected TreeLoader $loader;

    /**
     * @inheritDoc
     */
    protected $_defaultConfig = [
        'depth' => 3,
        'menuLoader' => [
            'objectTypesConfig' => [],
            'autoHydrateAssociations' => [
                'children' => 2,
            ],
        ],
    ];

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->loadModel('Trees');

        $menuLoader = new ObjectsLoader(
            $this->getConfig('menuLoader.objectTypesConfig', []),
            $this->getConfig('menuLoader.autoHydrateAssociations', [])
        );

        $this->loader = new TreeLoader($menuLoader);
    }

    /**
     * Load a menu graph of folders with param `menu = true`.
     *
     * @param string $id The id or uname of the parent folder.
     * @param array|null $options Additional options (e.g.: `['include' => 'poster']`).
     * @param array|null $hydrate Override auto-hydrate options (e.g.: `['poster' => 2]`).
     * @param int $depth The depth of the menu for recursive loading.
     * @return \BEdita\Core\Model\Entity\Folder The root menu folder.
     */
    public function load(string $id, array|null $options = null, array|null $hydrate = null, int|null $depth = null): Folder
    {
        if ($depth === null) {
            $depth = $this->getConfig('depth') ?? 1;
        }

        return $this->loader->loadMenu($id, $options, $hydrate, $depth);
    }
}

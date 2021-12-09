<?php
namespace Chialab\FrontendKit\Controller\Component;

use BEdita\Core\Model\Entity\Category;
use Cake\Collection\CollectionInterface;
use Cake\Controller\Component;
use Cake\Datasource\ModelAwareTrait;
use Cake\ORM\Query;
use Cake\Utility\Text;

/**
 * Objects component
 *
 * @property-read \BEdita\Core\Model\Table\CategoriesTable $Categories
 */
class CategoriesComponent extends Component
{

    use ModelAwareTrait;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [];

    /** {@inheritDoc} */
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->loadModel('Categories');
    }

    /**
     * Load categories by object type.
     *
     * @param string|null $type Object type name.
     * @param int|null $parentId ID of parent category.
     * @return \Cake\ORM\Query
     */
    public function load(?string $type = null, ?int $parentId): Query
    {
        $query = $this->Categories->find()
            ->where([$this->Categories->aliasField('enabled') => true]);

        if ($parentId !== null) {
            $query = $query
                ->where([$this->Categories->aliasField('parent_id') => $parentId]);
        }

        if ($type !== null) {
            $query = $query->find('type', [$type]);
        }

        $query
            ->order([$this->Categories->aliasField('name')])
            ->formatResults(function (CollectionInterface $results): CollectionInterface {
                return $results->map(function (Category $category): Category {
                    $category->set('slug', Text::slug($category->name));
                    $category->clean();

                    return $category;
                });
            });

        return $query;
    }

    /**
     * Add a filter by category id.
     *
     * @param \Cake\ORM\Query $query The current query.
     * @param int $id ID of the category.
     * @return \Cake\ORM\Query
     */
    public function filterById(Query $query, int $id): Query
    {
        return $query->distinct()->innerJoinWith('Categories', function (Query $query) use ($id) {
            return $query
                ->where([$this->Categories->aliasField('id') => $id])
                ->where([$this->Categories->aliasField('enabled') => true]);
        });
    }
}

<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Controller\Component;

use BEdita\Core\Model\Entity\Category;
use Cake\Collection\CollectionInterface;
use Cake\Controller\Component;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\ModelAwareTrait;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Text;
use InvalidArgumentException;

/**
 * Categories component
 *
 * @property-read \BEdita\Core\Model\Table\CategoriesTable $Categories
 */
class CategoriesComponent extends Component
{
    use ModelAwareTrait;

    /**
     * @inheritDoc
     */
    protected $_defaultConfig = [];

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
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
    public function load(?string $type = null, ?int $parentId = null): Query
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
     * Load a category by its name and type
     *
     * @param string|null $name Category name.
     * @param string|null $type Category type.
     * @return \Cake\ORM\Query
     */
    public function loadByName(string $name, string $type): Query
    {
        $query = $this->Categories->find()
            ->where([$this->Categories->aliasField('enabled') => true])
            ->where([$this->Categories->aliasField('name') => $name]);

        $query = $query->find('type', [$type]);

        $query
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
     * @deprecated Use {@see \Chialab\FrontendKit\Controller\Component\CategoriesComponent::filterByCategories()} instead.
     */
    public function filterById(Query $query, int $id): Query
    {
        return $this->filterByCategories($query, [$id]);
    }

    /**
     * Build categories subquery for filtering.
     *
     * @param \Cake\ORM\Table $table Categories table instance.
     * @param array<string|int> $categories Categories ids or names.
     * @return \Cake\ORM\Query
     */
    protected function buildCategoriesSubquery(Table $table, array $categories): Query
    {
        return $table->find()
            ->where(fn (QueryExpression $exp): QueryExpression => $exp
                ->or_(function (QueryExpression $exp) use ($categories, $table): QueryExpression {
                    $ids = array_filter($categories, 'is_numeric');
                    if (!empty($ids)) {
                        $exp = $exp->in($table->aliasField('id'), $ids);
                    }

                    $names = array_diff($categories, $ids);
                    if (!empty($names)) {
                        $exp = $exp->in($table->aliasField('name'), $names);
                    }

                    return $exp;
                }));
    }

    /**
     * Filter contents that are in one or more of the given categories.
     *
     * @param \Cake\ORM\Query $query The current query.
     * @param array<string|int> $categories Array of category ids or names.
     * @param 'in'|'exists' $strategy If 'in', use a `WHERE id IN (...)` condition to filter contents. If 'exists', use a `WHERE EXISTS(...)` condition.
     * @return \Cake\ORM\Query
     */
    public function filterByCategories(Query $query, array $categories, string $strategy = 'in'): Query
    {
        return $query->where(function (QueryExpression $exp, Query $query) use ($categories, $strategy): QueryExpression {
            /** @var \Cake\ORM\Table $table */
            $table = $query->getRepository();
            $catQuery = $this->buildCategoriesSubquery($table->getAssociation('Categories')->getTarget(), $categories);

            switch ($strategy) {
                case 'in':
                    return $exp->in(
                        $table->aliasField('id'),
                        $catQuery->innerJoinWith('ObjectCategories')->select(['ObjectCategories.object_id'])
                    );
                case 'exists':
                    return $exp->exists(
                        $catQuery
                            ->select(['existing' => 1])
                            ->innerJoinWith('ObjectCategories')
                            ->where(fn (QueryExpression $exp): QueryExpression => $exp->equalFields('ObjectCategories.object_id', $table->aliasField('id'))),
                    );
                default:
                    throw new InvalidArgumentException(sprintf('Unknown strategy "%s", valid strategies are: in, exists', $strategy));
            }
        });
    }

    /**
     * Filter contents that are not in any of the given categories.
     *
     * @param \Cake\ORM\Query $query The current query.
     * @param array<string|int> $categories Array of category ids or names.
     * @param 'in'|'exists' $strategy If 'in', use a `WHERE id NOT IN (...)` condition to filter contents. If 'exists', use a `WHERE NOT EXISTS(...)` condition.
     * @return \Cake\ORM\Query
     */
    public function filterExcludeByCategories(Query $query, array $categories, string $strategy = 'in'): Query
    {
        return $query->where(function (QueryExpression $exp, Query $query) use ($categories, $strategy): QueryExpression {
            /** @var \Cake\ORM\Table $table */
            $table = $query->getRepository();
            $catQuery = $this->buildCategoriesSubquery($table->getAssociation('Categories')->getTarget(), $categories);

            switch ($strategy) {
                case 'in':
                    return $exp->notIn(
                        $table->aliasField('id'),
                        $catQuery->innerJoinWith('ObjectCategories')->select(['ObjectCategories.object_id'])
                    );
                case 'exists':
                    return $exp->notExists(
                        $catQuery
                            ->select(['existing' => 1])
                            ->innerJoinWith('ObjectCategories')
                            ->where(fn (QueryExpression $exp): QueryExpression => $exp->equalFields('ObjectCategories.object_id', $table->aliasField('id'))),
                    );
                default:
                    throw new InvalidArgumentException(sprintf('Unknown strategy "%s", valid strategies are: in, exists', $strategy));
            }
        });
    }
}

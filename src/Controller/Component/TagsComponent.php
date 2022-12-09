<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Controller\Component;

use BEdita\Core\Model\Entity\Tag;
use Cake\Collection\CollectionInterface;
use Cake\Controller\Component;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\ModelAwareTrait;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Utility\Text;
use InvalidArgumentException;

/**
 * Tags component
 *
 * @property-read \BEdita\Core\Model\Table\TagsTable $Tags
 */
class TagsComponent extends Component
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

        $this->loadModel('Tags');
    }

    /**
     * Load Tags.
     *
     * @return \Cake\ORM\Query
     */
    public function load(): Query
    {
        return $this->Tags->find()
            ->where([$this->Tags->aliasField('enabled') => true])
            ->order([$this->Tags->aliasField('name')])
            ->formatResults(function (CollectionInterface $results): CollectionInterface {
                return $results->map(function (Tag $tag): Tag {
                    $tag->set('slug', Text::slug($tag->name));
                    $tag->clean();

                    return $tag;
                });
            });
    }

    /**
     * Build tags subquery for filtering.
     *
     * @param \Cake\ORM\Table $table Tags table instance.
     * @param array<string|int> $tags Tags ids or names.
     * @return \Cake\ORM\Query
     */
    protected function buildTagsSubquery(Table $table, array $tags): Query
    {
        return $table->find()
            ->where(fn (QueryExpression $exp): QueryExpression => $exp
                ->or_(function (QueryExpression $exp) use ($tags, $table): QueryExpression {
                    $ids = array_filter($tags, 'is_numeric');
                    if (!empty($ids)) {
                        $exp = $exp->in($table->aliasField('id'), $ids);
                    }

                    $names = array_diff($tags, $ids);
                    if (!empty($names)) {
                        $exp = $exp->in($table->aliasField('name'), $names);
                    }

                    return $exp;
                }));
    }

    /**
     * Filter contents that has at least one of the given tags.
     *
     * @param \Cake\ORM\Query $query The current query.
     * @param array<string|int> $tags Array of tag ids or names.
     * @param 'in'|'exists' $strategy If 'in', use a `WHERE id IN (...)` condition to filter contents. If 'exists', use a `WHERE EXISTS(...)` condition.
     * @return \Cake\ORM\Query
     */
    public function filterByTags(Query $query, array $tags, string $strategy = 'in'): Query
    {
        return $query->where(function (QueryExpression $exp, Query $query) use ($tags, $strategy): QueryExpression {
            /** @var \Cake\ORM\Table $table */
            $table = $query->getRepository();
            $tagQuery = $this->buildTagsSubquery($table->getAssociation('Tags')->getTarget(), $tags);

            switch ($strategy) {
                case 'in':
                    return $exp->in(
                        $table->aliasField('id'),
                        $tagQuery->innerJoinWith('ObjectTags')->select(['ObjectTags.object_id'])
                    );
                case 'exists':
                    return $exp->exists(
                        $tagQuery
                            ->select(['existing' => 1])
                            ->innerJoinWith('ObjectTags')
                            ->where(fn (QueryExpression $exp): QueryExpression => $exp->equalFields('ObjectTags.object_id', $table->aliasField('id'))),
                    );
                default:
                    throw new InvalidArgumentException(sprintf('Unknown strategy "%s", valid strategies are: in, exists', $strategy));
            }
        });
    }

    /**
     * Filter contents that does not have any of the given tags
     *
     * @param \Cake\ORM\Query $query The current query.
     * @param array<string|int> $tags Array of tags ids or names.
     * @param 'in'|'exists' $strategy If 'in', use a `WHERE id NOT IN (...)` condition to filter contents. If 'exists', use a `WHERE NOT EXISTS(...)` condition.
     * @return \Cake\ORM\Query
     */
    public function filterExcludeByTags(Query $query, array $tags, string $strategy = 'in'): Query
    {
        return $query->where(function (QueryExpression $exp, Query $query) use ($tags, $strategy): QueryExpression {
            /** @var \Cake\ORM\Table $table */
            $table = $query->getRepository();
            $tagQuery = $this->buildTagsSubquery($table->getAssociation('Tags')->getTarget(), $tags);

            switch ($strategy) {
                case 'in':
                    return $exp->notIn(
                        $table->aliasField('id'),
                        $tagQuery->innerJoinWith('ObjectTags')->select(['ObjectTags.object_id'])
                    );
                case 'exists':
                    return $exp->notExists(
                        $tagQuery
                            ->select(['existing' => 1])
                            ->innerJoinWith('ObjectTags')
                            ->where(fn (QueryExpression $exp): QueryExpression => $exp->equalFields('ObjectTags.object_id', $table->aliasField('id'))),
                    );
                default:
                    throw new InvalidArgumentException(sprintf('Unknown strategy "%s", valid strategies are: in, exists', $strategy));
            }
        });
    }
}

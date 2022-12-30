<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\View\Helper;

use BEdita\Core\Model\Table\ObjectTypesTable;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Hash;
use Cake\View\Helper;
use InvalidArgumentException;
use Iterator;

/**
 * Placeholders helper.
 */
class PlaceholdersHelper extends Helper
{
    use LocatorAwareTrait;

    /**
     * ObjectTypes table.
     *
     * @var \BEdita\Core\Model\Table\ObjectTypesTable
     */
    public ObjectTypesTable $ObjectTypes;

    /**
     * @inheritDoc
     */
    protected $_defaultConfig = [
        'relation' => 'placeholder',
        'extract' => null,
        'template' => null,
    ];

    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->ObjectTypes = $this->fetchTable('ObjectTypes');
    }

    /**
     * Get a list of template paths to check.
     *
     * @param \Cake\Datasource\EntityInterface $entity Parent entity.
     * @param string $field Field.
     * @param \Cake\Datasource\EntityInterface $placeholder Entity referenced in the placeholder.
     * @param mixed $params Placeholder custom params.
     * @return array<string>
     */
    public function getTemplatePaths(EntityInterface $entity, string $field, EntityInterface $placeholder, mixed $params = null): array
    {
        $type = $placeholder->get('type') ?: 'objects';
        $objectType = $this->ObjectTypes->get($type);

        $paths = [];
        foreach ($objectType->getFullInheritanceChain() as $type) {
            $paths[] = sprintf('Placeholders/%s', $type->name);
        }

        return $paths;
    }

    /**
     * Get template for placeholder rendering.
     *
     * @param \Cake\Datasource\EntityInterface $entity Parent entity.
     * @param string $field Field.
     * @param \Cake\Datasource\EntityInterface $placeholder Entity referenced in the placeholder.
     * @param mixed $params Placeholder custom params.
     * @return string|null
     */
    public function getTemplate(EntityInterface $entity, string $field, EntityInterface $placeholder, mixed $params = null): string|null
    {
        foreach ($this->getTemplatePaths($entity, $field, $placeholder, $params) as $element) {
            if ($this->getView()->elementExists($element)) {
                return $element;
            }
        }

        return null;
    }

    /**
     * Extract placeholders in a field, and invoke `$callback` for each instance, and return templated contents.
     *
     * @param \Cake\Datasource\EntityInterface $entity Parent entity.
     * @param string $field String name.
     * @param array<\Cake\Datasource\EntityInterface> $placeholders Entities referenced as placeholders.
     * @param callable $callback Callback to be invoked for each instance of placeholder. This is supposed to return the templated placeholder.
     * @return mixed Templated field contents.
     */
    public static function defaultTemplater(EntityInterface $entity, string $field, array $placeholders, callable $callback): mixed
    {
        $contents = $entity->get($field);
        if (!is_string($contents) || empty($contents) || empty($placeholders)) {
            return $contents;
        }

        $deltas = [];
        foreach ($placeholders as $placeholder) {
            $info = Hash::get($placeholder, ['relation', 'params', $field], []);
            foreach ($info as $i) {
                $offset = $i['offset'];
                $delta = array_sum(array_filter(
                    $deltas,
                    function (int $pos) use ($offset): bool {
                        return $pos < $offset;
                    },
                    ARRAY_FILTER_USE_KEY
                ));
                $length = $i['length'];
                $params = $i['params'] ?? null;

                $replacement = $callback($placeholder, $params);

                $contents = mb_substr($contents, 0, $offset + $delta) . $replacement . mb_substr($contents, $offset + $delta + $length);

                $deltas[$offset] = mb_strlen($replacement) - $length;
            }
        }

        return $contents;
    }

    /**
     * Template an entity's field with placeholders.
     *
     * @param \Cake\Datasource\EntityInterface $entity Entity.
     * @param string $field Field to be templated.
     * @return mixed
     */
    public function template(EntityInterface $entity, string $field): mixed
    {
        $relation = $this->getConfigOrFail('relation');
        $placeholder = $entity->get($relation);
        if ($placeholder instanceof Iterator) {
            $placeholder = iterator_to_array($placeholder);
        } elseif (!is_array($placeholder) && $placeholder !== null) {
            throw new InvalidArgumentException(
                sprintf('Expected property "%s" to be an iterable, got "%s"', $relation, is_object($placeholder) ? get_class($placeholder) : gettype($placeholder))
            );
        }

        $extract = $this->getConfig('extract', [static::class, 'defaultTemplater']);
        $template = $this->getConfig('template', [$this, 'getTemplate']);

        return $extract($entity, $field, (array)$placeholder, function (EntityInterface $placeholder, $params = null) use ($entity, $field, $template): string {
            $element = $template($entity, $field, $placeholder, $params);

            return $this->getView()->element($element, [
                'object' => $placeholder,
                'params' => $params,
                'parent' => $entity,
                'field' => $field,
            ]);
        });
    }
}

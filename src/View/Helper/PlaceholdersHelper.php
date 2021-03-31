<?php
namespace Chialab\FrontendKit\View\Helper;

use Cake\Datasource\EntityInterface;
use Cake\Datasource\ModelAwareTrait;
use Cake\Utility\Hash;
use Cake\View\Helper;
use Cake\View\View;
use \Iterator;

/**
 * Placeholders helper.
 *
 * @property \BEdita\Core\Model\Table\ObjectTypesTable $ObjectTypes
 */
class PlaceholdersHelper extends Helper
{
    use ModelAwareTrait;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'relation' => 'placeholder',
        'extract' => null,
        'template' => null,
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->loadModel('ObjectTypes');
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
    public function getTemplate(EntityInterface $entity, string $field, EntityInterface $placeholder, $params = null): ?string
    {
        $type = $placeholder->get('type') ?: 'objects';
        $objectType = $this->ObjectTypes->get($type);
        foreach ($objectType->getFullInheritanceChain() as $type) {
            $element = sprintf('Placeholders/%s', $type->name);
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
     * @param \Cake\Datasource\EntityInterface[] $placeholders Entities referenced as placeholders.
     * @param callable $callback Callback to be invoked for each instance of placeholder. This is supposed to return the templated placeholder.
     * @return mixed Templated field contents.
     */
    public static function defaultTemplater(EntityInterface $entity, string $field, array $placeholders, callable $callback)
    {
        $contents = $entity->get($field);
        if (!is_string($contents) || empty($contents) || empty($placeholders)) {
            return $contents;
        }

        foreach ($placeholders as $placeholder) {
            $info = Hash::get($placeholder, ['relation', 'params', $field], []);
            foreach ($info as $i) {
                $offset = $i['offset'];
                $length = $i['length'];
                $params = $i['params'] ?? null;

                $replacement = $callback($placeholder, $params);

                $contents = mb_substr($contents, 0, $offset) . $replacement . mb_substr($contents, $offset + $length);
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
    public function template(EntityInterface $entity, string $field)
    {
        $relation = $this->getConfigOrFail('relation');
        $placeholder = $entity->get($relation);
        if ($placeholder instanceof Iterator) {
            $placeholder = iterator_to_array($placeholder);
        } elseif (!is_array($placeholder) && $placeholder !== null) {
            throw new \InvalidArgumentException(
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

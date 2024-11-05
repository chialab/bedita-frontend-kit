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
use RuntimeException;

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
     * The default regex to use to interpolate placeholders data.
     *
     * @see https://github.com/bedita/placeholders/blob/main/src/Model/Behavior/PlaceholdersBehavior.php
     * @var string
     */
    protected const REGEX = '/<!--\s*BE-PLACEHOLDER\.(?P<id>\d+)(?:\.(?P<params>[A-Za-z0-9+=-]+))?\s*-->/';

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

        if (preg_match_all(static::REGEX, $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === false) {
            throw new RuntimeException('Failed to extract placeholders');
        }

        $placeholdersMap = Hash::combine($placeholders, '{n}.id', '{n}');
        foreach ($matches as $match) {
            $placeholder = $placeholdersMap[(int)$match['id'][0]];
            if ($placeholder === null) {
                continue;
            }

            $params = !empty($match['params'][0]) ? base64_decode($match['params'][0]) : null;

            $replacement = $callback($placeholder, $params);

            $contents = preg_replace(static::REGEX, $replacement, $contents, 1);
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

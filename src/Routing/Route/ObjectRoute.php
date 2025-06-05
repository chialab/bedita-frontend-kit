<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Routing\Route;

use ArrayAccess;
use Cake\Routing\Route\DashedRoute;
use Cake\Utility\Hash;
use InvalidArgumentException;
use RuntimeException;

/**
 * Matches object entities to routes.
 *
 * This route will match by entity and map its fields to the URL pattern by
 * comparing the field names with the template vars. This makes it easy and
 * convenient to change routes globally.
 */
class ObjectRoute extends DashedRoute
{
    /**
     * Route filters.
     *
     * @var array
     */
    protected array $filters = [];

    /**
     * @inheritDoc
     */
    public function compile(): string
    {
        $compiled = parent::compile();

        if (isset($this->defaults['_filters'])) {
            $this->filters = $this->defaults['_filters'];
            unset($this->defaults['_filters']);
        }

        return $compiled;
    }

    /**
     * Match by entity and map its fields to the URL pattern by comparing the
     * field names with the template vars.
     *
     * If a routing key is defined in both `$url` and the entity, the value defined
     * in `$url` will be preferred.
     *
     * @param array $url Array of parameters to convert to a string.
     * @param array $context An array of the current request context.
     *   Contains information such as the current host, scheme, port, and base
     *   directory.
     * @return string|null Either false or a string URL.
     */
    public function match(array $url, array $context = []): string|null
    {
        if (empty($this->_compiledRoute)) {
            $this->compile();
        }

        $entity = Hash::get($url, '_entity');
        unset($url['_entity'], $url['_filters']);

        if (!isset($entity)) {
            return parent::match($url, $context);
        }
        if (isset($url['locale']) && !in_array('locale', $this->keys)) {
            return null;
        }
        $this->checkEntity($entity);
        if ($this->checkFilters($entity) === false) {
            return null;
        }

        foreach ($this->keys as $field) {
            if (!isset($url[$field]) && isset($entity[$field])) {
                $url[$field] = $entity[$field];
            }
        }

        return parent::match($url, $context);
    }

    /**
     * Checks that we really deal with an entity object
     *
     * @param \ArrayAccess|array $entity Entity value from the URL options
     * @return void
     * @throws \RuntimeException
     */
    protected function checkEntity(ArrayAccess|array $entity): void
    {
        if (!$entity instanceof ArrayAccess && !is_array($entity)) {
            throw new RuntimeException(sprintf(
                'Route `%s` expects the URL option `_entity` to be an array or object implementing \ArrayAccess, but `%s` passed.',
                $this->template,
                getTypeName($entity),
            ));
        }
    }

    /**
     * Check if Object matches route filters.
     *
     * @param \ArrayAccess|array $entity Entity value from the URL options.
     * @return bool
     */
    protected function checkFilters(ArrayAccess|array $entity): bool
    {
        if (empty($this->filters)) {
            return true;
        }

        $matchProp = function ($entity, $prop, $values) {
            if (!$entity[$prop] || empty($entity[$prop])) {
                return false;
            }

            return array_reduce(
                (array)$values,
                function (bool $matches, string $value) use ($entity, $prop): bool {
                    return $matches || fnmatch($value, $entity[$prop]);
                },
                false,
            );
        };

        foreach ($this->filters as $filter => $values) {
            if (str_starts_with($filter, 'property:')) {
                if (!$matchProp($entity, substr($filter, 9), $values)) {
                    return false;
                }

                continue;
            }

            switch ($filter) {
                case 'type':
                    if ($values !== '*' && (empty($entity['type']) || !in_array($entity['type'], (array)$values))) {
                        return false;
                    }
                    break;

                case 'parent':
                    if (empty($entity['parents'])) {
                        return false;
                    }
                    $parents = Hash::extract($entity['parents'], '{*}.uname');
                    if (count(array_intersect($parents, (array)$values)) === 0) {
                        return false;
                    }
                    break;

                case 'uname':
                    if (!$matchProp($entity, 'uname', $values)) {
                        return false;
                    }

                    break;
                default:
                    throw new InvalidArgumentException(sprintf(
                        'Unknown route filter "%s"',
                        $filter,
                    ));
            }
        }

        return true;
    }
}

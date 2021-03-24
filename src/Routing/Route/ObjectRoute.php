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
     * @return bool|string Either false or a string URL.
     */
    public function match(array $url, array $context = [])
    {
        if (empty($this->_compiledRoute)) {
            $this->compile();
        }
        if (!isset($url['_entity'])) {
            return parent::match($url, $context);
        }

        $entity = $url['_entity'];
        $this->checkEntity($entity);
        if (!empty($url['_filters']) && $this->checkFilters($entity, $url['_filters']) === false) {
            return false;
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
    protected function checkEntity($entity)
    {
        if (!$entity instanceof ArrayAccess && !is_array($entity)) {
            throw new RuntimeException(sprintf(
                'Route `%s` expects the URL option `_entity` to be an array or object implementing \ArrayAccess, but `%s` passed.',
                $this->template,
                getTypeName($entity)
            ));
        }
    }

    /**
     * Check if Object matches route filters.
     *
     * @param \ArrayAccess|array $entity Entity value from the URL options.
     * @param array $filters Filters from the URL options.
     * @return bool
     */
    protected function checkFilters($entity, $filters = []): bool
    {
        foreach ($filters as $filter => $values) {
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

                default:
                    throw new InvalidArgumentException(sprintf(
                        'Unknown route filter "%s"',
                        $filter
                    ));
            }
        }

        return true;
    }
}

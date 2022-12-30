<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Twig;

use Cake\Utility\Hash;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Sort by extension for Twig.
 */
class SortByExtension extends AbstractExtension
{
    /**
     * @inheritDoc
     */
    public function getFilters()
    {
        return [
            new TwigFilter('sort_by', [$this, 'sortBy']),
            new TwigFilter('sort_by_multi', [$this, 'sortByMulti']),
        ];
    }

    /**
     * Sort items by field.
     *
     * @param iterable $items Items iterator to sort.
     * @param string $field Field to sort by.
     * @param bool $desc Sort in descending order.
     * @param bool $numeric Sort numerically.
     * @return iterable
     */
    public function sortBy(iterable $items, string $field, bool $desc = false, bool $numeric = false): iterable
    {
        return collection($items)
            ->sortBy($field, $desc ? SORT_DESC : SORT_ASC, $numeric ? SORT_NATURAL : SORT_LOCALE_STRING);
    }

    /**
     * Sort items by multiple fields.
     *
     * @param iterable $items Items iterator to sort.
     * @param array $fields Fields to sort by.
     * @param bool $desc Sort in descending order.
     * @return iterable
     */
    public function sortByMulti(iterable $items, array $fields, bool $desc = false): iterable
    {
        if (!is_array($items)) {
            $items = iterator_to_array($items);
        }

        usort($items, function ($a, $b) use ($fields) {
            $aVals = array_filter(array_map(fn ($attr) => Hash::get($a, $attr), $fields));
            $bVals = array_filter(array_map(fn ($attr) => Hash::get($b, $attr), $fields));

            while (true) {
                $aVal = array_shift($aVals);
                $bVal = array_shift($bVals);

                if ($aVal === null && $bVal === null) {
                    return 0;
                }

                if ($aVal === null) {
                    return -1;
                }

                if ($bVal === null) {
                    return 1;
                }

                $comparison = $aVal <=> $bVal;
                if (is_string($aVal) && is_string($bVal)) {
                    $comparison = strcasecmp($aVal, $bVal);
                }

                if ($comparison !== 0) {
                    return $comparison;
                }
            }
        });

        if ($desc) {
            $items = array_reverse($items);
        }

        return collection($items);
    }
}

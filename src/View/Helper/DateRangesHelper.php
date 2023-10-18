<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\View\Helper;

use BEdita\Core\Model\Entity\DateRange;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\View\Helper;
use DateTimeInterface;
use Generator;
use const SORT_ASC;

/**
 * Date helper
 */
class DateRangesHelper extends Helper
{
    /**
     * @inheritDoc
     */
    protected $_defaultConfig = [
        'single' => '{0,date,long}, {0,time,short}',
        'wholeDay' => '{0,date,long}',
        'sameDay' => '{0,date,long}, dalle {0,time,short} alle {1,time,short}',
        'sameMonth' => 'dal {0,date,d} al {1,date,long}',
        'sameYear' => 'dal {0,date,d MMM} al {1,date,d MMM y}',
        'other' => 'dal {0,date,d MMM y} al {1,date,d MMM y}',
    ];

    /**
     * Normalize date object.
     *
     * @param \DateTimeInterface $dt Date time object.
     * @return \Cake\I18n\FrozenTime
     */
    protected static function normalize(DateTimeInterface $dt): FrozenTime
    {
        return new FrozenTime($dt);
    }

    /**
     * Format a range of dates.
     *
     * @param \BEdita\Core\Model\Entity\DateRange $range Date range.
     * @param array $options Format options.
     * @return string
     */
    public function formatRange(DateRange $range, array $options = []): string
    {
        if ($range->start_date === null) {
            Log::warning(sprintf('start_date for object with id %d is null', $range->object_id));

            return '';
        }

        $start = static::normalize($range->start_date);
        if ($range->end_date === null) {
            return __x('dateRange', $options['single'] ?? $this->getConfigOrFail('single'), $start);
        }

        $end = static::normalize($range->end_date);
        if ($end->lessThan($start)) {
            Log::warning(sprintf('Date range is in reverse order: %s - %s', $start->toIso8601String(), $end->toIso8601String()));

            [$start, $end] = [$end, $start]; // Swap.
        }
        foreach ($this->getFormats($start, $end) as $format) {
            $fmt = $options[$format] ?? $this->getConfig($format);
            if ($fmt !== null) {
                return __x('dateRange', $fmt, $start, $end);
            }
        }

        return __x('dateRange', $options['other'] ?? $this->getConfigOrFail('other'), $start, $end);
    }

    /**
     * Iterate through applicable formats for the given range.
     *
     * @param \Cake\I18n\FrozenTime $start Beginning of range.
     * @param \Cake\I18n\FrozenTime $end End of range.
     * @return \Generator|array<string>
     */
    protected function getFormats(FrozenTime $start, FrozenTime $end): Generator
    {
        if ($end->isSameDay($start)) {
            if ($start->second(0)->equals($start->startOfDay()) && $end->second(59)->equals($end->endOfDay())) {
                yield 'wholeDay';
            }
            yield 'sameDay';
        }
        if ($end->year === $start->year && $end->month === $start->month) {
            if ($start->startOfDay()->equals($start->startOfMonth()) && $end->endOfDay()->equals($end->endOfMonth())) {
                yield 'wholeMonth';
            }
            yield 'sameMonth';
        }
        if ($end->year === $start->year) {
            if ($start->startOfDay()->equals($start->startOfYear()) && $end->endOfDay()->equals($end->endOfYear())) {
                yield 'wholeYear';
            }
            yield 'sameYear';
        }
    }

    /**
     * Find closest range in a list of date ranges.
     *
     * @param array<\BEdita\Core\Model\Entity\DateRange> $ranges Date ranges.
     * @param string|null $now Relative to date.
     * @return \BEdita\Core\Model\Entity\DateRange|null
     */
    public function getClosestRange(array $ranges, string|null $now = null): DateRange|null
    {
        $now = $now !== null ? new FrozenTime($now) : FrozenTime::now();
        $sorted = collection($ranges)
            ->filter(fn (DateRange $dr): bool => $dr->start_date !== null)
            ->sortBy(fn (DateRange $dr): DateTimeInterface => $dr->start_date, SORT_ASC);
        $currentOrFuture = $sorted
            ->filter(fn (DateRange $dr): bool => (new FrozenTime($dr->start_date))->gte($now) || ($dr->end_date !== null && (new FrozenTime($dr->end_date))->gte($now)))
            ->first();
        if ($currentOrFuture !== null) {
            // Found an event overlapping `$now` point in time, or the closest one in the future.
            return $currentOrFuture;
        }

        return $sorted->last(); // Get closest event in the past, relative to `$now`.
    }

    /**
     * Sort a list of objects by their closest date.
     *
     * @param array<\BEdita\Core\Model\Entity\ObjectEntity> $objects Objects to sort.
     * @param int $dir either SORT_DESC or SORT_ASC.
     * @return \Cake\Collection\Collection Sorted objects.
     */
    public function sortByClosestRange(iterable $objects, int $dir = SORT_ASC): iterable
    {
        return collection($objects)
            ->sortBy(function (ObjectEntity $obj): int|null {
                $range = $this->getClosestRange($obj->date_ranges ?? []);
                if ($range === null) {
                    return PHP_INT_MIN;
                }

                return $range->start_date->getTimestamp();
            }, $dir);
    }
}

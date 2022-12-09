<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Controller\Component;

use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Controller\Component;
use Cake\Database\Expression\FunctionExpression;
use Cake\Database\Expression\QueryExpression;
use Cake\I18n\FrozenDate;
use Cake\I18n\FrozenTime;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Generator;
use InvalidArgumentException;

/**
 * Calendar component
 */
class CalendarComponent extends Component
{
    /**
     * @inheritDoc
     */
    public $components = ['Chialab/FrontendKit.Objects'];

    /**
     * An array of i18n months names, useful for building a select input.
     *
     * @return array
     */
    public function monthsLabels(): array
    {
        $months = range(1, 12);

        return array_combine($months, array_map(
            fn ($monthNum): string => FrozenDate::now()->month($monthNum)->i18nFormat('MMMM'),
            $months
        ));
    }

    /**
     * Get sub-query for joining with date boundaries.
     *
     * @param \Cake\ORM\Table $dateRanges Date ranges table instance.
     * @param \Cake\I18n\FrozenTime $from From.
     * @param \Cake\I18n\FrozenTime $to To.
     * @return \Cake\ORM\Query
     */
    protected function getDateBoundariesSubQuery(Table $dateRanges, FrozenTime $from, FrozenTime $to): Query
    {
        $query = $dateRanges->find();

        return $query
            ->find('dateRanges', [
                'from_date' => $from->toIso8601String(),
                'to_date' => $to->toIso8601String(),
            ])
            ->select([
                'object_id' => $dateRanges->aliasField('object_id'),
                'closest_start_date' => $query->func()->min('start_date'),
                'closest_end_date' => $query->func()->min('end_date'),
            ])
            ->group($dateRanges->aliasField('object_id'));
    }

    /**
     * Add filtering and sorting to a query.
     *
     * Objects are filtered by the requested range (from/to). Objects are sorted so that:
     *
     * 1. objects that are in progress at the range start appear first. Within this class, event ending sooner appear first.
     * 2. objects starting after the range start are sorted with objects starting sooner appearing first.
     * 3. when two future objects start at the same time, the one that ends sooner appear first.
     *
     * @param \Cake\ORM\Query $query Query object.
     * @param \Cake\I18n\FrozenTime $from Range start.
     * @param \Cake\I18n\FrozenTime $to Range end.
     * @return \Cake\ORM\Query
     * @throws \InvalidArgumentException Throws an exception when the table being queried is not linked with DateRanges.
     */
    public function findInRange(Query $query, FrozenTime $from, FrozenTime $to): Query
    {
        /** @var \Cake\ORM\Table */
        $table = $query->getRepository();
        if (!$table->hasAssociation('DateRanges')) {
            throw new InvalidArgumentException('Table must be associated with DateRanges');
        }

        $dateRanges = $table->getAssociation('DateRanges')->getTarget();

        return $query
            // Add join with DateRanges table.
            ->innerJoin(
                ['DateBoundaries' => $this->getDateBoundariesSubQuery($dateRanges, $from, $to)],
                fn (QueryExpression $exp): QueryExpression => $exp->equalFields(
                    'DateBoundaries.object_id',
                    $table->aliasField('id')
                )
            )
            // Sort by closest events.
            ->orderAsc(new FunctionExpression('GREATEST', ['DateBoundaries.closest_start_date' => 'identifier', $from->toIso8601String()]), true)
            ->orderDesc(fn (QueryExpression $exp): QueryExpression => $exp->isNull('DateBoundaries.closest_end_date'))
            ->orderAsc('DateBoundaries.closest_end_date');
    }

    /**
     * Group objects by the day in which they occurr.
     *
     * @param \Cake\ORM\Query $query The query object.
     * @param \Cake\I18n\FrozenTime $from Range start.
     * @param \Cake\I18n\FrozenTime|null $to Range end.
     * @return \Cake\ORM\Query
     */
    public function findGroupedByDay(Query $query, FrozenTime $from, ?FrozenTime $to = null): Query
    {
        $to = $to ?? $from->addWeek();

        return $this->findInRange($query, $from, $to)
            ->contain(['DateRanges'])
            ->formatResults(function (iterable $results) use ($from, $to): iterable {
                $grouped = collection($results)->unfold(function (ObjectEntity $event) use ($from, $to): Generator {
                    foreach ($event->date_ranges as $dr) {
                        $start = new FrozenDate($dr->start_date);
                        $end = new FrozenDate($dr->end_date ?: $dr->start_date);
                        if ($start->gte($to) || $end->lt($from)) {
                            continue;
                        }

                        $start = $start->max($from);
                        while ($start->lte($end) && $start->lte($to)) {
                            $day = $start->format('Y-m-d');
                            $start = $start->addDay();

                            yield compact('event', 'day');
                        }
                    }
                })
                ->groupBy('day')
                ->map(fn (array $items): array => array_column($items, 'event'))
                ->toArray();

                ksort($grouped, SORT_STRING);

                return collection($grouped);
            });
    }

    /**
     * Load calendar items from a folder.
     *
     * @param string $parent The parent folder uname.
     * @param \Cake\I18n\FrozenTime $from Range start.
     * @param \Cake\I18n\FrozenTime|null $to Range end.
     * @return \Cake\ORM\Query
     */
    public function calendarFolder(string $parent, FrozenTime $from, ?FrozenTime $to): Query
    {
        return $this->findGroupedByDay(
            $this->Objects->loadObjects(['parent' => $parent], 'objects'),
            $from,
            $to,
        );
    }

    /**
     * Create `from` and `to` values for "today".
     *
     * @param bool $fullDay Return the full day or just the remaining time.
     * @return array<\Cake\I18n\FrozenTime>
     */
    public function today(bool $fullDay = true): array
    {
        $now = FrozenTime::now();

        return [$fullDay ? $now->startOfDay() : $now, $now->endOfDay()];
    }

    /**
     * Create `from` and `to` values for "tomorrow".
     *
     * @return array<\Cake\I18n\FrozenTime>
     */
    public function tomorrow(): array
    {
        $tomorrow = FrozenTime::tomorrow();

        return [$tomorrow->startOfDay(), $tomorrow->endOfDay()];
    }

    /**
     * Create `from` and `to` values for the current week.
     *
     * @param bool $fullWeek Return the full week range or just the remaining time.
     * @return array<\Cake\I18n\FrozenTime>
     */
    public function thisWeek(bool $fullWeek = true): array
    {
        $now = FrozenTime::now();

        return [$fullWeek ? $now->startOfWeek() : $now, $now->endOfWeek()];
    }

    /**
     * Create `from` and `to` values for the current weekend.
     *
     * @param bool $fullWeekend Return the full weekend range or just the remaining time.
     * @return array<\Cake\I18n\FrozenTime>
     */
    public function thisWeekend(bool $fullWeekend = true): array
    {
        $now = FrozenTime::now();
        switch ($now->dayOfWeek) {
            case FrozenTime::SATURDAY:
                return [$fullWeekend ? $now->startOfDay() : $now, $now->addDay()->endOfDay()];
            case FrozenTime::SUNDAY:
                return [$fullWeekend ? $now->subDay()->startOfDay() : $now, $now->endOfDay()];
            default:
                return [$now->next(FrozenTime::SATURDAY)->startOfDay(), $now->next(FrozenTime::SUNDAY)->endOfDay()];
        }
    }

    /**
     * Create `from` and `to` values for the current month.
     *
     * @param bool $fullMonth Return the full month range or just the remaining time.
     * @return array<\Cake\I18n\FrozenTime>
     */
    public function thisMonth(bool $fullMonth = true): array
    {
        $now = FrozenTime::now();

        return [$fullMonth ? $now->startOfMonth() : $now, $now->endOfMonth()];
    }
}

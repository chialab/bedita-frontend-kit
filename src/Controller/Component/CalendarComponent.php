<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Controller\Component;

use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\Controller\Component;
use Cake\I18n\FrozenDate;
use Cake\ORM\Query;

/**
 * Calendar component
 */
class CalendarComponent extends Component
{
    /**
     * Filter events by date range and group each event by day.
     *
     * @param \Cake\Orm\Query $query The query object.
     * @param \DateTimeInterface|int|string $from The filter start date.
     * @param \DateTimeInterface|int|string|null $to The filter end date (default $from + 1 week).
     * @return \Cake\Orm\Query The augmented query object.
     */
    public function groupByDay(Query $query, $from, $to = null): Query
    {
        $from = new FrozenDate($from);
        $to = $to !== null ? new FrozenDate($to) : $from->addWeek();

        return $query
            ->find('dateRanges', [
                'from_date' => $from->toIso8601String(),
                'to_date' => $to->toIso8601String(),
            ])
            ->formatResults(fn (iterable $results): iterable =>
                collection($results)->unfold(function (ObjectEntity $event) use ($from, $to): \Generator {
                    foreach ($event->date_ranges as $dr) {
                        $start = new FrozenDate($dr->start_date);
                        $end = new FrozenDate($dr->end_date ?: $dr->start_date);
                        if ($start->gte($to) || $end->lt($from)) {
                            continue;
                        }

                        $start = $start->max($from);
                        while ($start->lte($end) && $start->lte($to)) {
                            $day = $start->toIso8601String();
                            $start = $start->addDay();

                            yield compact('event', 'day');
                        }
                    }
                })
                ->groupBy('day')
                ->map(fn (array $items): array => array_column($items, 'event')));
    }
}

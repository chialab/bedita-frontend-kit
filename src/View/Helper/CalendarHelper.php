<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\View\Helper;

use BEdita\Core\Model\Entity\DateRange;
use Cake\I18n\FrozenTime;

/**
 * Calendar helper
 */
class CalendarHelper extends DateRangesHelper
{
    /**
     * Get the date instance for given year, month and day.
     *
     * @param int $year Year
     * @param int $month Month
     * @param int $day Day
     * @return \Cake\I18n\FrozenTime
     */
    public function getDate(int $year, int $month, int $day)
    {
        return FrozenTime::create($year, $month, $day, 0, 0, 0);
    }

    /**
     * Get a range of years.
     *
     * @param string|int $start The start year.
     * @param int $sub Number of years to remove for the start.
     * @param int $add Number of years to add to the start.
     * @return array
     */
    public function getYears($start, $sub = 0, $add = 2): array
    {
        if (is_string($start)) {
            $date = new FrozenTime($start);
            $start = (int)$date->year;
        }

        return range($start - $sub, $start + $add);
    }

    /**
     * An array of i18n months names, useful for building a select input.
     *
     * @return array
     */
    public function getMonths(): array
    {
        $months = range(1, 12);

        return array_combine($months, array_map(
            fn ($monthNum): string => FrozenTime::now()->month($monthNum)->i18nFormat('MMMM'),
            $months
        ));
    }

    /**
     * Get a list of available days in a month for a given year.
     *
     * @param int $month Month
     * @param int $year Year
     * @return array
     */
    public function getDaysInMonth(int $month, int $year): array
    {
        $last = FrozenTime::create($year, $month, 1);

        return range(1, $last->lastOfMonth()->day);
    }
}

<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\View\Helper;

use Cake\I18n\FrozenTime;

/**
 * Calendar helper
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class CalendarHelper extends DateRangesHelper
{
    /**
     * @inheritdoc
     */
    public $helpers = ['Html', 'Url'];

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'dayParam' => 'day',
        'monthParam' => 'month',
        'yearParam' => 'year',
    ];

    /**
     * Get a range of years.
     * It can be used with absolute values, eg 2019 and 2022
     * or relative values to the $from value, eg "-2 years" and "+2 years"
     *
     * @param int|string $startRange The initial value of the range, it can be absolute or relative.
     * @param int|string $endRange The initial value of the range, it can be absolute or relative.
     * @param \Cake\I18n\FrozenTime|string $from The start date for relative values.
     * @return array
     */
    public function getYears($startRange, $endRange, $from = 'now'): array
    {
        if (is_int($startRange)) {
            return range($startRange, $endRange);
        }

        $from = new FrozenTime($from);
        $start = $from->modify($startRange)->year;
        $end = $from->modify($endRange)->year;

        return range($start, $end);
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
     * @param int $year Year
     * @param int $month Month
     * @return array
     */
    public function getDaysInMonth(int $year, int $month): array
    {
        $last = FrozenTime::create($year, $month, 1);

        return range(1, $last->lastOfMonth()->day);
    }

    /**
     * Get the request date, if available.
     *
     * @return \Cake\I18n\FrozenTime|null
     */
    public function getDate(?int $year = null, ?int $month = null, ?int $day = null): ?FrozenTime
    {
        $dayParam = $this->getConfig('dayParam');
        $monthParam = $this->getConfig('monthParam');
        $yearParam = $this->getConfig('yearParam');
        $request = $this->_View->getRequest();

        if ($year !== null && $month !== null) {
            return FrozenTime::create($year, $month, $day ?? 1);
        }

        if ($request->getQuery($monthParam) === null || $request->getQuery($yearParam) === null) {
            return null;
        }

        return FrozenTime::create($request->getQuery($yearParam), $request->getQuery($monthParam), $request->getQuery($dayParam) ?? 1, 0, 0, 0);
    }

    /**
     * Convert s string input to a date.
     * If the given input is a relative date, it will apply the modifer to the current request date.
     *
     * @param string $date The date to convert.
     * @param mixed $start The start date for relative dates.
     * @return \Cake\I18n\FrozenTime
     */
    protected function prepareLinkDate($date, $start = null)
    {
        if (is_string($date) && ($date[0] === '+' || $date[0] === '-')) {
            $start = ($start ? new FrozenTime($start) : null) ?? $this->getDate() ?? FrozenTime::now()->startOfDay();
            return $start->modify($date);
        }

        return new FrozenTime($date);
    }

    /**
     * Generate an url to a day in the calendar.
     * It accept absolute and relative dates eg "+1 month" "2022-04-25" "-7 days"
     *
     * @param string $title Link title.
     * @param mixed $date The absolute or relative date.
     * @param array $options Link options.
     * @param mixed $start The start date for relative urls.
     * @return string The url to the calendar date.
     */
    public function url($date, array $options = [], $start = null)
    {
        $dayParam = $this->getConfig('dayParam');
        $monthParam = $this->getConfig('monthParam');
        $yearParam = $this->getConfig('yearParam');
        $date = $this->prepareLinkDate($date, $start);
        $query = array_merge($options['?'] ?? [], [
            $dayParam => $date->day,
            $monthParam => $date->month,
            $yearParam => $date->year,
        ]);

        return $this->Url->build(['?' => $query] + $options);
    }

    /**
     * Generate a link to a day in the calendar.
     * It accept absolute and relative dates eg "+1 month" "2022-04-25" "-7 days"
     *
     * @param string $title Link title.
     * @param mixed $date The absolute or relative date.
     * @param array $options Link options.
     * @param mixed $start The start date for relative urls.
     * @return string The anchor element with a link to the calendar date.
     */
    public function link($title, $date, array $options = [], $start = null)
    {
        $dayParam = $this->getConfig('dayParam');
        $monthParam = $this->getConfig('monthParam');
        $yearParam = $this->getConfig('yearParam');
        $date = $this->prepareLinkDate($date, $start);
        $query = array_merge($options['?'] ?? [], [
            $dayParam => $date->day,
            $monthParam => $date->month,
            $yearParam => $date->year,
        ]);

        return $this->Html->link($title, ['?' => $query] + $options);
    }
}

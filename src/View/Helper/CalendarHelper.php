<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\View\Helper;

use BEdita\Core\Model\Entity\DateRange;
use Cake\I18n\FrozenTime;

/**
 * Calendar helper
 *
 * @property \Cake\View\Helper\FormHelper $Form
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\UrlHelper $Url
 */
class CalendarHelper extends DateRangesHelper
{
    /**
     * @inheritdoc
     */
    public $helpers = ['Form', 'Html', 'Url'];

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'searchParam' => 'q',
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

        $years = range($start, $end);

        return array_combine($years, $years);
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
        $days = range(1, $last->lastOfMonth()->day);

        return array_combine($days, $days);
    }

    /**
     * Get the request date, if available.
     *
     * @return \Cake\I18n\FrozenTime
     */
    public function getDate(): FrozenTime
    {
        $dayParam = $this->getConfig('dayParam');
        $monthParam = $this->getConfig('monthParam');
        $yearParam = $this->getConfig('yearParam');
        $request = $this->_View->getRequest();

        if ($request->getQuery($monthParam) === null || $request->getQuery($yearParam) === null) {
            return FrozenTime::now();
        }

        return FrozenTime::create($request->getQuery($yearParam), $request->getQuery($monthParam), $request->getQuery($dayParam) ?? 1, 0, 0, 0);
    }

    /**
     * Get the request search text, if available.
     *
     * @return string|null
     */
    public function getSearchText(): ?string
    {
        $request = $this->_View->getRequest();

        return $request->getQuery($this->getConfig('searchParam'));
    }

    /**
     * Convert s string input to a date.
     * If the given input is a relative date, it will apply the modifer to the current request date.
     *
     * @param string $date The date to convert.
     * @param mixed $start The start date for relative dates.
     * @return \Cake\I18n\FrozenTime
     */
    protected function prepareLinkDate($date, $start = null): FrozenTime
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
     * @param mixed $date The absolute or relative date.
     * @param array $options Link options.
     * @param mixed $start The start date for relative urls.
     * @return string The url to the calendar date.
     */
    public function url($date, array $options = [], $start = null): string
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
    public function link(string $title, $date, array $options = [], $start = null): string
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

    /**
     * A JavaScript to update days select on month/year changes.
     *
     * @return string JavaScript code.
     */
    protected function onChangeScript(): string
    {
        $dayParam = $this->getConfig('dayParam');
        $monthParam = $this->getConfig('monthParam');
        $yearParam = $this->getConfig('yearParam');

        $code = 'var form = event.target.closest(\'form\');';
        $code .= 'if (form) {';
        $code .= sprintf('var days = form.querySelector(%s);', json_encode(sprintf('[name=%s]', $dayParam)));
        $code .= 'if (days) {';
        $code .= 'var data = new FormData(form);';
        $code .= sprintf('var month = data.get(%s);', json_encode($monthParam));
        $code .= sprintf('var year = data.get(%s);', json_encode($yearParam));
        $code .= 'var date = new Date(year, month, 0);';
        $code .= 'days.innerHTML = \'\';';
        $code .= 'var num = date.getDate(); while (num--) {';
        $code .= 'var option = document.createElement(\'option\');';
        $code .= 'option.value = num + 1;';
        $code .= 'option.textContent = num + 1;';
        $code .= 'option.selected = num === 0;';
        $code .= 'days.insertBefore(option, days.firstChild);';
        $code .= '}';
        $code .= '}';
        $code .= '}';

        return $code;
    }

    /**
     * Genrate a <input> element for search.
     *
     * @param array|null $options Options for the input element.
     * @return string The <input> element.
     */
    public function searchControl($options = null): string
    {
        $text = $this->getSearchText();
        $options = $options ?? [];

        return $this->Form->text($this->getConfig('searchParam'), [
            'value' => $text,
        ] + $options);
    }

    /**
     * Genrate a <select> element for days.
     *
     * @param array|null $options Options for the select element.
     * @return string The <select> element.
     */
    public function daysControl($options = null): string
    {
        $date = $this->getDate();
        $options = $options ?? [];

        return $this->Form->control($this->getConfig('dayParam'), [
            'label' => '',
            'type' => 'select',
            'options' => $this->getDaysInMonth($date->year, $date->month),
            'value' => $date->day,
        ] + $options);
    }

    /**
     * Genrate a <select> element for months.
     *
     * @param array|null $options Options for the select element.
     * @return string The <select> element.
     */
    public function monthsControl($options = null): string
    {
        $date = $this->getDate();
        $options = $options ?? [];

        return $this->Form->control($this->getConfig('monthParam'), [
            'label' => '',
            'type' => 'select',
            'options' => $this->getMonths(),
            'onchange' => $this->onChangeScript(),
            'value' => $date->month,
        ] + $options);
    }

    /**
     * Genrate a <select> element for years.
     *
     * @param array|null $options Options for the select element.
     * @return string The <select> element.
     */
    public function yearsControl($options = null, $start = '-2 years', $end = '+2 years'): string
    {
        $date = $this->getDate();
        $options = $options ?? [];

        return $this->Form->control($this->getConfig('yearParam'), [
            'label' => '',
            'type' => 'select',
            'options' => $this->getYears($start, $end),
            'onchange' => $this->onChangeScript(),
            'value' => $date->year,
        ] + $options);
    }

    /**
     * Genrate a reset link for the calendar view.
     *
     * @param string The title of the link.
     * @param array|null $options Options for the link element.
     * @return string The <a> element.
     */
    public function resetControl(string $title, $options = null): string
    {
        $searchParam = $this->getConfig('searchParam');
        $dayParam = $this->getConfig('dayParam');
        $monthParam = $this->getConfig('monthParam');
        $yearParam = $this->getConfig('yearParam');

        $url = $this->_View->getRequest()->url;
        $query = $this->_View->getRequest()->getQueryParams();
        $query = array_diff_key($query, [
            $searchParam => null,
            $dayParam => null,
            $monthParam => null,
            $yearParam => null,
        ]);
        if (!empty($query)) {
            $url = sprintf('%s?%s', $url, http_build_query($query));
        }

        return $this->Html->link($title, $url, $options);
    }
}

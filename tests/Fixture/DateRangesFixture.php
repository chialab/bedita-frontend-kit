<?php
namespace Chialab\FrontendKit\Test\Fixture;

use BEdita\Core\Test\Fixture\DateRangesFixture as BEDateRangesFixture;

/**
 * DateRanges test fixture.
 */
class DateRangesFixture extends BEDateRangesFixture
{
    public $records = [
        [
            'object_id' => '13',
            'start_date' => '2022-02-15 12:00:00',
            'end_date' => null,
            'params' => null,
        ],
        [
            'object_id' => '14',
            'start_date' => '2022-02-14 12:00:00',
            'end_date' => '2022-02-17 12:00:00',
            'params' => null,
        ],
        [
            'object_id' => '15',
            'start_date' => '2022-02-01 12:00:00',
            'end_date' => '2022-02-03 12:00:00',
            'params' => null,
        ],
        [
            'object_id' => '15',
            'start_date' => '2022-02-16 12:00:00',
            'end_date' => '2022-02-18 12:00:00',
            'params' => null,
        ],
        [
            'object_id' => '15',
            'start_date' => '2022-02-21 12:00:00',
            'end_date' => '2022-02-23 12:00:00',
            'params' => null,
        ],
    ];
}

<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestCase\View\Helper;

use BEdita\Core\Model\Entity\DateRange;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\I18n\FrozenTime;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Chialab\FrontendKit\View\Helper\DateRangesHelper;
use DateTimeInterface;

/**
 * {@see \Chialab\FrontendKit\View\Helper\DateRangesHelper} Test Case
 *
 * @coversDefaultClass \Chialab\FrontendKit\View\Helper\DateRangesHelper
 */
class DateRangesHelperTest extends TestCase
{
    public $fixtures = [
        'plugin.Chialab/FrontendKit.ObjectTypes',
        'plugin.BEdita/Core.PropertyTypes',
        'plugin.Chialab/FrontendKit.Properties',
        'plugin.Chialab/FrontendKit.Relations',
        'plugin.Chialab/FrontendKit.RelationTypes',
        'plugin.Chialab/FrontendKit.Objects',
        'plugin.Chialab/FrontendKit.Users',
        'plugin.Chialab/FrontendKit.ObjectRelations',
        'plugin.BEdita/Core.Categories',
        'plugin.BEdita/Core.ObjectCategories',
        'plugin.BEdita/Core.Tags',
        'plugin.BEdita/Core.ObjectTags',
    ];

    /**
     * Test subject
     *
     * @var \Chialab\FrontendKit\View\Helper\DateRangesHelper
     */
    protected DateRangesHelper $DateRanges;

    /** @inheritDoc */
    public function setUp(): void
    {
        parent::setUp();

        $this->DateRanges = new DateRangesHelper(new View());
        FrozenTime::setTestNow(new FrozenTime('2020-01-01 00:00:00'));
        I18n::setLocale('it');
    }

    /** @inheritDoc */
    public function tearDown(): void
    {
        unset($this->DateRanges);
        FrozenTime::setTestNow(null);
        I18n::setLocale('en');

        parent::tearDown();
    }

    /**
     * Data provider for {@see DateRangesHelperTest::testFormatRange()} test case.
     *
     * @return array[]
     */
    public function formatRangeProvider(): array
    {
        return [
            'single' => [
                '9 settembre 2021, 11:41',
                new FrozenTime('2021-09-09T11:41:10'),
                null,
            ],
            'whole day' => [
                '9 settembre 2021',
                 new FrozenTime('2021-09-09T00:00:00'),
                 new FrozenTime('2021-09-09T23:59:59'),
            ],
            'whole day, with approximation to the whole minute ' => [
                '9 settembre 2021',
                new FrozenTime('2021-09-09T00:00:01'),
                new FrozenTime('2021-09-09T23:59:00'),
            ],
            'time range within same day' => [
                '9 settembre 2021, dalle 18:15 alle 19:00',
                new FrozenTime('2021-09-09T18:15:00'),
                new FrozenTime('2021-09-09T19:00:00'),
            ],
            'same month' => [
                'dal 22 al 25 settembre 2021',
                new FrozenTime('2021-09-22T18:15:00'),
                new FrozenTime('2021-09-25T19:00:00'),
            ],
            'same year' => [
                'dal 1 gen al 17 ago 2021',
                new FrozenTime('2021-01-01T18:15:00'),
                new FrozenTime('2021-08-17T19:00:00'),
            ],

            'different year' => [
                'dal 1 ott 2019 al 16 ott 2020', // musixmatch
                new FrozenTime('2019-10-01T18:15:00'),
                new FrozenTime('2020-10-16T19:00:00'),
            ],

            'end date is in the first week of the next year (same year)' => [
                'dal 1 gen al 31 dic 2024',
                new FrozenTime('2024-01-01T00:00:00'),
                new FrozenTime('2024-12-31T00:00:00'),
            ],
            'end date is in the first week of the next year (different year)' => [
                'dal 18 ott 2023 al 31 dic 2024',
                new FrozenTime('2023-10-18T14:59:34'),
                new FrozenTime('2024-12-31T00:00:00'),
            ],
        ];
    }

    /**
     * Test {@see DateRangesHelper::formatRange()} method.
     *
     * @param string $expected Expected result.
     * @param \DateTimeInterface $start Start of range.
     * @param \DateTimeInterface|null $end End of range.
     * @return void
     * @dataProvider formatRangeProvider()
     * @covers ::formatRange()
     */
    public function testFormatRange(string $expected, DateTimeInterface $start, DateTimeInterface|null $end): void
    {
        $range = new DateRange([
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $actual = $this->DateRanges->formatRange($range);

        static::assertSame($expected, $actual);
    }

    /**
     * Test {@see DateRangesHelper::formatRange()} method when arguments are swapped.
     *
     * @return void
     * @covers ::formatRange()
     */
    public function testFormatRangeSwapped(): void
    {
        $range = new DateRange([
            'start_date' => new FrozenTime('2021-09-09T13:26:31'),
            'end_date' => new FrozenTime('1992-08-17T05:00:00'),
        ]);
        $actual = $this->DateRanges->formatRange($range);

        static::assertSame('dal 17 ago 1992 al 9 set 2021', $actual);
    }

    /**
     * Data provider for {@see DateRangesHelperTest::testGetClosestRange()} test case.
     *
     * @return array[]
     */
    public function getClosestRangeProvider(): array
    {
        return [
            'overlapping' => [
                [new FrozenTime('2021-09-01T00:00:00'), new FrozenTime('2021-09-30T23:59:59')],
                [
                    [new FrozenTime('1992-08-01T00:00:00'), new FrozenTime('1992-08-31T23:59:59')],
                    [new FrozenTime('2021-09-01T00:00:00'), new FrozenTime('2021-09-30T23:59:59')],
                    [new FrozenTime('2022-08-01T00:00:00'), new FrozenTime('2022-08-31T23:59:59')],
                ],
            ],
            'all past' => [
                [new FrozenTime('2001-08-01T00:00:00'), new FrozenTime('2001-08-31T23:59:59')],
                [
                    [new FrozenTime('1992-08-01T00:00:00'), new FrozenTime('1992-08-31T23:59:59')],
                    [new FrozenTime('1999-09-01T00:00:00'), new FrozenTime('1999-09-30T23:59:59')],
                    [new FrozenTime('2001-08-01T00:00:00'), new FrozenTime('2001-08-31T23:59:59')],
                ],
            ],
            'interlaced' => [
                [new FrozenTime('2022-09-01T00:00:00'), new FrozenTime('2022-09-30T23:59:59')],
                [
                    [new FrozenTime('1992-08-01T00:00:00'), new FrozenTime('1992-08-31T23:59:59')],
                    [new FrozenTime('2023-09-01T00:00:00'), new FrozenTime('2023-09-30T23:59:59')],
                    [new FrozenTime('2001-08-01T00:00:00'), new FrozenTime('2001-08-31T23:59:59')],
                    [new FrozenTime('2022-09-01T00:00:00'), new FrozenTime('2022-09-30T23:59:59')],
                ],
            ],
            'empty' => [
                null,
                [],
            ],
        ];
    }

    /**
     * Test {@see DateRangesHelper::getClosestRange()} method.
     *
     * @param \DateTimeInterface[]|null $expected Expected result.
     * @param \DateTimeInterface[][] $ranges Ranges.
     * @return void
     * @dataProvider getClosestRangeProvider()
     * @covers ::getClosestRange()
     */
    public function testGetClosestRange(array|null $expected, array $ranges): void
    {
        $actual = $this->DateRanges->getClosestRange(array_map(
            function (array $range): DateRange {
                [$start, $end] = $range;

                return new DateRange([
                    'start_date' => $start,
                    'end_date' => $end,
                ]);
            },
            $ranges,
        ));

        if ($expected === null) {
            static::assertNull($actual);
        } else {
            [$start, $end] = $expected;
            static::assertEquals($start, $actual->start_date);
            static::assertEquals($end, $actual->end_date);
        }
    }

    /**
     * Test {@see DateRangesHelper::sortByClosestRange()} method.
     *
     * @return void
     * @covers ::sortByClosestRange()
     */
    public function testSortByClosestRange(): void
    {
        $items = array_map(function (array $ranges) {
            $id = array_shift($ranges);

            return new ObjectEntity([
                'id' => $id,
                'type' => 'events',
                'date_ranges' => array_map(fn(array $range): DateRange => new DateRange([
                    'start_date' => $range[0],
                    'end_date' => $range[1],
                ]), $ranges),
            ]);
        }, [
            [1, [new FrozenTime('1992-08-01T00:00:00'), new FrozenTime('1992-08-31T23:59:59')]],
            [2, [new FrozenTime('2023-09-01T00:00:00'), new FrozenTime('2023-09-30T23:59:59')]],
            [3],
            [4, [new FrozenTime('2001-08-01T00:00:00'), new FrozenTime('2001-08-31T23:59:59')]],
            [5, [new FrozenTime('2022-09-01T00:00:00'), new FrozenTime('2022-09-30T23:59:59')]],
            [6, [new FrozenTime('1950-09-01T00:00:00'), new FrozenTime('1950-09-30T23:59:59')]],
        ]);

        $sorted = $this->DateRanges->sortByClosestRange($items)->toList();

        $expected = [3, 6, 1, 4, 5, 2];
        $actual = array_map(fn($item) => $item->id, $sorted);

        static::assertSame($expected, $actual);
    }
}

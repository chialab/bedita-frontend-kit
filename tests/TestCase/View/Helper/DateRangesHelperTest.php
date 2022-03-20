<?php
namespace Chialab\FrontendKit\Test\TestCase\View\Helper;

use BEdita\Core\Model\Entity\DateRange;
use Cake\I18n\FrozenTime;
use Cake\I18n\I18n;
use Chialab\FrontendKit\View\Helper\DateRangesHelper;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * {@see \Chialab\FrontendKit\View\Helper\DateRangesHelper} Test Case
 *
 * @coversDefaultClass \Chialab\FrontendKit\View\Helper\DateRangesHelper
 */
class DateRangesHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Chialab\FrontendKit\View\Helper\DateRangesHelper
     */
    protected DateRangesHelper $DateRanges;

    /** @inheritDoc */
    public function setUp()
    {
        parent::setUp();

        $this->locale = I18n::getLocale();
        $this->DateRanges = new DateRangesHelper(new View());
        I18n::setlocale('it_IT');
    }

    /** @inheritDoc */
    public function tearDown()
    {
        I18n::setlocale($this->locale);
        unset($this->DateRanges, $this->locale);
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
                'dal 1 gennaio al 17 agosto 2021',
                new FrozenTime('2021-01-01T18:15:00'),
                new FrozenTime('2021-08-17T19:00:00'),
            ],

            'different year' => [
                'dal 1 ottobre 2019 al 16 ottobre 2020', // musixmatch
                new FrozenTime('2019-10-01T18:15:00'),
                new FrozenTime('2020-10-16T19:00:00'),
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
     *
     * @dataProvider formatRangeProvider()
     * @covers ::formatRange()
     */
    public function testFormatRange(string $expected, \DateTimeInterface $start, ?\DateTimeInterface $end): void
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
     *
     * @covers ::formatRange()
     */
    public function testFormatRangeSwapped(): void
    {
        $range = new DateRange([
            'start_date' => new FrozenTime('2021-09-09T13:26:31'),
            'end_date' => new FrozenTime('1992-08-17T05:00:00'),
        ]);
        $actual = $this->DateRanges->formatRange($range);

        static::assertSame('dal 17 agosto 1992 al 9 settembre 2021', $actual);
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
                new FrozenTime('2021-09-09T00:00:00'),
                [new FrozenTime('2021-09-01T00:00:00'), new FrozenTime('2021-09-30T23:59:59')],
                [
                    [new FrozenTime('1992-08-01T00:00:00'), new FrozenTime('1992-08-31T23:59:59')],
                    [new FrozenTime('2021-09-01T00:00:00'), new FrozenTime('2021-09-30T23:59:59')],
                    [new FrozenTime('2022-08-01T00:00:00'), new FrozenTime('2022-08-31T23:59:59')],
                ],
            ],
            'all past' => [
                new FrozenTime('2021-09-09T00:00:00'),
                [new FrozenTime('2001-08-01T00:00:00'), new FrozenTime('2001-08-31T23:59:59')],
                [
                    [new FrozenTime('1992-08-01T00:00:00'), new FrozenTime('1992-08-31T23:59:59')],
                    [new FrozenTime('1999-09-01T00:00:00'), new FrozenTime('1999-09-30T23:59:59')],
                    [new FrozenTime('2001-08-01T00:00:00'), new FrozenTime('2001-08-31T23:59:59')],
                ],
            ],
            'interlaced' => [
                new FrozenTime('2021-09-09T00:00:00'),
                [new FrozenTime('2022-09-01T00:00:00'), new FrozenTime('2022-09-30T23:59:59')],
                [
                    [new FrozenTime('1992-08-01T00:00:00'), new FrozenTime('1992-08-31T23:59:59')],
                    [new FrozenTime('2023-09-01T00:00:00'), new FrozenTime('2023-09-30T23:59:59')],
                    [new FrozenTime('2001-08-01T00:00:00'), new FrozenTime('2001-08-31T23:59:59')],
                    [new FrozenTime('2022-09-01T00:00:00'), new FrozenTime('2022-09-30T23:59:59')],
                ],
            ],
            'empty' => [
                new FrozenTime('2021-09-09T00:00:00'),
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
     *
     * @dataProvider getClosestRangeProvider()
     * @covers ::getClosestRange()
     */
    public function testGetClosestRange(FrozenTime $now, ?array $expected, array $ranges): void
    {
        $actual = $this->DateRanges->getClosestRange(array_map(
            function (array $range): DateRange {
                [$start, $end] = $range;

                return new DateRange([
                    'start_date' => $start,
                    'end_date' => $end,
                ]);
            },
            $ranges
        ), $now);

        if ($expected === null) {
            static::assertNull($actual);
        } else {
            [$start, $end] = $expected;
            static::assertEquals($start, $actual->start_date);
            static::assertEquals($end, $actual->end_date);
        }
    }
}

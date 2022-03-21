<?php
namespace Chialab\FrontendKit\Test\TestCase\View\Helper;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\FrozenTime;
use Cake\Routing\Router;
use Chialab\FrontendKit\View\Helper\CalendarHelper;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * {@see \Chialab\FrontendKit\View\Helper\CalendarHelper} Test Case
 *
 * @coversDefaultClass \Chialab\FrontendKit\View\Helper\CalendarHelper
 */
class CalendarHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Chialab\FrontendKit\View\Helper\CalendarHelper
     */
    protected CalendarHelper $Calendar;

    /** @inheritDoc */
    public function setUp()
    {
        parent::setUp();

        $request = (new ServerRequest())
            ->withParam('controller', 'App')
            ->withParam('action', 'index')
            ->withQueryParams([
                'day' => '25',
                'month' => '3',
                'year' => '2022',
            ]);
        Router::pushRequest($request);

        $response = new Response();
        /** @var \Cake\Controller\Controller */
        $this->controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setConstructorArgs([$request, $response])
            ->setMethods(null)
            ->getMock();
        $this->controller->setName('App');
        $this->Calendar = new CalendarHelper(new View());
    }

    /** @inheritDoc */
    public function tearDown()
    {
        unset($this->Calendar, $this->controller);
        parent::tearDown();
    }

    /**
     * Data provider for {@see CalendarHelperTest::testGetYears()} test case.
     *
     * @return array[]
     */
    public function getYearsProvider(): array
    {
        return [
            'relative' => [
                [2020, 2021, 2022, 2023, 2024],
                '-2 years',
                '+2 years',
                '2022-04-25T00:00:00',
            ],
            'absolute' => [
                [2021, 2022, 2023],
                2021,
                2023,
                null,
            ],
        ];
    }

    /**
     * Test {@see CalendarHelperTest::getYears()} method.
     *
     * @param array $expected Expected result.
     * @param int|string $start Start of range.
     * @param int|string $end End of range.
     * @param \DateTimeInterface|null $from Start date.
     * @return void
     *
     * @dataProvider getYearsProvider()
     * @covers ::getYears()
     */
    public function testGetYears(array $expected, $start, $end, $from): void
    {
        $actual = $this->Calendar->getYears($start, $end, $from);

        static::assertEquals($expected, $actual);
    }

    /**
     * Test {@see CalendarHelperTest::getMonths()} method.
     *
     * @return void
     *
     * @covers ::getMonths()
     */
    public function testGetMonths()
    {
        $actual = $this->Calendar->getMonths();

        static::assertEquals(
            [
                1 => 'January',
                2 => 'February',
                3 => 'March',
                4 => 'April',
                5 => 'May',
                6 => 'June',
                7 => 'July',
                8 => 'August',
                9 => 'September',
                10 => 'October',
                11 => 'November',
                12 => 'December',
            ],
            $actual
        );
    }

    /**
     * Data provider for {@see CalendarHelperTest::testGetDaysInMonth()} test case.
     *
     * @return array[]
     */
    public function getDaysInMonthProvider(): array
    {
        return [
            'november' => [
                30,
                11,
                2022,
            ],
            'march' => [
                31,
                3,
                2022,
            ],
            'february' => [
                28,
                2,
                2022,
            ],
            'leap' => [
                29,
                2,
                2024,
            ],
        ];
    }

    /**
     * Test {@see CalendarHelperTest::getDaysInMonth()} method.
     *
     * @param int $expected Expected result.
     * @param int $month The month.
     * @param int $year The year.
     * @return void
     *
     * @dataProvider getDaysInMonthProvider()
     * @covers ::getDaysInMonth()
     */
    public function testGetDaysInMonth(int $expected, int $month, int $year): void
    {
        $actual = $this->Calendar->getDaysInMonth($month, $year);

        static::assertSame($expected, count($actual));
    }

    /**
     * Data provider for {@see CalendarHelperTest::testUrl()} test case.
     *
     * @return array[]
     */
    public function urlProvider(): array
    {
        return [
            'absolute' => [
                '/?day=25&month=4&year=2022',
                '2022-04-25',
                null,
            ],
            'data' => [
                '/?day=25&month=4&year=2022',
                FrozenTime::create(2022, 4, 25),
                null,
            ],
            'relative' => [
                '/?day=25&month=4&year=2022',
                '+1 month',
                null,
            ],
            'from' => [
                '/?day=1&month=9&year=2022',
                '+1 month',
                '2022-08-01',
            ],
        ];
    }

    /**
     * Test {@see CalendarHelperTest::url()} method.
     *
     * @param string $expected Expected result.
     * @param mixed $date The absolute or relative date.
     * @param array $options Link options.
     * @param mixed $start The start date for relative urls.
     * @return void
     *
     * @dataProvider urlProvider()
     * @covers ::url()
     */
    public function testUrl(string $expected, $date, $start): void
    {
        $actual = $this->Calendar->url($date, [], $start);

        static::assertEquals($expected, html_entity_decode($actual));
    }

    /**
     * Data provider for {@see CalendarHelperTest::testLink()} test case.
     *
     * @return array[]
     */
    public function linkProvider(): array
    {
        return [
            'absolute' => [
                '<a href="/?day=25&month=4&year=2022">Link</a>',
                '2022-04-25',
                null,
            ],
            'data' => [
                '<a href="/?day=25&month=4&year=2022">Link</a>',
                FrozenTime::create(2022, 4, 25),
                null,
            ],
            'relative' => [
                '<a href="/?day=25&month=4&year=2022">Link</a>',
                '+1 month',
                null,
            ],
            'from' => [
                '<a href="/?day=1&month=9&year=2022">Link</a>',
                '+1 month',
                '2022-08-01',
            ],
        ];
    }

    /**
     * Test {@see CalendarHelperTest::link()} method.
     *
     * @param string $expected Expected result.
     * @param mixed $date The absolute or relative date.
     * @param array $options Link options.
     * @param mixed $start The start date for relative urls.
     * @return void
     *
     * @dataProvider linkProvider()
     * @covers ::link()
     */
    public function testLink(string $expected, $date, $start): void
    {
        $actual = $this->Calendar->link('Link', $date, [], $start);

        static::assertEquals($expected, html_entity_decode($actual));
    }
}

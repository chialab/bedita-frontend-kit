<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\FrozenTime;
use Cake\TestSuite\TestCase;

/**
 * {@see \Chialab\FrontendKit\Controller\Component\CalendarComponent} Test Case
 *
 * @coversDefaultClass \Chialab\FrontendKit\Controller\Component\CalendarComponent
 */
class CalendarComponentTest extends TestCase
{
    public $fixtures = [
        'plugin.Chialab/FrontendKit.ObjectTypes',
        'plugin.BEdita/Core.PropertyTypes',
        'plugin.Chialab/FrontendKit.Properties',
        'plugin.Chialab/FrontendKit.Relations',
        'plugin.Chialab/FrontendKit.RelationTypes',
        'plugin.Chialab/FrontendKit.Objects',
        'plugin.Chialab/FrontendKit.Users',
        'plugin.Chialab/FrontendKit.Media',
        'plugin.Chialab/FrontendKit.Streams',
        'plugin.Chialab/FrontendKit.Profiles',
        'plugin.Chialab/FrontendKit.Trees',
        'plugin.Chialab/FrontendKit.ObjectRelations',
        'plugin.Chialab/FrontendKit.DateRanges',
        'plugin.BEdita/Core.Categories',
        'plugin.BEdita/Core.ObjectCategories',
        'plugin.BEdita/Core.Tags',
        'plugin.BEdita/Core.ObjectTags',
    ];

    /**
     * Objects component
     *
     * @var \Chialab\FrontendKit\Controller\Component\ObjectsComponent
     */
    public $Objects;

    /**
     * Test subject
     *
     * @var \Chialab\FrontendKit\Controller\Component\CalendarComponent
     */
    public $Calendar;

    /**
     * The request controller.
     *
     * @var \Cake\Controller\Controller
     */
    public $controller;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $request = new ServerRequest();
        $response = new Response();
        /** @var \Cake\Controller\Controller */
        $this->controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setConstructorArgs([$request, $response])
            ->setMethods(null)
            ->getMock();

        $this->controller->viewBuilder()->setTemplatePath('Pages');

        $registry = new ComponentRegistry($this->controller);
        $this->Objects = $registry->load('Chialab/FrontendKit.Objects');
        $this->Calendar = $registry->load('Chialab/FrontendKit.Calendar');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Calendar, $this->Objects, $this->controller);

        parent::tearDown();
    }

    /**
     * Data provider for {@see CalendarComponentTest::testFindGroupByDayWithStart()} test case.
     *
     * @return array[]
     */
    public function findGroupByDayWithStartProvider()
    {
        return [
            'test' => [
                [
                    '2022-02-15' => [
                        'event-1',
                        'event-2',
                    ],
                    '2022-02-16' => [
                        'event-2',
                        'event-3',
                    ],
                    '2022-02-17' => [
                        'event-2',
                        'event-3',
                    ],
                    '2022-02-18' => [
                        'event-3',
                    ],
                    '2022-02-21' => [
                        'event-3',
                    ],
                    '2022-02-22' => [
                        'event-3',
                    ],
                ],
                '2022-02-15 00:00:00',
            ],
        ];
    }

    /**
     * Test {@see CalendarComponent::findGroupedByDay()}.
     *
     * @param array $expected Expected objects.
     * @param string $start Start date.
     * @return void
     *
     * @covers ::findGroupedByDay()
     * @dataProvider findGroupByDayWithStartProvider()
     */
    public function testFindGroupByDayWithStart(array $expected, $start)
    {
        $events = $this->Calendar->findGroupedByDay(
            $this->Objects->loadObjects([], 'events'),
            new FrozenTime($start)
        )->toArray();

        static::assertEquals($expected, array_map(fn ($items) => array_map(fn ($event) => $event->uname, $items), $events), '', 0, 10, true);
    }

    /**
     * Data provider for {@see CalendarComponentTest::testFindGroupedByDayWithRange()} test case.
     *
     * @return array[]
     */
    public function findGroupedByDayWithRangeProvider()
    {
        return [
            'test' => [
                [
                    '2022-02-15' => [
                        'event-1',
                        'event-2',
                    ],
                    '2022-02-16' => [
                        'event-2',
                        'event-3',
                    ],
                    '2022-02-17' => [
                        'event-2',
                        'event-3',
                    ],
                ],
                '2022-02-15 00:00:00',
                '2022-02-17 00:00:00',
            ],
        ];
    }

    /**
     * Test {@see CalendarComponent::findGroupedByDay()}.
     *
     * @param array $expected Expected objects.
     * @param string $start Start date.
     * @param string $end End date.
     * @return void
     *
     * @covers ::findGroupedByDay()
     * @dataProvider findGroupedByDayWithRangeProvider()
     */
    public function testFindGroupedByDayWithRange(array $expected, string $start, string $end)
    {
        $start = new FrozenTime('2022-02-15 00:00:00');
        $events = $this->Calendar->findGroupedByDay(
            $this->Objects->loadObjects([], 'events'),
            new FrozenTime($start),
            new FrozenTime($end)
        )->toArray();

        static::assertEquals($expected, array_map(fn ($items) => array_map(fn ($event) => $event->uname, $items), $events), '', 0, 10, true);
    }
}

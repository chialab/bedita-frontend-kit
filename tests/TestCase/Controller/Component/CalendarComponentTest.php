<?php
namespace Chialab\FrontendKit\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\FrozenDate;
use Cake\TestSuite\TestCase;

/**
 * Chialab\FrontendKit\Controller\Component\CalendarComponent Test Case
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

    public function testGroupByDayWithStart()
    {
        $start = new FrozenDate('2022-02-15 00:00:00');
        $events = $this->Calendar->groupByDay($this->Objects->loadObjects([], 'events'), $start)
            ->toArray();

        static::assertEquals([
            '2022-02-15T00:00:00+00:00' => [
                'event-1',
                'event-2',
            ],
            '2022-02-16T00:00:00+00:00' => [
                'event-2',
                'event-3',
            ],
            '2022-02-17T00:00:00+00:00' => [
                'event-2',
                'event-3',
            ],
            '2022-02-18T00:00:00+00:00' => [
                'event-3',
            ],
            '2022-02-21T00:00:00+00:00' => [
                'event-3',
            ],
            '2022-02-22T00:00:00+00:00' => [
                'event-3',
            ],
        ], array_map(fn ($items) => array_map(fn ($event) => $event->uname, $items), $events));
    }

    public function testGroupByDayWithRange()
    {
        $start = new FrozenDate('2022-02-15 00:00:00');
        $events = $this->Calendar->groupByDay($this->Objects->loadObjects([], 'events'), $start, $start->addDays(2))
            ->toArray();

        static::assertEquals([
            '2022-02-15T00:00:00+00:00' => [
                'event-1',
                'event-2',
            ],
            '2022-02-16T00:00:00+00:00' => [
                'event-2',
                'event-3',
            ],
            '2022-02-17T00:00:00+00:00' => [
                'event-2',
                'event-3',
            ],
        ], array_map(fn ($items) => array_map(fn ($event) => $event->uname, $items), $events));
    }
}

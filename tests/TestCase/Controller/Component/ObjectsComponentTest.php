<?php
namespace Chialab\Frontend\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;
use Chialab\Frontend\Controller\Component\ObjectsComponent;

/**
 * Chialab\Frontend\Controller\Component\ObjectsComponent Test Case
 */
class ObjectsComponentTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Chialab\Frontend\Controller\Component\ObjectsComponent
     */
    public $Objects;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $registry = new ComponentRegistry();
        $this->Objects = new ObjectsComponent($registry);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Objects);

        parent::tearDown();
    }

    /**
     * Test initial setup
     *
     * @return void
     */
    public function testInitialization()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}

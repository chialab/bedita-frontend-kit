<?php
namespace Chialab\FrontendKit\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;
use Chialab\FrontendKit\Controller\Component\ObjectsComponent;

/**
 * Chialab\FrontendKit\Controller\Component\ObjectsComponent Test Case
 */
class ObjectsComponentTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Chialab\FrontendKit\Controller\Component\ObjectsComponent
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

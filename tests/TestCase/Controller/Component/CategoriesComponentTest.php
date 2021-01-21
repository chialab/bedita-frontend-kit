<?php
namespace Chialab\FrontendKit\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;
use Chialab\FrontendKit\Controller\Component\CategoriesComponent;

/**
 * Chialab\FrontendKit\Controller\Component\CategoriesComponent Test Case
 */
class CategoriesComponentTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Chialab\FrontendKit\Controller\Component\CategoriesComponent
     */
    public $Categories;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $registry = new ComponentRegistry();
        $this->Categories = new CategoriesComponent($registry);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Categories);

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

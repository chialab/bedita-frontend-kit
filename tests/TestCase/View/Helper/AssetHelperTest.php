<?php
namespace Chialab\FrontendKit\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use Chialab\FrontendKit\View\Helper\AssetHelper;

/**
 * Chialab\FrontendKit\View\Helper\AssetHelper Test Case
 */
class AssetHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Chialab\FrontendKit\View\Helper\AssetHelper
     */
    public $Asset;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $view = new View();
        $this->Asset = new AssetHelper($view);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Asset);

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

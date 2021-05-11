<?php
namespace Chialab\FrontendKit\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use Chialab\FrontendKit\View\Helper\PlaceholdersHelper;

/**
 * Chialab\FrontendKit\View\Helper\PlaceholdersHelper Test Case
 */
class PlaceholdersHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Chialab\FrontendKit\View\Helper\PlaceholdersHelper
     */
    public $Placeholders;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $view = new View();
        $this->Placeholders = new PlaceholdersHelper($view);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Placeholders);

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

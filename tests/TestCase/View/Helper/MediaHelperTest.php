<?php
namespace Chialab\FrontendKit\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use Chialab\FrontendKit\View\Helper\MediaHelper;

/**
 * {@see \Chialab\FrontendKit\View\Helper\MediaHelper} Test Case
 * 
 * @coversDefaultClass \Chialab\FrontendKit\View\Helper\MediaHelper
 */
class MediaHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Chialab\FrontendKit\View\Helper\MediaHelper
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
        $this->Media = new MediaHelper($view);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Media);

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

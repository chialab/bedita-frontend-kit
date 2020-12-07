<?php
namespace Chialab\Frontend\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use Chialab\Frontend\View\Helper\PosterHelper;

/**
 * Chialab\Frontend\View\Helper\PosterHelper Test Case
 */
class PosterHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Chialab\Frontend\View\Helper\PosterHelper
     */
    public $Poster;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $view = new View();
        $this->Poster = new PosterHelper($view);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Poster);

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

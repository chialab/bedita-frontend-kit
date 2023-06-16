<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use Chialab\FrontendKit\View\Helper\DownloadHelper;

/**
 * Chialab\FrontendKit\View\Helper\DownloadHelper Test Case
 */
class DownloadHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Chialab\FrontendKit\View\Helper\DownloadHelper
     */
    public $Download;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $view = new View();
        $this->Download = new DownloadHelper($view);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Download);

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

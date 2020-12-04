<?php
namespace Chialab\Frontend\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;
use Chialab\Frontend\Controller\Component\PublicationComponent;

/**
 * Chialab\Frontend\Controller\Component\PublicationComponent Test Case
 */
class PublicationComponentTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Chialab\Frontend\Controller\Component\PublicationComponent
     */
    public $Publication;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $registry = new ComponentRegistry();
        $this->Publication = new PublicationComponent($registry);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Publication);

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

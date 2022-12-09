<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestCase\Controller\Component;

use BEdita\Core\Model\Entity\Folder;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * {@see \Chialab\FrontendKit\Controller\Component\PublicationComponent} Test Case
 *
 * @coversDefaultClass \Chialab\FrontendKit\Controller\Component\PublicationComponent
 */
class PublicationComponentTest extends TestCase
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
        'plugin.BEdita/Core.Categories',
        'plugin.BEdita/Core.ObjectCategories',
        'plugin.BEdita/Core.Tags',
        'plugin.BEdita/Core.ObjectTags',
        'plugin.BEdita/Core.Translations',
    ];

    /**
     * Test subject
     *
     * @var \Chialab\FrontendKit\Controller\Component\PublicationComponent
     */
    public $Publication;

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
        /** @var \Cake\Controller\Controller $controller */
        $controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setConstructorArgs([$request, $response])
            ->setMethods(null)
            ->getMock();

            $controller->viewBuilder()->setTemplatePath('Pages');
        $this->controller = $controller;

        $registry = new ComponentRegistry($this->controller);
        $registry->load('Chialab/FrontendKit.Objects', [
            'objectTypesConfig' => [
                'objects' => ['include' => 'poster'],
                'folders' => ['include' => 'children,parents,poster'],
            ],
            'autoHydrateAssociations' => [
                'parents' => 2,
                'children' => 3,
            ],
        ]);
        $this->Publication = $registry->load('Chialab/FrontendKit.Publication', [
            'publication' => 'root-1',
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Publication, $this->controller);

        parent::tearDown();
    }

    /**
     * Test {@see PublicationComponent::initialize()}.
     *
     * @covers ::initialize()
     * @covers ::getPublication()
     */
    public function testInitialization()
    {
        $publication = $this->Publication->getPublication();
        static::assertNotNull($publication);
        static::assertInstanceOf(Folder::class, $publication);
        static::assertSame('root-1', $publication->uname);
    }

    /**
     * Test {@see PublicationComponent::initialize()}.
     *
     * @covers ::initialize()
     */
    public function testTemplateVars()
    {
        static::assertNotNull($this->controller->viewVars['publication']);
    }
}

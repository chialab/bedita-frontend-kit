<?php
namespace Chialab\FrontendKit\Test\TestCase\Controller\Component;

use BEdita\Core\Model\Entity\Folder;
use Cake\Controller\ComponentRegistry;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Chialab\FrontendKit\Controller\Component\PublicationComponent;

/**
 * Chialab\FrontendKit\Controller\Component\PublicationComponent Test Case
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
        'plugin.Chialab/FrontendKit.Trees',
        'plugin.Chialab/FrontendKit.ObjectRelations',
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
        /** @var \Cake\Controller\Controller */
        $this->controller = $this->getMockBuilder('Cake\Controller\Controller')
            ->setConstructorArgs([$request, $response])
            ->setMethods(null)
            ->getMock();

        $this->controller->viewBuilder()->setTemplatePath('Pages');

        $registry = new ComponentRegistry($this->controller);
        $this->Publication = new PublicationComponent($registry, [
            'publication' => 'root-1',
            'menuFolders' => [
                'main' => 'root-1',
            ],
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

    public function testInitialization()
    {
        $publication = $this->Publication->getPublication();
        static::assertNotNull($publication);
        static::assertInstanceOf(Folder::class, $publication);
        static::assertSame('root-1', $publication->uname);
    }

    public function testMenuFolders()
    {
        /** @var \BEdita\Core\Model\Entity\Folder[] */
        $menuFolders = $this->controller->viewVars['menuFolders'];
        static::assertNotEmpty($menuFolders);
    }

    public function testTemplateVars()
    {
        static::assertNotNull($this->controller->viewVars['publication']);
        static::assertNotNull($this->controller->viewVars['menuFolders']);
    }

    /**
     * @covers ::genericTreeAction()
     */
    public function testGenericTreeAction()
    {
        $response = $this->Publication->genericTreeAction('parent-1/child-1');

        /** @var \BEdita\Core\Model\Entity\Folder */
        $object = $this->controller->viewVars['object'];
        /** @var \BEdita\Core\Model\Entity\Folder */
        $parent = $this->controller->viewVars['parent'];
        /** @var \BEdita\Core\Model\Entity\Folder[] */
        $ancestors = $this->controller->viewVars['ancestors'];

        static::assertNotNull($object);
        static::assertSame('child-1', $object->uname);
        static::assertNotNull($parent);
        static::assertSame('parent-1', $parent->uname);
        static::assertNotEmpty($ancestors);
        static::assertSame(1, count($ancestors));
        static::assertSame('parent-1', $ancestors[0]->uname);
        static::assertSame('folders', (string)$response->getBody());
    }
}

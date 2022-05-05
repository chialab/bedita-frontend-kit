<?php
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

    /**
     * Test {@see PublicationComponent::genericTreeAction()}.
     *
     * @covers ::genericTreeAction()
     * @covers ::loadObjectPath()
     * @covers ::getViablePaths()
     * @covers ::renderFirstTemplate()
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

    /**
     * Test {@see PublicationComponent::genericTreeAction()}.
     *
     * @covers ::genericTreeAction()
     * @covers ::loadObjectPath()
     * @covers ::getViablePaths()
     * @covers ::renderFirstTemplate()
     */
    public function testGenericTreeActionWithObject()
    {
        $response = $this->Publication->genericTreeAction('parent-1/child-1/profile-1');

        /** @var \BEdita\Core\Model\Entity\Folder */
        $object = $this->controller->viewVars['object'];

        static::assertNotNull($object);
        static::assertSame('profile-1', $object->uname);
        static::assertSame('profiles', $object->type);
        static::assertSame('Alan', $object->name);
        static::assertSame('Turing', $object->surname);
        static::assertNotEmpty($object->author_of);
        static::assertNotEmpty($object->author_of->first()->poster);
        static::assertSame('objects', (string)$response->getBody());
    }

    /**
     * Test {@see PublicationComponent::genericTreeAction()}.
     *
     * @covers ::genericTreeAction()
     * @covers ::loadObjectPath()
     * @covers ::getViablePaths()
     */
    public function testGenericTreeActionWithDocument()
    {
        $this->Publication->genericTreeAction('parent-1/child-1/document-1');

        /** @var \BEdita\Core\Model\Entity\Folder */
        $object = $this->controller->viewVars['object'];

        static::assertNotNull($object);
        static::assertSame('document-1', $object->uname);
        static::assertSame('documents', $object->type);
        static::assertNotEmpty($object->poster);
        static::assertNotEmpty($object->has_author);
        $author = $object->has_author->first();
        static::assertSame('profile-1', $author->uname);
        static::assertSame('profiles', $author->type);
        static::assertSame('Alan', $author->name);
        static::assertSame('Turing', $author->surname);
    }

    /**
     * Test {@see PublicationComponent::genericTreeAction()}.
     *
     * @covers ::genericTreeAction()
     */
    public function testFilteredChildren()
    {
        $this->Publication->genericTreeAction('parent-1/child-1');
        $children = $this->controller->viewVars['children'];
        static::assertSame(2, count($children));
        static::assertSame('Document 1', $children[0]->title);
        static::assertSame('Profile 1', $children[1]->title);

        $this->Publication->genericTreeAction('parent-1/child-1', ['query' => 'Document']);
        $children = $this->controller->viewVars['children'];
        static::assertSame(1, count($children));
        static::assertSame('Document 1', $children[0]->title);
    }

    /**
     * Test {@see PublicationComponent::genericTreeAction()}.
     *
     * @covers ::genericTreeAction()
     */
    public function testSortedChildren()
    {
        $this->Publication->genericTreeAction('parent-1/child-1');
        $children = $this->controller->viewVars['children'];
        static::assertSame('Document 1', $children[0]->title);
        static::assertSame('Profile 1', $children[1]->title);

        $this->controller->request = $this->controller->request->withQueryParams([
            'sort' => 'title',
            'direction' => 'desc',
        ]);
        $this->Publication->genericTreeAction('parent-1/child-1');
        $children = $this->controller->viewVars['children'];
        static::assertSame('Profile 1', $children[0]->title);
        static::assertSame('Document 1', $children[1]->title);
    }

    /**
     * Test {@see PublicationComponent::genericTreeAction()}.
     *
     * @covers ::genericTreeAction()
     */
    public function testChildrenParams()
    {
        $this->Publication->genericTreeAction('parent-1/child-1');
        $children = $this->controller->viewVars['children'];

        static::assertSame(true, $children[0]->relation['menu']);
        static::assertSame(false, $children[0]->relation['canonical']);
    }
}

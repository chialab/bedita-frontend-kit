<?php
namespace Chialab\FrontendKit\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;

/**
 * {@see \Chialab\FrontendKit\Controller\Component\ObjectsComponent} Test Case
 * 
 * @coversDefaultClass \Chialab\FrontendKit\Controller\Component\ObjectsComponent
 */
class ObjectsComponentTest extends TestCase
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
    ];

    /**
     * Test subject
     *
     * @var \Chialab\FrontendKit\Controller\Component\ObjectsComponent
     */
    public $Objects;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $registry = new ComponentRegistry();
        $this->Objects = $registry->load('Chialab/FrontendKit.Objects', [
            'objectTypesConfig' => [
                'objects' => ['include' => 'poster'],
                'folders' => ['include' => 'children,parents,poster'],
            ],
            'autoHydrateAssociations' => [
                'parents' => 2,
                'children' => 3,
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
        unset($this->Objects);

        parent::tearDown();
    }

    /**
     * Test {@see ObjectsComponent::loadObject()}.
     * 
     * @covers ::initialize()
     * @covers ::loadObject()
     */
    public function testLoadObjectFolder()
    {
        $object = $this->Objects->loadObject('child-1', 'folders', ['include' => 'children']);

        static::assertSame('child-1', $object->uname);
        static::assertSame('folders', $object->type);
        static::assertNotEmpty($object->children);

        $children = $object->children->toArray();
        static::assertSame('document-1', $children[0]->uname);
        static::assertSame('documents', $children[0]->type);
        static::assertNotEmpty($children[0]->poster);
        $poster = $children[0]->poster[0];
        static::assertSame('image-1', $poster->uname);
        static::assertSame('images', $poster->type);

        static::assertSame('profile-1', $children[1]->uname);
        static::assertSame('profiles', $children[1]->type);
        static::assertSame('Alan', $children[1]->name);
        static::assertSame('Turing', $children[1]->surname);
        static::assertEmpty($children[1]->author_of);
    }
}

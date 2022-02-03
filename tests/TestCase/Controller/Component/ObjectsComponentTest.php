<?php
namespace Chialab\FrontendKit\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;
use Chialab\FrontendKit\Controller\Component\ObjectsComponent;

/**
 * Chialab\FrontendKit\Controller\Component\ObjectsComponent Test Case
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
        'plugin.Chialab/FrontendKit.Media',
        'plugin.Chialab/FrontendKit.Streams',
        'plugin.Chialab/FrontendKit.Users',
        'plugin.Chialab/FrontendKit.Trees',
        'plugin.Chialab/FrontendKit.ObjectRelations',
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
        $this->Objects = new ObjectsComponent($registry, [
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

    public function testLoadObjectFolder()
    {
        $object = $this->Objects->loadObject('child-1', 'folders', ['include' => 'children']);

        static::assertSame('child-1', $object->uname);
        static::assertSame('folders', $object->type);
        static::assertNotEmpty($object->children);
        
        $child = $object->children->first();
        static::assertSame('document-1', $child->uname);
        static::assertSame('documents', $child->type);
        static::assertNotEmpty($child->poster);

        $poster = $child->poster[0];
        static::assertSame('image-1', $poster->uname);
        static::assertSame('images', $poster->type);
    }
}

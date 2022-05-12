<?php
namespace Chialab\FrontendKit\Test\TestCase\Controller\Component;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Chialab\FrontendKit\Test\TestApp\Controller\PagesController;

/**
 * {@see \Chialab\FrontendKit\Traits\RenderTrait} Test Case
 *
 * @coversDefaultClass \Chialab\FrontendKit\Traits\RenderTrait
 */
class RenderTraitTest extends TestCase
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
     * The request controller.
     *
     * @var \Chialab\FrontendKit\Test\TestApp\Controller\PagesController
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

        $request = new ServerRequest([
            'params' => [
                'controller' => 'Pages',
            ],
        ]);
        $response = new Response();
        $this->controller = new PagesController($request, $response);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->controller);

        parent::tearDown();
    }

    /**
     * Data provider for {@see RenderTraitTest::testGetTemplatesToIterate()} test case.
     *
     * @return array[]
     */
    public function getTemplatesToIterateProvider()
    {
        return [
            'single' => [['profile-1', 'profiles', 'objects'], 12, []],
            'path' => [
                ['profile-1', 'parent-1.profiles', 'parent-1.objects', 'root-1.profiles', 'root-1.objects', 'profiles', 'objects'],
                12,
                [2, 4],
            ],
        ];
    }

    /**
     * Test {@see RenderTrait::getTemplatesToIterate()}.
     *
     * @covers ::getTemplatesToIterate()
     * @dataProvider getTemplatesToIterateProvider()
     */
    public function testGetTemplatesToIterate($expected, $id, $parents)
    {
        $Object = TableRegistry::getTableLocator()->get('BEdita/Core.Objects');
        $Folders = TableRegistry::getTableLocator()->get('BEdita/Core.Folders');
        $object = $Object->get($id, ['contain' => 'ObjectTypes']);
        $folders = array_map(fn (int $id) => $Folders->get($id), $parents);

        $result = [...$this->controller->getTemplatesToIterate($object, ...$folders)];

        static::assertEquals($expected, $result);
    }

    /**
     * Data provider for {@see RenderTraitTest::testRenderFirstTemplate()} test case.
     *
     * @return array[]
     */
    public function renderFirstTemplateProvider()
    {
        return [
            'single' => ['profiles', 12, []],
            'path' => ['parent-1', 12, [2, 4]],
        ];
    }

    /**
     * Test {@see RenderTrait::renderFirstTemplate()}.
     *
     * @covers ::renderFirstTemplate()
     * @dataProvider renderFirstTemplateProvider()
     */
    public function testRenderFirstTemplate($expected, $id, $parents)
    {
        $Object = TableRegistry::getTableLocator()->get('BEdita/Core.Objects');
        $Folders = TableRegistry::getTableLocator()->get('BEdita/Core.Folders');
        $object = $Object->get($id, ['contain' => 'ObjectTypes']);
        $folders = array_map(fn (int $id) => $Folders->get($id), $parents);

        $response = $this->controller->renderFirstTemplate(...$this->controller->getTemplatesToIterate($object, ...$folders));

        static::assertEquals($expected, (string)$response->getBody());
    }
}

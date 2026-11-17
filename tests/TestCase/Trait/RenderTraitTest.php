<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestCase\Trait;

use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Chialab\FrontendKit\Test\TestApp\Controller\PagesController;
use Chialab\FrontendKit\Traits\RenderTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * {@see \Chialab\FrontendKit\Traits\RenderTrait} Test Case
 */
#[CoversClass(RenderTrait::class)]
class RenderTraitTest extends TestCase
{
    protected array $fixtures = [
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
    public function setUp(): void
    {
        parent::setUp();

        $request = new ServerRequest([
            'params' => [
                'controller' => 'Pages',
            ],
        ]);
        $this->controller = new PagesController($request);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->controller);

        parent::tearDown();
    }

    /**
     * Data provider for {@see RenderTraitTest::testGetTemplatesToIterate()} test case.
     *
     * @return array[]
     */
    public static function getTemplatesToIterateProvider()
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
     */
    #[DataProvider('getTemplatesToIterateProvider')]
    public function testGetTemplatesToIterate($expected, $id, $parents)
    {
        $Object = TableRegistry::getTableLocator()->get('BEdita/Core.Objects');
        $Folders = TableRegistry::getTableLocator()->get('BEdita/Core.Folders');
        $object = $Object->get($id, contain: ['ObjectTypes']);
        $folders = array_map(fn(int $id) => $Folders->get($id), $parents);

        $result = [...$this->controller->getTemplatesToIterate($object, ...array_reverse($folders))];

        static::assertEquals($expected, $result);
    }

    /**
     * Data provider for {@see RenderTraitTest::testRenderFirstTemplate()} test case.
     *
     * @return array[]
     */
    public static function renderFirstTemplateProvider()
    {
        return [
            'single' => ['profiles', 12, []],
            'path' => ['parent-1', 12, [2, 4]],
        ];
    }

    /**
     * Test {@see RenderTrait::renderFirstTemplate()}.
     */
    #[DataProvider('renderFirstTemplateProvider')]
    public function testRenderFirstTemplate($expected, $id, $parents)
    {
        $Object = TableRegistry::getTableLocator()->get('BEdita/Core.Objects');
        $Folders = TableRegistry::getTableLocator()->get('BEdita/Core.Folders');
        $object = $Object->get($id, contain: ['ObjectTypes']);
        $folders = array_map(fn(int $id) => $Folders->get($id), $parents);

        $response = $this->controller->renderFirstTemplate(...$this->controller->getTemplatesToIterate($object, ...array_reverse($folders)));

        static::assertEquals($expected, (string)$response->getBody());
    }
}

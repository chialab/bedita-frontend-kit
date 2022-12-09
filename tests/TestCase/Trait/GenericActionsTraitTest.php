<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestCase\Controller\Component;

use BEdita\Core\Model\Entity\Folder;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Chialab\FrontendKit\Test\TestApp\Controller\PagesController;

/**
 * {@see \Chialab\FrontendKit\Traits\GenericActionsTrait} Test Case
 *
 * @coversDefaultClass \Chialab\FrontendKit\Traits\GenericActionsTrait
 */
class GenericActionsTraitTest extends TestCase
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
    public function setUp(): void
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
    public function tearDown(): void
    {
        unset($this->controller);

        parent::tearDown();
    }

    /**
     * Data provider for {@see GenericActionsTraitTest::testFallback()} test case.
     *
     * @return array[]
     */
    public function fallbackProvider()
    {
        return [
            'folder' => [
                ['parent-1/child-1', []],
                'child-1',
                [
                    'document-1' => ['menu' => true, 'canonical' => false],
                    'profile-1' => ['menu' => true, 'canonical' => false],
                ],
                [],
                'parent-1',
                ['parent-1'],
                'folders',
            ],
            'sorted' => [
                ['parent-1/child-1', [
                    'sort' => 'title',
                    'direction' => 'desc',
                ]],
                'child-1',
                [
                    'profile-1' => ['menu' => true, 'canonical' => false],
                    'document-1' => ['menu' => true, 'canonical' => false],
                ],
                [],
                'parent-1',
                ['parent-1'],
                'folders',
            ],
            'profile' => [
                ['parent-1/child-1/profile-1', []],
                'profile-1',
                null,
                [],
                'child-1',
                ['parent-1', 'child-1'],
                'parent-1',
            ],
            'document' => [
                ['parent-1/child-1/document-1', []],
                'document-1',
                null,
                [
                    'poster' => ['image-1'],
                    'has_author' => ['profile-1'],
                ],
                'child-1',
                ['parent-1', 'child-1'],
                'objects',
            ],
        ];
    }

    /**
     * Test {@see GenericActionsTrait::fallback()}.
     *
     * @covers ::fallback()
     * @dataProvider fallbackProvider()
     */
    public function testFallback($url, $objectUname, $children, $related, $parentUname, $ancestorUnames, $body)
    {
        [$path, $filters] = $url;
        $this->controller->request = $this->controller->getRequest()->withQueryParams($filters);

        $response = $this->controller->fallback($path);

        /** @var \BEdita\Core\Model\Entity\Folder $object */
        $object = $this->controller->viewBuilder()->getVar('object');
        /** @var \BEdita\Core\Model\Entity\Folder $parent */
        $parent = $this->controller->viewBuilder()->getVar('parent');
        /** @var \BEdita\Core\Model\Entity\Folder[] $ancestors */
        $ancestors = $this->controller->viewBuilder()->getVar('ancestors');

        // assert the object has been correctly load
        static::assertNotNull($object);
        static::assertSame($objectUname, $object->uname);

        if ($children) {
            // if folder, check the children are loaded too
            static::assertSame(array_keys($children), collection($this->controller->viewBuilder()->getVar('children'))->extract('uname')->toArray());

            // check children tree params
            foreach ($this->controller->viewBuilder()->getVar('children') as $child) {
                $params = $children[$child->uname];
                foreach ($params as $key => $param) {
                    static::assertSame($param, $child->relation[$key]);
                }
            }
        } else {
            // children variable should not be set if not a folder
            static::assertNotNull($this->controller->viewBuilder()->getVar('children'));
        }

        // assert main object relations are loaded
        foreach ($related as $key => $list) {
            static::assertSame($list, collection($object->get($key))->extract('uname')->toArray());
        }

        // assert the parent has been correctly loaded
        static::assertSame($parentUname, $parent ? $parent->uname : null);

        // assert the ancestors have been correctly loaded
        static::assertSame($ancestorUnames, array_map(fn (Folder $ancestor): string => $ancestor->uname, $ancestors));

        // check the response result
        static::assertSame($body, (string)$response->getBody());
    }
}

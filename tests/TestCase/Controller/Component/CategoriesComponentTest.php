<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use Chialab\FrontendKit\Controller\Component\CategoriesComponent;
use Chialab\FrontendKit\Model\ObjectsLoader;

/**
 * {@see \Chialab\FrontendKit\Controller\Component\CategoriesComponent} Test Case
 *
 * @coversDefaultClass \Chialab\FrontendKit\Controller\Component\CategoriesComponent
 */
class CategoriesComponentTest extends TestCase
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
        'plugin.Chialab/FrontendKit.DateRanges',
        'plugin.BEdita/Core.Categories',
        'plugin.BEdita/Core.ObjectCategories',
        'plugin.BEdita/Core.Tags',
        'plugin.BEdita/Core.ObjectTags',
    ];

    /**
     * Test subject
     *
     * @var \Chialab\FrontendKit\Controller\Component\CategoriesComponent
     */
    public $Categories;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
        $registry = new ComponentRegistry();
        $this->Categories = new CategoriesComponent($registry);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->Categories);

        parent::tearDown();
    }

    /**
     * Data provider for {@see CategoriesComponentTest::testFilterByCategories()} test case.
     *
     * @return array[]
     */
    public function filterByCategoriesProvider()
    {
        return [
            'inInt' => [[2], [1], 'in'],
            'inNames' => [[2], ['second-cat'], 'in'],
            'inMixed' => [[2], [1, 'second-cat'], 'in'],
            'existsInt' => [[2], [1], 'exists'],
            'existsNames' => [[2], ['second-cat'], 'exists'],
            'existsMixed' => [[2], [1, 'second-cat'], 'exists'],
        ];
    }

    /**
     * Test {@see CategoriessComponent::filterByCategories()}.
     *
     * @param array $expected Expected objects.
     * @param array $ids Ids or names.
     * @param string $strategy The strategy to use.
     * @return void
     *
     * @covers ::filterByCategories()
     * @dataProvider filterByCategoriesProvider()
     */
    public function testFilterByCategories(array $expected, array $ids, string $strategy)
    {
        $loader = new ObjectsLoader([], [], []);
        $query = $loader->loadObjects([]);

        $result = Hash::extract($this->Categories->filterByCategories($query, $ids, $strategy)->toArray(), '{n}.id');

        static::assertEquals($expected, $result);
    }

    /**
     * Data provider for {@see CategoriesComponentTest::testFilterExcludeByCategories()} test case.
     *
     * @return array[]
     */
    public function filterExcludeByCategoriesProvider()
    {
        return [
            'inInt' => [[1, 3, 4], [1], 'in'],
            'inNames' => [[1, 3, 4], ['second-cat'], 'in'],
            'inMixed' => [[1, 3, 4], [1, 'second-cat'], 'in'],
            'existsInt' => [[1, 3, 4], [1], 'exists'],
            'existsNames' => [[1, 3, 4], ['second-cat'], 'exists'],
            'existsMixed' => [[1, 3, 4], [1, 'second-cat'], 'exists'],
        ];
    }

    /**
     * Test {@see CategoriessComponent::filterExcludeByCategories()}.
     *
     * @param array $expected Expected objects.
     * @param array $ids Ids or names.
     * @param string $strategy The strategy to use.
     * @return void
     *
     * @covers ::filterExcludeByCategories()
     * @dataProvider filterExcludeByCategoriesProvider()
     */
    public function testFilterExcludeByCategories(array $expected, array $ids, string $strategy)
    {
        $loader = new ObjectsLoader([], [], []);
        $query = $loader->loadObjects([]);

        $result = Hash::extract(
            $this->Categories->filterExcludeByCategories($query, $ids, $strategy)
                ->limit(3)
                ->toArray(),
            '{n}.id'
        );

        static::assertEquals($expected, $result);
    }
}

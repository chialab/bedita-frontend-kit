<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use Chialab\FrontendKit\Controller\Component\TagsComponent;
use Chialab\FrontendKit\Model\ObjectsLoader;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/** {@see \Chialab\FrontendKit\Controller\Component\TagsComponent} Test Case */
#[CoversClass(TagsComponent::class)]
class TagsComponentTest extends TestCase
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
        'plugin.Chialab/FrontendKit.DateRanges',
        'plugin.BEdita/Core.Categories',
        'plugin.BEdita/Core.ObjectCategories',
        'plugin.Chialab/FrontendKit.Tags',
        'plugin.Chialab/FrontendKit.ObjectTags',
    ];

    protected TagsComponent $Tags;

    public function setUp(): void
    {
        parent::setUp();

        $this->Tags = new TagsComponent(new ComponentRegistry());
    }

    public function tearDown(): void
    {
        unset($this->Tags);
        Configure::delete('I18n.lang');

        parent::tearDown();
    }

    /** Test {@see TagsComponent::load()} returns only enabled tags, sorted and slugged. */
    public function testLoad(): void
    {
        $tags = $this->Tags->load()->toArray();

        static::assertSame(['first-tag', 'second-tag'], Hash::extract($tags, '{n}.name'));
        static::assertSame(['first-tag', 'second-tag'], Hash::extract($tags, '{n}.slug'));
        static::assertSame(['First tag', 'Second tag'], Hash::extract($tags, '{n}.label'));
        static::assertFalse($tags[0]->isDirty());
    }

    /** Test {@see TagsComponent::load()} translates labels, falling back to the default one. */
    public function testLoadTranslated(): void
    {
        Configure::write('I18n.lang', 'it');

        $tags = $this->Tags->load()->toArray();

        static::assertSame(['Primo tag', 'Second tag'], Hash::extract($tags, '{n}.label'));
    }

    /** Data provider for {@see TagsComponentTest::testFilterByTags()}. */
    public static function filterByTagsProvider(): array
    {
        return [
            'inInt' => [[4], [1], 'in'],
            'inNames' => [[4], ['first-tag'], 'in'],
            'inMixed' => [[4, 10], [1, 'second-tag'], 'in'],
            'existsInt' => [[4], [1], 'exists'],
            'existsNames' => [[4], ['first-tag'], 'exists'],
            'existsMixed' => [[4, 10], [1, 'second-tag'], 'exists'],
        ];
    }

    /** Test {@see TagsComponent::filterByTags()}. */
    #[DataProvider('filterByTagsProvider')]
    public function testFilterByTags(array $expected, array $tags, string $strategy): void
    {
        $query = (new ObjectsLoader())->loadObjects([]);

        $result = Hash::extract($this->Tags->filterByTags($query, $tags, $strategy)->orderBy(['Objects.id'])->toArray(), '{n}.id');

        static::assertSame($expected, $result);
    }

    /** Data provider for {@see TagsComponentTest::testFilterExcludeByTags()}. */
    public static function filterExcludeByTagsProvider(): array
    {
        return [
            'inInt' => [[4], [1], 'in'],
            'inMixed' => [[4, 10], [1, 'second-tag'], 'in'],
            'existsNames' => [[4], ['first-tag'], 'exists'],
            'existsMixed' => [[4, 10], [1, 'second-tag'], 'exists'],
        ];
    }

    /** Test {@see TagsComponent::filterExcludeByTags()}. */
    #[DataProvider('filterExcludeByTagsProvider')]
    public function testFilterExcludeByTags(array $excluded, array $tags, string $strategy): void
    {
        $loader = new ObjectsLoader();
        $all = Hash::extract($loader->loadObjects([])->toArray(), '{n}.id');
        static::assertEmpty(array_diff($excluded, $all));

        $result = Hash::extract($this->Tags->filterExcludeByTags($loader->loadObjects([]), $tags, $strategy)->toArray(), '{n}.id');

        static::assertEqualsCanonicalizing(array_values(array_diff($all, $excluded)), $result);
    }

    /** Test {@see TagsComponent::filterByTags()} rejects an unknown strategy. */
    public function testUnknownStrategy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown strategy "nope"');

        $this->Tags->filterByTags((new ObjectsLoader())->loadObjects([]), [1], 'nope');
    }

    /** Test {@see TagsComponent::filterExcludeByTags()} rejects an unknown strategy. */
    public function testExcludeUnknownStrategy(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->Tags->filterExcludeByTags((new ObjectsLoader())->loadObjects([]), [1], 'nope');
    }
}

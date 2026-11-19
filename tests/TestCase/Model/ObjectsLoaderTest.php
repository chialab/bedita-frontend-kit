<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestCase\Model;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use Chialab\FrontendKit\Model\ObjectsLoader;
use PHPUnit\Framework\Attributes\CoversClass;

/** {@see \Chialab\FrontendKit\Model\ObjectsLoader} Test Case */
#[CoversClass(ObjectsLoader::class)]
class ObjectsLoaderTest extends TestCase
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
        'plugin.Chialab/FrontendKit.Tags',
        'plugin.Chialab/FrontendKit.ObjectTags',
        'plugin.BEdita/Core.Translations',
    ];

    protected ObjectsLoader $loader;

    public function setUp(): void
    {
        parent::setUp();

        $this->loader = new ObjectsLoader();
    }

    public function tearDown(): void
    {
        unset($this->loader);
        Configure::delete('I18n.lang');

        parent::tearDown();
    }

    /** Test {@see ObjectsLoader::loadObject()} resolving both unames and numeric IDs. */
    public function testLoadObject(): void
    {
        $byUname = $this->loader->loadObject('document-1', 'documents');
        $byId = $this->loader->loadObject('10', 'documents');

        static::assertSame(10, $byUname->id);
        static::assertSame('documents', $byUname->type);
        static::assertSame('document-1', $byId->uname);
    }

    /** Test {@see ObjectsLoader::loadFullObject()} without an explicit type. */
    public function testLoadFullObject(): void
    {
        $object = $this->loader->loadFullObject('document-1');
        $poster = $object->poster->toList();
        $authors = $object->has_author->toList();

        static::assertSame('documents', $object->type);
        static::assertSame(['image-1'], Hash::extract($poster, '{n}.uname'));
        static::assertSame('images', $poster[0]->type);
        static::assertNotNull($poster[0]->get('relation'));
        static::assertSame(['profile-1'], Hash::extract($authors, '{n}.uname'));
        static::assertSame('Alan', $authors[0]->name);
    }

    /** Test {@see ObjectsLoader::loadFullObject()} with an explicit `include` option. */
    public function testLoadFullObjectWithInclude(): void
    {
        $object = $this->loader->loadFullObject(10, 'documents', ['include' => 'poster']);

        static::assertSame(['image-1'], Hash::extract($object->poster->toList(), '{n}.uname'));
        static::assertFalse($object->has('has_author'));
    }

    /** Test {@see ObjectsLoader::loadObjects()} with an include limited per object. */
    public function testLoadObjectsWithLimitedInclude(): void
    {
        $folders = $this->loader->loadObjects(['id' => 8], 'folders', ['include' => 'children|1'], [])->toArray();

        static::assertCount(1, $folders);
        static::assertCount(1, $folders[0]->children);
    }

    /** Test {@see ObjectsLoader::loadObjects()} uses per-type default options. */
    public function testLoadObjectsDefaultOptions(): void
    {
        $this->loader->setObjectTypesConfig(['objects' => ['include' => 'poster']]);

        $documents = $this->loader->loadObjects(['id' => 10], 'documents')->toArray();

        static::assertSame(['image-1'], Hash::extract($documents[0]->poster, '{n}.uname'));
    }

    /** Test {@see ObjectsLoader::loadRelatedObjects()} copies join data into `relation`. */
    public function testLoadRelatedObjects(): void
    {
        $related = $this->loader->loadRelatedObjects('document-1', 'documents', 'poster')->toArray();

        static::assertSame(['image-1'], Hash::extract($related, '{n}.uname'));
        static::assertSame('images', $related[0]->type);
        static::assertNotNull($related[0]->get('relation'));
        static::assertFalse($related[0]->isDirty());
    }

    /** Test {@see ObjectsLoader::loadRelatedObjects()} with a lowercased association name. */
    public function testLoadRelatedObjectsLowercasedName(): void
    {
        $this->deprecated(function (): void {
            $related = $this->loader->loadRelatedObjects('10', 'documents', 'hasauthor')->toArray();

            static::assertSame(['profile-1'], Hash::extract($related, '{n}.uname'));
        });
    }

    /** Test {@see ObjectsLoader::hydrateObjects()} maps objects to their concrete types. */
    public function testHydrateObjects(): void
    {
        $objects = $this->loader->Objects->find()->where(['id IN' => [12, 10]])->orderByDesc('id')->toArray();

        $hydrated = $this->loader->hydrateObjects($objects)->toList();

        static::assertSame([12, 10], Hash::extract($hydrated, '{n}.id'));
        static::assertSame('Turing', $hydrated[0]->surname);
        static::assertSame('documents', $hydrated[1]->type);
    }

    /** Test auto-hydration of a configured association up to the given depth. */
    public function testAutoHydrateAssociations(): void
    {
        $this->loader->setAutoHydrateAssociations(['poster' => 2]);

        $object = $this->loader->loadObject('document-1', 'documents', ['include' => 'poster']);

        $poster = $object->poster->toList();
        static::assertSame('images', $poster[0]->type);
        static::assertNotNull($poster[0]->get('relation'));
        static::assertFalse($object->isDirty());
    }

    /** Test tag labels are translated to the requested language. */
    public function testTranslatedTagLabels(): void
    {
        $object = $this->loader->loadObject('parent-1', 'folders', ['include' => 'tags', 'lang' => 'it']);

        static::assertSame(['Primo tag'], Hash::extract($object->tags, '{n}.label'));
    }
}

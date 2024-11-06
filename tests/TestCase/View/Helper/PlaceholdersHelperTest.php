<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestCase\View\Helper;

use BEdita\Core\Model\Entity\Media;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Chialab\FrontendKit\View\Helper\PlaceholdersHelper;

/**
 * {@see \Chialab\FrontendKit\View\Helper\PlaceholdersHelper} Test Case
 *
 * @coversDefaultClass \Chialab\FrontendKit\View\Helper\PlaceholdersHelper
 */
class PlaceholdersHelperTest extends TestCase
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
    ];

    /**
     * Test subject
     *
     * @var \Chialab\FrontendKit\View\Helper\PlaceholdersHelper
     */
    public $Placeholders;

    /**
     * Test object
     *
     * @var \BEdita\Core\Model\Entity\ObjectEntity
     */
    public $object;

    /**
     * Test media
     *
     * @var \BEdita\Core\Model\Entity\Media
     */
    public $media1;

    /**
     * Test media
     *
     * @var \BEdita\Core\Model\Entity\Media
     */
    public $media2;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $view = new View();
        $this->Placeholders = new PlaceholdersHelper($view);

        $this->image1 = new Media([
            'id' => 2,
            'type' => 'images',
            'title' => 'image1',
            'relation' => [
                'params' => [
                    'body' => [[
                        'offset' => 15,
                        'length' => 25,
                    ]],
                ],
            ],
        ]);

        $this->image2 = new Media([
            'id' => 3,
            'type' => 'images',
            'title' => 'image2',
            'relation' => [
                'params' => [
                    'body' => [[
                        'offset' => 44,
                        'length' => 25,
                        'params' => '{"class":"test"}',
                    ]],
                ],
            ],
        ]);

        $this->object = new ObjectEntity([
            'id' => 1,
            'type' => 'objects',
            'body' => '<p>Hello World <!-- BE-PLACEHOLDER.2 --></p><!-- BE-PLACEHOLDER.3.eyJjbGFzcyI6InRlc3QifQ== -->',
            'placeholder' => [$this->image1, $this->image2],
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Placeholders, $this->object, $this->media1, $this->media2);

        parent::tearDown();
    }

    /**
     * Test {@see PlaceholdersHelper::getTemplatePaths()}.
     *
     * @return void
     * @covers ::getTemplatePaths()
     */
    public function testGetTemplatePaths()
    {
        $this->assertSame([
            'Placeholders/images',
            'Placeholders/media',
            'Placeholders/objects',
        ], $this->Placeholders->getTemplatePaths($this->object, 'body', $this->image1));
    }

    /**
     * Test {@see PlaceholdersHelper::getTemplate()}.
     *
     * @return void
     * @covers ::getTemplate()
     */
    public function testGetTemplate()
    {
        $this->assertSame('Placeholders/media', $this->Placeholders->getTemplate($this->object, 'body', $this->image1));
    }

    /**
     * Test {@see PlaceholdersHelper::defaultTemplater()}.
     *
     * @return void
     * @covers ::defaultTemplater()
     */
    public function testDefaultTemplater()
    {
        $contents = $this->Placeholders::defaultTemplater($this->object, 'body', [$this->image1, $this->image2], fn ($entity, $params) => sprintf('%s %s', $entity->title, $params ? $params : ''));
        $this->assertSame('<p>Hello World image1 </p>image2 {"class":"test"}', $contents);
    }

    /**
     * Test {@see PlaceholdersHelper::template()}.
     *
     * @return void
     * @covers ::template()
     */
    public function testTemplate()
    {
        $contents = $this->Placeholders->template($this->object, 'body');
        $this->assertSame('<p>Hello World image1</p>image2', $contents);
    }
}

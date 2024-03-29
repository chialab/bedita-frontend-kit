<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestCase\View\Helper;

use BEdita\Core\Model\Entity\Media;
use BEdita\Core\Model\Entity\ObjectEntity;
use BEdita\Core\Model\Entity\Stream;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Chialab\FrontendKit\View\Helper\PosterHelper;

/**
 * {@see \Chialab\FrontendKit\View\Helper\PosterHelper} Test Case
 *
 * @coversDefaultClass \Chialab\FrontendKit\View\Helper\PosterHelper
 */
class PosterHelperTest extends TestCase
{
    public $fixtures = [
        'plugin.Chialab/FrontendKit.ObjectTypes',
        'plugin.BEdita/Core.PropertyTypes',
        'plugin.Chialab/FrontendKit.Properties',
        'plugin.Chialab/FrontendKit.Relations',
        'plugin.Chialab/FrontendKit.RelationTypes',
    ];

    /**
     * Test subject
     *
     * @var \Chialab\FrontendKit\View\Helper\PosterHelper
     */
    public $Poster;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $view = new View();
        $this->Poster = new PosterHelper($view);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Poster);

        parent::tearDown();
    }

    protected function createObject(): ObjectEntity
    {
        return new ObjectEntity([
            'type' => 'documents',
        ]);
    }

    protected function createVideo(): Media
    {
        return new Media([
            'type' => 'videos',
        ]);
    }

    protected function createStream(int $width, int $height): Stream
    {
        return new Stream([
            'uri' => 'default://6aceb0eb-bd30-4f60-ac74-273083b921b6-bedita-logo-gray.gif',
            'file_name' => 'bedita-logo-gray.gif',
            'mime_type' => 'image/gif',
            'file_size' => 927,
            'width' => $width,
            'height' => $height,
        ]);
    }

    protected function createImage(int $width, int $height): Media
    {
        $image = new Media([
            'type' => 'images',
            'width' => $width,
            'height' => $height,
        ]);

        $image->set('streams', [$this->createStream($width, $height)]);

        return $image;
    }

    /**
     * Test {@see PosterHelper::orientation()}.
     *
     * @return void
     * @covers ::orientation()
     */
    public function testOrientationWithPosterArray()
    {
        $object = $this->createObject();
        $image = $this->createImage(150, 100);
        $object->set('poster', [$image]);

        $orientation = $this->Poster->orientation($object);
        $this->assertSame('landscape', $orientation);
    }

    /**
     * Test {@see PosterHelper::orientation()}.
     *
     * @return void
     * @covers ::orientation()
     */
    public function testOrientationWithPosterCollection()
    {
        $object = $this->createObject();
        $image = $this->createImage(150, 100);
        $object->set('poster', collection([$image]));

        $orientation = $this->Poster->orientation($object);
        $this->assertSame('landscape', $orientation);
    }

    /**
     * Test {@see PosterHelper::exists()}.
     *
     * @return void
     * @covers ::exists()
     */
    public function testExistsWithProviderThumbnail()
    {
        $object = $this->createVideo();
        $object->set('provider_thumbnail', 'https://www.bedita.com/favicon.png');

        $this->assertTrue($this->Poster->exists($object));
    }

    /**
     * Test {@see PosterHelper::url()}.
     *
     * @return void
     * @covers ::url()
     */
    public function testUrlWithProviderThumbnail()
    {
        $object = $this->createVideo();
        $object->set('provider_thumbnail', 'https://www.bedita.com/favicon.png');

        $this->assertSame('https://www.bedita.com/favicon.png', $this->Poster->url($object));
    }
}

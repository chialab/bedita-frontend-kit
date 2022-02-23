<?php
namespace Chialab\FrontendKit\Test\TestCase\View\Helper;

use BEdita\Core\Model\Entity\Media;
use BEdita\Core\Model\Entity\ObjectEntity;
use BEdita\Core\Model\Entity\Stream;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Chialab\FrontendKit\View\Helper\PosterHelper;

/**
 * Chialab\FrontendKit\View\Helper\PosterHelper Test Case
 */
class PosterHelperTest extends TestCase
{
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
    public function setUp()
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
    public function tearDown()
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

        $image->set('stream', $this->createStream($width, $height));

        return $image;
    }

    public function testOrientationWithPosterArray()
    {
        $object = $this->createObject();
        $image = $this->createImage(150, 100);
        $object->set('poster', [$image]);

        $orientation = $this->Poster->orientation($object);
        $this->assertSame('landscape', $orientation);
    }

    public function testOrientationWithPosterCollection()
    {
        $object = $this->createObject();
        $image = $this->createImage(150, 100);
        $object->set('poster', collection([$image]));

        $orientation = $this->Poster->orientation($object);
        $this->assertSame('landscape', $orientation);
    }
}

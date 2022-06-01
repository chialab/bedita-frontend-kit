<?php
namespace Chialab\FrontendKit\Test\TestCase\Twig;

use Cake\TestSuite\TestCase;
use Chialab\FrontendKit\Twig\FileExtension;

/**
 * {@see \Chialab\FrontendKit\Twig\FileExtension} Test Case
 *
 * @coversDefaultClass \Chialab\FrontendKit\Twig\FileExtension
 */
class FileExtensionTest extends TestCase
{
    /**
     * @var \Chialab\FrontendKit\Twig\FileExtension
     */
    protected $extension;

    /** @inheritDoc */
    public function setUp(): void
    {
        $this->extension = new FileExtension();
        parent::setUp();
    }

    /** @inheritDoc */
    public function tearDown(): void
    {
        unset($this->extension);
        parent::tearDown();
    }

    /**
     * Data provider for {@see FileExtensionTest::testFormatFileSize()} test case.
     *
     * @return array[]
     */
    public function formatFileSizeProvider(): array
    {
        return [
            'bytes' => ['100 Bytes', 100],
            'kilo' => ['2 KB', 2048],
            'mega' => ['1.5 MB', 1572864],
        ];
    }

    /**
     * Test {@see FileExtension::formatFileSize()}.
     *
     * @return void
     *
     * @dataProvider formatFileSizeProvider()
     * @covers ::formatFileSize()
     */
    public function testFormatFileSize(string $expected, int $size)
    {
        static::assertSame($expected, $this->extension->formatFileSize($size));
    }

    /**
     * Data provider for {@see FileExtensionTest::testMediaTypeCategory()} test case.
     *
     * @return array[]
     */
    public function mediaTypeCateoryProvider(): array
    {
        return [
            'word' => ['word', 'application/msword'],
            'archive' => ['archive', 'application/zip'],
            'image' => ['image', 'image/png'],
            'audio' => ['audio', 'audio/wav'],
        ];
    }

    /**
     * Test {@see FileExtension::mediaTypeCategory()}.
     *
     * @return void
     *
     * @dataProvider mediaTypeCateoryProvider()
     * @covers ::mediaTypeCategory()
     */
    public function testMediaTypeCategory(string $expected, string $mediaType)
    {
        static::assertSame($expected, $this->extension->mediaTypeCategory($mediaType));
    }
}

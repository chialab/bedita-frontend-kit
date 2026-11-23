<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestCase\Twig;

use Cake\TestSuite\TestCase;
use Chialab\FrontendKit\Twig\FileExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * {@see \Chialab\FrontendKit\Twig\FileExtension} Test Case
 */
#[CoversClass(FileExtension::class)]
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
    public static function formatFileSizeProvider(): array
    {
        return [
            'bytes' => ['100 Bytes', 100],
            'kilo' => ['2 KB', 2000],
            'mega' => ['1.5 MB', 1500000],
        ];
    }

    /**
     * Test {@see FileExtension::formatFileSize()}.
     *
     * @return void
     */
    #[DataProvider('formatFileSizeProvider')]
    public function testFormatFileSize(string $expected, int $size)
    {
        static::assertSame($expected, $this->extension->formatFileSize($size));
    }

    /**
     * Data provider for {@see FileExtensionTest::testMediaTypeCategory()} test case.
     *
     * @return array[]
     */
    public static function mediaTypeCateoryProvider(): array
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
     */
    #[DataProvider('mediaTypeCateoryProvider')]
    public function testMediaTypeCategory(string $expected, string $mediaType)
    {
        static::assertSame($expected, $this->extension->mediaTypeCategory($mediaType));
    }
}

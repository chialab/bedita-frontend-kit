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
     * Data provider for {@see FileExtensionTest::testReadableSize()} test case.
     *
     * @return array[]
     */
    public function readableSizeProvider(): array
    {
        return [
            'bytes' => ['100 Bytes', 100],
            'kilo' => ['2 KB', 2048],
            'mega' => ['1.5 MB', 1572864],
        ];
    }

    /**
     * Test {@see FileExtension::readableSize()}.
     *
     * @return void
     *
     * @dataProvider readableSizeProvider()
     * @covers ::readableSize()
     */
    public function testReadableSize(string $expected, int $size)
    {
        static::assertSame($expected, $this->extension->readableSize($size));
    }
}

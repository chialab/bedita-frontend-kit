<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestCase\View\Helper;

use BEdita\Core\Model\Entity\Folder;
use BEdita\Core\Model\Entity\ObjectEntity;
use Cake\TestSuite\TestCase;
use Cake\View\View;
use Chialab\FrontendKit\View\Helper\MetadataHelper;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MetadataHelper::class)]
class MetadataHelperTest extends TestCase
{
    protected MetadataHelper $Metadata;

    public function setUp(): void
    {
        parent::setUp();

        $this->Metadata = new MetadataHelper(new View());
    }

    // CakePHP 5 `has()` is true for null fields: null relations must fall back instead of failing.
    public function testCreatorAndPublisherWithNullRelations(): void
    {
        $object = new ObjectEntity(['has_author' => null, 'has_publisher' => null]);
        $publication = new Folder(['has_publisher' => [new ObjectEntity(['title' => '<b>Publisher</b>'])]]);

        $this->assertSame('Publisher', $this->Metadata->getCreator($object, $publication));
        $this->assertSame('Publisher', $this->Metadata->getPublisher($object, $publication));
        $this->assertNull($this->Metadata->getPublisher($object, new Folder(['has_publisher' => null])));
    }
}

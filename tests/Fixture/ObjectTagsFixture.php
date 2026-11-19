<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\Fixture;

use BEdita\Core\Test\Fixture\ObjectTagsFixture as BEObjectTagsFixture;

/** ObjectTags fixture. */
class ObjectTagsFixture extends BEObjectTagsFixture
{
    public array $records = [
        ['object_id' => 4, 'tag_id' => 1],
        ['object_id' => 10, 'tag_id' => 2],
    ];
}

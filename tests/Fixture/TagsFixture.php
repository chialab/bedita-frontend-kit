<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\Fixture;

use BEdita\Core\Test\Fixture\TagsFixture as BETagsFixture;

/** Tags fixture. */
class TagsFixture extends BETagsFixture
{
    public array $records = [
        // 1
        [
            'name' => 'first-tag',
            'labels' => ['default' => 'First tag', 'it' => 'Primo tag'],
            'enabled' => 1,
            'created' => '2022-01-01 00:00:00',
            'modified' => '2022-01-01 00:00:00',
        ],
        // 2
        [
            'name' => 'second-tag',
            'labels' => ['default' => 'Second tag'],
            'enabled' => 1,
            'created' => '2022-01-01 00:00:00',
            'modified' => '2022-01-01 00:00:00',
        ],
        // 3
        [
            'name' => 'disabled-tag',
            'labels' => ['default' => 'Disabled tag'],
            'enabled' => 0,
            'created' => '2022-01-01 00:00:00',
            'modified' => '2022-01-01 00:00:00',
        ],
    ];
}

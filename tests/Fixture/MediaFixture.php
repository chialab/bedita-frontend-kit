<?php
namespace Chialab\FrontendKit\Test\Fixture;

use BEdita\Core\Test\Fixture\MediaFixture as BEMediaFixture;

/**
 * Media test fixture.
 */
class MediaFixture extends BEMediaFixture
{
    public $records = [
        [
            'id' => 11,
            'name' => 'image-1',
            'width' => null,
            'height' => null,
            'duration' => null,
            'provider' => null,
            'provider_uid' => null,
            'provider_url' => null,
            'provider_thumbnail' => null,
        ],
    ];
}

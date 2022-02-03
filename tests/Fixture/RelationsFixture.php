<?php
namespace Chialab\FrontendKit\Test\Fixture;

use BEdita\Core\Test\Fixture\RelationsFixture as BERelationsFixture;

/**
 * Relations test fixture.
 */
class RelationsFixture extends BERelationsFixture
{
    public $records = [
        // 1
        [
            'name' => 'poster',
            'label' => 'Poster',
            'inverse_name' => 'poster_of',
            'inverse_label' => 'Poster of',
            'description' => '',
            'params' => null,
        ],
    ];
}
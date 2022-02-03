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
        // 2
        [
            'name' => 'has_author',
            'label' => 'Has author',
            'inverse_name' => 'author_of',
            'inverse_label' => 'Author of',
            'description' => '',
            'params' => null,
        ],
    ];
}
<?php
namespace Chialab\FrontendKit\Test\Fixture;

use BEdita\Core\Test\Fixture\RelationTypesFixture as BERelationTypesFixture;

/**
 * RelationTypes test fixture.
 */
class RelationTypesFixture extends BERelationTypesFixture
{
    public $records = [
        [
            'relation_id' => 1,
            'object_type_id' => 1,
            'side' => 'left',
        ],
        [
            'relation_id' => 1,
            'object_type_id' => 11,
            'side' => 'right',
        ],
        [
            'relation_id' => 2,
            'object_type_id' => 2,
            'side' => 'left',
        ],
        [
            'relation_id' => 2,
            'object_type_id' => 3,
            'side' => 'right',
        ],
    ];
}

<?php
namespace Chialab\FrontendKit\Test\Fixture;

use BEdita\Core\Test\Fixture\ObjectTypesFixture as BEObjectTypesFixture;

/**
 * ObjectTypes test fixture.
 */
class ObjectTypesFixture extends BEObjectTypesFixture
{
    public function init(): void
    {
        array_push(
            $this->records,
            // 11
            [
                'singular' => 'image',
                'name' => 'images',
                'is_abstract' => false,
                'parent_id' => 8,
                'tree_left' => 17,
                'tree_right' => 18,
                'description' => null,
                'plugin' => 'BEdita/Core',
                'model' => 'Media',
                'associations' => '["Streams"]',
                'created' => '2017-11-10 09:27:23',
                'modified' => '2017-11-10 09:27:23',
                'enabled' => true,
                'core_type' => true,
            ],
        );

        parent::init();
    }
}

<?php
namespace Chialab\FrontendKit\Test\Fixture;

use BEdita\Core\Test\Fixture\ObjectsFixture as BEObjectsFixture;

/**
 * Objects test fixture.
 */
class ObjectsFixture extends BEObjectsFixture
{
    public $records = [
        // 1
        [
            'uname' => 'bedita',
            'title' => 'BEdita',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'status' => 'on',
            'locked' => 1,
            'deleted' => 0,
            'created_by' => 1,
            'modified_by' => 1,
            'object_type_id' => 3,
            'created' => '2022-01-01 00:00:00',
            'modified' => '2022-01-01 00:00:00',
        ],
        // 2
        [
            'uname' => 'root-1',
            'title' => 'Root 1',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'status' => 'on',
            'locked' => 0,
            'deleted' => 0,
            'created_by' => 1,
            'modified_by' => 1,
            'object_type_id' => 10,
            'published' => null,
            'created' => '2022-01-01 00:00:00',
            'modified' => '2022-01-01 00:00:00',
        ],
        // 3
        [
            'uname' => 'root-2',
            'title' => 'Root 2',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'status' => 'on',
            'created_by' => 1,
            'modified_by' => 1,
            'locked' => 0,
            'deleted' => 0,
            'object_type_id' => 10,
            'published' => null,
            'created' => '2022-01-01 00:00:00',
            'modified' => '2022-01-01 00:00:00',
        ],
        // 4
        [
            'uname' => 'parent-1',
            'title' => 'Parent 1',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'status' => 'on',
            'locked' => 0,
            'deleted' => 0,
            'created_by' => 1,
            'modified_by' => 1,
            'object_type_id' => 10,
            'published' => null,
            'created' => '2022-01-01 00:00:00',
            'modified' => '2022-01-01 00:00:00',
        ],
        // 5
        [
            'uname' => 'parent-2',
            'title' => 'Parent 2',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'status' => 'on',
            'locked' => 0,
            'deleted' => 0,
            'created_by' => 1,
            'modified_by' => 1,
            'object_type_id' => 10,
            'published' => null,
            'created' => '2022-01-01 00:00:00',
            'modified' => '2022-01-01 00:00:00',
        ],
        // 6
        [
            'uname' => 'parent-3',
            'title' => 'Parent 3',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'status' => 'on',
            'locked' => 0,
            'deleted' => 0,
            'created_by' => 1,
            'modified_by' => 1,
            'object_type_id' => 10,
            'published' => null,
            'created' => '2022-01-01 00:00:00',
            'modified' => '2022-01-01 00:00:00',
        ],
        // 7
        [
            'uname' => 'parent-4',
            'title' => 'Parent 4',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'status' => 'on',
            'locked' => 0,
            'deleted' => 0,
            'created_by' => 1,
            'modified_by' => 1,
            'object_type_id' => 10,
            'published' => null,
            'created' => '2022-01-01 00:00:00',
            'modified' => '2022-01-01 00:00:00',
        ],
        // 8
        [
            'uname' => 'child-1',
            'title' => 'Child 1',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'status' => 'on',
            'locked' => 0,
            'deleted' => 0,
            'created_by' => 1,
            'modified_by' => 1,
            'object_type_id' => 10,
            'published' => null,
            'created' => '2022-01-01 00:00:00',
            'modified' => '2022-01-01 00:00:00',
        ],
        // 9
        [
            'uname' => 'child-2',
            'title' => 'Child 2',
            'description' => null,
            'body' => null,
            'extra' => null,
            'lang' => 'en',
            'status' => 'on',
            'locked' => 0,
            'deleted' => 0,
            'created_by' => 1,
            'modified_by' => 1,
            'object_type_id' => 10,
            'published' => null,
            'created' => '2022-01-01 00:00:00',
            'modified' => '2022-01-01 00:00:00',
        ],
        // 10
        [
            'uname' => 'document-1',
            'title' => 'Document 1',
            'description' => "<p>Hello there</p>",
            'body' => "<p>Hello world</p>",
            'extra' => null,
            'lang' => 'en',
            'status' => 'on',
            'locked' => 0,
            'deleted' => 0,
            'created_by' => 1,
            'modified_by' => 1,
            'object_type_id' => 2,
            'published' => null,
            'created' => '2022-01-01 00:00:00',
            'modified' => '2022-01-01 00:00:00',
        ],
        // 11
        [
            'uname' => 'image-1',
            'title' => 'Image 1',
            'description' => "",
            'body' => "",
            'extra' => null,
            'lang' => 'en',
            'status' => 'on',
            'locked' => 0,
            'deleted' => 0,
            'created_by' => 1,
            'modified_by' => 1,
            'object_type_id' => 11,
            'published' => null,
            'created' => '2022-01-01 00:00:00',
            'modified' => '2022-01-01 00:00:00',
        ],
        // 12
        [
            'uname' => 'profile-1',
            'title' => 'Profile 1',
            'description' => "",
            'body' => "",
            'extra' => null,
            'lang' => 'en',
            'status' => 'on',
            'locked' => 0,
            'deleted' => 0,
            'created_by' => 1,
            'modified_by' => 1,
            'object_type_id' => 3,
            'published' => null,
            'created' => '2022-01-01 00:00:00',
            'modified' => '2022-01-01 00:00:00',
        ],
    ];
}

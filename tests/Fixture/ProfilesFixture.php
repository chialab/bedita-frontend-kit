<?php
namespace Chialab\FrontendKit\Test\Fixture;

use BEdita\Core\Test\Fixture\ProfilesFixture as BEProfilesFixture;

/**
 * Profiles test fixture.
 */
class ProfilesFixture extends BEProfilesFixture
{
    public $records = [
        // 1
        [
            'id' => 12,
            'name' => 'Alan',
            'surname' => 'Turing',
            'email' => 'alan.turing@email.com',
            'person_title' => 'Mr.',
            'gender' => null,
            'birthdate' => '1912-06-23',
            'deathdate' => '1954-06-07',
        ],
    ];
}

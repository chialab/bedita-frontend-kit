<?php
namespace Chialab\FrontendKit\Test\Fixture;

use Authentication\PasswordHasher\LegacyPasswordHasher;
use BEdita\Core\Test\Fixture\UsersFixture as BEUsersFixture;

/**
 * Users test fixture.
 */
class UsersFixture extends BEUsersFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'username' => 'bedita',
                'password_hash' => (new LegacyPasswordHasher(['hashType' => 'md5']))->hash('password1'),
                'blocked' => 0,
                'last_login' => null,
                'last_login_err' => null,
                'num_login_err' => 1,
                'verified' => '2022-01-01 00:00:00',
                'password_modified' => '2022-01-01 00:00:00',
            ],
        ];

        parent::init();
    }
}

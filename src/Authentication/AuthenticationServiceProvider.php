<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Authentication;

use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Plugin for Chialab\FrontendKit
 */
class AuthenticationServiceProvider implements AuthenticationServiceProviderInterface
{
    /**
     * The auth service instance.
     *
     * @var \Authentication\AuthenticationServiceInterface
     */
    protected AuthenticationServiceInterface $authService;

    /**
     * Create auth service provider.
     *
     * @param string|null $loginUrl The url of the login page.
     * @return void
     */
    public function __construct(string|null $loginUrl = '/login')
    {
        $this->authService = new AuthenticationService();
        $this->authService->setConfig([
            'unauthenticatedRedirect' => $loginUrl,
            'queryParam' => 'redirect',
        ]);

        // Load identifiers
        $this->authService->loadIdentifier('Authentication.Password', [
            'fields' => [
                'username' => 'username',
                'password' => 'password_hash',
            ],
            'resolver' => [
                'className' => 'Authentication.Orm',
                'finder' => 'login',
            ],
        ]);

        // Load the authenticators, you want session first
        $this->authService->loadAuthenticator('Authentication.Session');
        $this->authService->loadAuthenticator('Authentication.Form', [
            'fields' => [
                'username' => 'username',
                'password' => 'password',
            ],
            'loginUrl' => $loginUrl,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        return $this->authService;
    }
}

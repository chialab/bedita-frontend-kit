<?php

namespace Chialab\FrontendKit\Authentication;

use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Plugin for Chialab\FrontendKit
 */
class AuthenticationServiceProvider implements AuthenticationServiceProviderInterface
{
    /**
     * The auth service instance.
     *
     * @var \Authentication\AuthenticationService
     */
    protected AuthenticationService $authService;

    /**
     * Create auth service provider.
     *
     * @param string|null $loginUrl The url of the login page.
     * @return void
     */
    public function __construct($loginUrl = '/login')
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
    public function getAuthenticationService(ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->authService;
    }
}

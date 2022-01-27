<?php

namespace Chialab\FrontendKit;

use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;

/**
 * Plugin for Chialab\FrontendKit
 */
class Plugin extends BasePlugin
{
    /**
     * @inheritDoc
     */
    public function routes($routes): void
    {
        $routes->plugin(
            'Chialab/FrontendKit',
            ['path' => '/'],
            function ($routes) {
                $routes->connect('/', ['controller' => 'Pages', 'action' => 'home'], ['_name' => 'pages:home']);

                $routes->connect(
                    '/objects/{uname}',
                    ['controller' => 'Pages', 'action' => 'object'],
                    ['_name' => 'pages:objects', 'pass' => ['uname'], 'routeClass' => ObjectRoute::class]
                );

                $routes->connect(
                    '/**',
                    ['controller' => 'Pages', 'action' => 'fallback'],
                    ['_name' => 'pages:fallback']
                );
            }
        );
    }
}

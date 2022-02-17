<?php

namespace Chialab\FrontendKit;

use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Chialab\FrontendKit\Authentication\AuthenticationServiceProvider;

/**
 * Plugin for Chialab\FrontendKit
 */
class Plugin extends BasePlugin
{
    /**
     * {@inheritDoc}
     */
    public function initialize()
    {
        parent::initialize();

        if (Configure::read('Status.level') === 'on') {
            // ensure the published filter
            Configure::write('Publish.checkDate', true);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function bootstrap(PluginApplicationInterface $app)
    {
        $app->addPlugin('Authentication');
    }

    /**
     * Check if a middleware is already in the middlewares queue.
     *
     * @param \Cake\Http\MiddlewareQueue $queue The middlewares queue.
     * @param string $class The middleware class name to check.
     * @return bool
     */
    protected static function isInMiddelwareQueue(MiddlewareQueue $queue, string $class): bool
    {
        $queue = clone $queue;
        $len = count($queue);
        for ($i = 0; $i < $len; $i++) {
            if ($queue->get($i) instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function middleware($middleware)
    {
        if (!static::isInMiddelwareQueue($middleware, AuthenticationMiddleware::class)) {
            // Add authentication middleware only on staging sites.
            return $middleware->add(new AuthenticationMiddleware(new AuthenticationServiceProvider('/login')));
        }

        return $middleware;
    }
}

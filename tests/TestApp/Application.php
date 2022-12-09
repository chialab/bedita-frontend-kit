<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestApp;

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;

/**
 * Application setup class.
 *
 * This defines the bootstrapping logic and middleware layers you
 * want to use in your application.
 */
class Application extends BaseApplication
{
    /**
     * {@inheritDoc}
     */
    public function bootstrap(): void
    {
        $this->addPlugin('BEdita/Core');
    }

    /**
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue The middleware queue to set in your App Class
     * @return \Cake\Http\MiddlewareQueue
     */
    public function middleware($middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue->add(new RoutingMiddleware($this));
    }
}

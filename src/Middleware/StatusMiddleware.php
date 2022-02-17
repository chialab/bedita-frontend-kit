<?php

namespace Chialab\FrontendKit\Middleware;

use Migrations\Migrations;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Status middleware, useful to create an endpoint for healthchecks.
 *
 * The endpoint will return a 500 error code if any migration has failed or is missing, or a 204 if everything is ok.
 */
class StatusMiddleware
{
    /**
     * List of plugins to check for migrations.
     *
     * @var array
     */
    protected array $plugins;

    /**
     * Path where the healthcheck will be performed.
     *
     * @var string
     */
    protected string $path;

    /**
     * Status middleware constructor.
     *
     * @param array $plugins List of plugins to check for migrations
     * @param string $path Path where the healthcheck will be performed
     */
    public function __construct(array $plugins, string $path = '/.well-known/status')
    {
        $this->plugins = $plugins;
        $this->path = $path;
    }

    /**
     * Invoke method.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        if ($request->getUri()->getPath() !== $this->path) {
            return $next($request, $response);
        }

        $migrations = new Migrations();
        if (!$this->checkMigrated($migrations->status())) {
            return $response->withStatus(500);
        }
        foreach ($this->plugins as $plugin) {
            if (!$this->checkMigrated($migrations->status(compact('plugin')))) {
                return $response->withStatus(500);
            }
        }

        return $response->withStatus(204);
    }

    /**
     * Check status of a list of migrations.
     *
     * @param array $migrations List of migrations to check
     * @return bool True if all migrations are successful, false if even one migration failed or is missing
     */
    protected function checkMigrated(array $migrations): bool
    {
        foreach ($migrations as $migration) {
            if ($migration['status'] !== 'up') {
                return false;
            }
        }

        return true;
    }
}

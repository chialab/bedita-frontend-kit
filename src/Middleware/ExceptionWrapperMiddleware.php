<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Middleware;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Exception wrapper middleware.
 *
 * Catch and rethrow mapped exceptions.
 */
class ExceptionWrapperMiddleware
{
    /**
     * Exceptions map.
     *
     * @var array<class-string, callable>
     */
    protected array $exceptionMap = [];

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        $this->exceptionMap = [
            RecordNotFoundException::class => fn (RecordNotFoundException $e): NotFoundException => new NotFoundException(__('Page not found'), null, $e),
        ];
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
        try {
            return $next($request, $response);
        } catch (Throwable $e) {
            $ctr = get_class($e);
            if (!isset($this->exceptionMap[$ctr])) {
                throw $e;
            }

            $cb = $this->exceptionMap[$ctr];

            throw $cb($e);
        }
    }
}

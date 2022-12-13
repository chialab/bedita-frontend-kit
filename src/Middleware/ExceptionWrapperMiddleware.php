<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Middleware;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * Exception wrapper middleware.
 *
 * Catch and rethrow mapped exceptions.
 */
class ExceptionWrapperMiddleware implements MiddlewareInterface
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
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler($request);
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

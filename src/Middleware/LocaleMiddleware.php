<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Middleware;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Locale middleware
 */
class LocaleMiddleware
{
    /**
     * Invoke method.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        if (!$request instanceof ServerRequest) {
            return $next($request, $response);
        }

        $locale = $request->getParam('locale');
        if (!empty($locale)) {
            I18n::setLocale($locale);
            Configure::write('I18n.lang', $locale);
        }

        return $next($request, $response);
    }
}

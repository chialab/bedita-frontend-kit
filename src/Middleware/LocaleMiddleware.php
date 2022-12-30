<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Middleware;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Locale middleware
 */
class LocaleMiddleware implements MiddlewareInterface
{
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$request instanceof ServerRequest) {
            return $handler->handle($request);
        }

        $locale = $request->getParam('locale');
        if (!empty($locale)) {
            I18n::setLocale($locale);
            Configure::write('I18n.lang', $locale);
        }

        return $handler->handle($request);
    }
}

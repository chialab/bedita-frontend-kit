<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Middleware;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\I18n\I18n;
use Cake\Log\Log;
use Cake\Utility\Hash;
use Locale;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ResourceBundle;

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
            $availableLocales = ResourceBundle::getLocales('');
            $parsedLocale = Hash::get(Locale::parseLocale($locale), 'language');
            if (!in_array($parsedLocale, $availableLocales)) {
                Log::debug(sprintf('Requested locale "%s" (%s) is not available', $parsedLocale, $locale));
            } else {
                I18n::setLocale($parsedLocale);
                Configure::write('I18n.lang', $parsedLocale);
            }
        }

        return $handler->handle($request);
    }
}

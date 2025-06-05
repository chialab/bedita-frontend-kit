<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestCase\Middleware;

use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Chialab\FrontendKit\Middleware\LocaleMiddleware;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Test {@see \Chialab\FrontendKit\Middleware\LocaleMiddleware}.
 *
 * @covers \Chialab\FrontendKit\Middleware\LocaleMiddleware
 */
class LocaleMiddlewareTest extends TestCase
{
    /**
     * @return array
     */
    public function localeProvider(): array
    {
        return [
            'invalid language code (ISO 639-1)' => [null, 'xk'],
            'invalid language code (ISO 639-2)' => [null, 'xkc'],
            'invalid language code (ISO 639-1 + ISO 3166-1 alpha-2)' => [null, 'xk_CD'],
            'correct language code (ISO 639-1)' => ['en', 'en'],
            'correct language code (ISO 639-2)' => ['en', 'eng'],
            'correct language code (ISO 639-1 + ISO 3166-1 alpha-2)' => ['en', 'en_GB'],
        ];
    }

    /**
     * Test {@see \Chialab\FrontendKit\Middleware\LocaleMiddleware::process()}.
     *
     * @param string|null $expectedLocale Expected configured locale.
     * @param string $locale Requested locale.
     * @return void
     * @dataProvider localeProvider())
     */
    public function testLocale(string|null $expectedLocale, string $locale): void
    {
        $middleware = new LocaleMiddleware();
        $request = new ServerRequest(['params' => compact('locale')]);
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new EmptyResponse();
            }
        };
        $middleware->process($request, $handler);
        $actualLocale = Configure::read('I18n.lang');

        static::assertEquals($expectedLocale, $actualLocale);
    }
}

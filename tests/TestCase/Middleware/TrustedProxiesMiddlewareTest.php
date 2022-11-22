<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Test\TestCase\Middleware;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use Chialab\FrontendKit\Middleware\TrustedProxiesMiddleware;
use Laminas\Diactoros\ServerRequest as DiactorosServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test {@see \Chialab\FrontendKit\Middleware\TrustedProxiesMiddleware}.
 *
 * @covers \Chialab\FrontendKit\Middleware\TrustedProxiesMiddleware
 */
class TrustedProxiesMiddlewareTest extends TestCase
{
    /**
     * Data provider for {@see TrustedProxiesMiddlewareTest::testInvoke()} test case.
     *
     * @return array{string|null, string[]|null, \Chialab\FrontendKit\Middleware\TrustedProxiesMiddleware, \Psr\Http\Message\ServerRequestInterface}[]
     */
    public function invokeProvider(): array
    {
        $requestFactory = fn (string $remoteAddr, string $xForwardedFor): ServerRequest => new ServerRequest([
            'environment' => [
                'REMOTE_ADDR' => $remoteAddr,
                'HTTP_X_FORWARDED_FOR' => $xForwardedFor,
            ],
        ]);

        return [
            'not a Cake request' => [null, null, new TrustedProxiesMiddleware('127.0.0.1'), new DiactorosServerRequest()],
            'no trusted proxies' => ['127.0.0.1', [], new TrustedProxiesMiddleware(), $requestFactory('127.0.0.1', '10.0.1.1, 192.168.1.1')],
            'trusted proxies' => ['192.168.1.1', ['127.0.0.1'], new TrustedProxiesMiddleware('127.0.0.1'), $requestFactory('127.0.0.1', '10.0.1.1, 192.168.1.1')],
            'multiple trusted proxies' => ['10.0.1.1', ['127.0.0.1', '192.168.1.1'], new TrustedProxiesMiddleware('127.0.0.1', '192.168.0.0/16'), $requestFactory('127.0.0.1', '10.0.1.1, 192.168.1.1')],
            'invalid address' => ['not-an-ip-address', ['127.0.0.1'], new TrustedProxiesMiddleware('127.0.0.1', '192.168.0.0/16'), $requestFactory('127.0.0.1', '10.0.1.1, 192.168.1.1, not-an-ip-address')],
            'all trusted' => ['192.168.1.1', ['127.0.0.1', '192.168.1.1'], new TrustedProxiesMiddleware('127.0.0.1', '192.168.0.0/16'), $requestFactory('127.0.0.1', '192.168.1.1')],
        ];
    }

    /**
     * Test {@see TrustedProxiesMiddleware}.
     *
     * @param string|null $expectedClientIp Expected client IP after middleware execution.
     * @param string|null $expectedTrustedProxies Expected trusted proxies list after middleware execution.
     * @param \Chialab\FrontendKit\Middleware\TrustedProxiesMiddleware $middleware Middleware instance.
     * @param \Psr\Http\Message\ServerRequestInterface $request Incoming request.
     * @return void
     * @dataProvider invokeProvider()
     */
    public function testInvoke(?string $expectedClientIp, ?array $expectedTrustedProxies, TrustedProxiesMiddleware $middleware, ServerRequestInterface $request): void
    {
        $response = new Response();
        $invoked = 0;
        $next = function (ServerRequestInterface $req, ResponseInterface $res) use ($response, $expectedClientIp, $expectedTrustedProxies, &$invoked): ResponseInterface {
            $invoked++;
            static::assertSame($response, $res, 'It should not manipulate response object');
            if ($expectedClientIp !== null) {
                static::assertInstanceOf(ServerRequest::class, $req);
            }
            if ($req instanceof ServerRequest) {
                static::assertSame($expectedClientIp, $req->clientIp());
                static::assertEquals($expectedTrustedProxies, $req->getTrustedProxies());
            }

            return $res;
        };

        $res = $middleware($request, $response, $next);
        static::assertSame($response, $res);
        static::assertSame(1, $invoked);
    }
}

<?php
declare(strict_types=1);

namespace Chialab\FrontendKit\Middleware;

use Cake\Http\ServerRequest;
use Chialab\Ip\Address;
use Chialab\Ip\Subnet;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Traversable;

/**
 * Middleware to set trusted proxies on the incoming request, thus reliably reading actual client IP.
 */
class TrustedProxiesMiddleware
{
    /**
     * List of trusted proxies.
     *
     * @var string[]
     */
    protected array $trustedProxies = [];

    /**
     * Compiled list of subnets.
     *
     * @var \Chialab\Ip\Subnet[]|null
     */
    protected ?array $subnets;

    /**
     * Middleware constructor.
     *
     * @param string ...$trustedProxies List of trusted proxies, expressed as IP addresses or subnets (in CIDR block notation).
     * @codeCoverageIgnore
     */
    public function __construct(string ...$trustedProxies)
    {
        $this->trustedProxies = $trustedProxies;
    }

    /**
     * Iterate through trusted proxies, compiled as {@see \Chialab\Ip\Subnet} objects.
     *
     * @return \Chialab\Ip\Subnet[]
     */
    protected function compile(): array
    {
        if (isset($this->subnets)) {
            return $this->subnets;
        }

        $subnets = [];
        foreach ($this->trustedProxies as $trustedProxy) {
            if (strpos($trustedProxy, '/') !== false) {
                // If the string contains a forward slash, we assume it's in CIDR block notation.
                $subnets[] = Subnet::parse($trustedProxy);

                continue;
            }

            // Otherwise, we assume it is a single IP address, and we identify an address with the subnet consisting of that address alone.
            $address = Address::parse($trustedProxy);
            $subnets[] = new Subnet($address, $address->getProtocolVersion()->getBitsLength());
        }

        return $this->subnets = $subnets;
    }

    /**
     * Check if an address is trusted.
     *
     * @param string $remoteAddress Remote address.
     * @return bool
     */
    protected function isTrusted(string $remoteAddress): bool
    {
        try {
            $address = Address::parse($remoteAddress);
        } catch (\InvalidArgumentException $e) {
            return false;
        }

        foreach ($this->compile() as $subnet) {
            if ($subnet->contains($address)) {
                return true;
            }
        }

        return false;
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
        if (!$request instanceof ServerRequest || empty($this->trustedProxies)) {
            return $next($request, $response);
        }

        while (true) {
            $remoteAddress = $request->clientIp();
            $trustedProxies = $request->getTrustedProxies();
            if (!$this->isTrusted($remoteAddress) || in_array($remoteAddress, $trustedProxies)) {
                // Remote address does not belong to any trusted proxies,
                // or there are no more addresses in the `X-Forwarded-For` stack.
                break;
            }

            // The remote address belongs to a trusted proxy subnet.
            // Add it to the trusted proxies addresses and repeat.
            $request->trustProxy = true;
            $request->setTrustedProxies(array_merge($trustedProxies, [$remoteAddress]));
        }

        return $next($request, $response);
    }
}

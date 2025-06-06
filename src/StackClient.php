<?php

namespace WeDesignIt\Common;

use WeDesignIt\Common\Api\Middleware\MiddlewareInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class StackClient implements ClientInterface
{
    private ClientInterface $client;
    /**
     * @var MiddlewareInterface[]
     */
    private array $middleware;

    /**
     * @param ClientInterface $client A PSR-18 client implementation (e.g. Guzzle)
     * @param MiddlewareInterface[] $middleware Array of middlewares (in processing order)
     */
    public function __construct(ClientInterface $client, array $middleware = [])
    {
        $this->client = $client;
        $this->middleware = $middleware;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $middleware = $this->middleware;
        $client = $this->client;

        $next = function (RequestInterface $request) use ($client) {
            return $client->sendRequest($request);
        };

        // Build the middleware chain (visualize as onion layers; last in, first out))
        while ($layer = array_pop($middleware)) {
            $next = function (RequestInterface $request) use ($layer, $next) {
                return $layer->process($request, $next);
            };
        }

        return $next($request);
    }
}

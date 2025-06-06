<?php

namespace WeDesignIt\Common\Http;

use WeDesignIt\Common\Http\Middleware\MiddlewareInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use WeDesignIt\Common\Http\Support\DefaultHttpFactory;

class StackClient implements ClientInterface
{
    private ClientInterface $client;
    /**
     * @var MiddlewareInterface[]
     */
    private array $middleware;

    /**
     * @param MiddlewareInterface[] $middleware Array of middlewares (in processing order)
     * @param ClientInterface|null $client A PSR-18 client implementation (e.g. Guzzle). If null, a default client will be created.
     */
    public function __construct(array $middleware = [], ?ClientInterface $client = null)
    {
        $this->client = $client ?? DefaultHttpFactory::makeClient();
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

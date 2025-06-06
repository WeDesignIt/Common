<?php

namespace WeDesignIt\Common\Tests\Api\Middleware;

use PHPUnit\Framework\TestCase;
use WeDesignIt\Common\Api\Middleware\RequestThrottlingMiddleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

class RequestThrottlingMiddlewareTest extends TestCase
{
    public function test_allows_requests_under_limit()
    {
        $psr6cache = new ArrayAdapter();
        $cache = new Psr16Cache($psr6cache);

        $middleware = new RequestThrottlingMiddleware($cache, 2, 1); // max 2 per seconde

        $next = function ($request) {
            return new Response(200, [], 'ok');
        };

        $request = new Request('GET', 'https://api.example.com/test');

        // Eerste twee mogen door
        $response1 = $middleware->process($request, $next);
        $this->assertEquals(200, $response1->getStatusCode());

        $response2 = $middleware->process($request, $next);
        $this->assertEquals(200, $response2->getStatusCode());
    }

    public function test_blocks_when_limit_exceeded()
    {
        $psr6cache = new ArrayAdapter();
        $cache = new Psr16Cache($psr6cache);

        $middleware = new RequestThrottlingMiddleware($cache, 1, 2); // max 1 per 2 sec

        $next = function ($request) {
            return new Response(200, [], 'ok');
        };

        $request = new Request('GET', 'https://api.example.com/test');

        // Eerste mag door
        $middleware->process($request, $next);

        // Tweede binnen window: blok (429)
        $response2 = $middleware->process($request, $next);

        $this->assertEquals(429, $response2->getStatusCode());
    }
}

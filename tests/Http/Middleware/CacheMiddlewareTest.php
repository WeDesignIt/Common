<?php

namespace WeDesignIt\Common\Tests\Http\Middleware;

use PHPUnit\Framework\TestCase;
use WeDesignIt\Common\Http\Middleware\CacheMiddleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

class CacheMiddlewareTest extends TestCase
{
    public function test_caches_get_responses() : void
    {
        $psr6cache = new ArrayAdapter();
        $cache = new Psr16Cache($psr6cache);

        $middleware = new CacheMiddleware($cache, 60); // 60 sec TTL

        $callCount = 0;
        $next = function ($request) use (&$callCount) {
            $callCount++;
            return new Response(200, [], 'from_api');
        };

        $request = new Request('GET', 'https://api.example.com/test');

        // Eerste call: niet gecachet
        $response1 = $middleware->process($request, $next);
        $this->assertEquals('from_api', (string)$response1->getBody());
        $this->assertEquals(1, $callCount);

        // Tweede call: uit cache (next wordt niet meer aangeroepen)
        $response2 = $middleware->process($request, $next);
        $this->assertEquals('from_api', (string)$response2->getBody());
        $this->assertEquals(1, $callCount);
    }

    public function test_does_not_cache_post_responses() : void
    {
        $psr6cache = new ArrayAdapter();
        $cache = new Psr16Cache($psr6cache);

        $middleware = new CacheMiddleware($cache, 60);
        $callCount = 0;

        $next = function ($request) use (&$callCount) {
            $callCount++;
            return new Response(201, [], 'created');
        };

        $request = new Request('POST', 'https://api.example.com/test');
        $middleware->process($request, $next);
        $middleware->process($request, $next);

        $this->assertEquals(2, $callCount);
    }
}

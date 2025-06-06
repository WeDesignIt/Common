<?php

namespace WeDesignIt\Common\Tests\Api\Middleware;

use PHPUnit\Framework\TestCase;
use WeDesignIt\Common\Api\Middleware\RetryMiddleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;

class RetryMiddlewareTest extends TestCase
{
    public function test_retries_on_configured_status() : void
    {
        $attempts = 0;
        $middleware = new RetryMiddleware(3, 10, [503]);
        $request = new Request('GET', 'http://test.local');

        $next = function (RequestInterface $req) use (&$attempts) {
            $attempts++;
            return new Response(503); // Return 503 to trigger retry
        };

        $response = $middleware->process($request, $next);
        $this->assertEquals(503, $response->getStatusCode());
        $this->assertEquals(3, $attempts, 'Should have retried 3 times');
    }

    public function test_does_not_retry_on_success() : void
    {
        $middleware = new RetryMiddleware(3, 10, [503]);
        $request = new Request('GET', 'http://test.local');

        $next = fn($req) => new Response(200);

        $response = $middleware->process($request, $next);
        $this->assertEquals(200, $response->getStatusCode());
    }
}

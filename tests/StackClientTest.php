<?php

namespace WeDesignIt\Common\Tests;

use PHPUnit\Framework\TestCase;
use WeDesignIt\Common\StackClient;
use WeDesignIt\Common\Api\Middleware\MiddlewareInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Client\ClientInterface;

class StackClientTest extends TestCase
{
    public function test_executes_all_middlewares_in_order()
    {
        $callOrder = [];

        // Middleware 1
        $middleware1 = new class ($callOrder) implements MiddlewareInterface {
            private $callOrder;
            public function __construct(&$callOrder)
            {
                $this->callOrder = &$callOrder;
            }
            public function process(RequestInterface $request, callable $next): Response
            {
                $this->callOrder[] = 'mw1';
                return $next($request);
            }
        };

        // Middleware 2
        $middleware2 = new class ($callOrder) implements MiddlewareInterface {
            private $callOrder;
            public function __construct(&$callOrder)
            {
                $this->callOrder = &$callOrder;
            }
            public function process(RequestInterface $request, callable $next): Response
            {
                $this->callOrder[] = 'mw2';
                return $next($request);
            }
        };

        // Dummy HTTP client
        $client = new class implements ClientInterface {
            public function sendRequest(RequestInterface $request): Response
            {
                return new Response(200, [], 'done');
            }
        };

        $stackClient = new StackClient($client, [$middleware1, $middleware2]);
        $request = new Request('GET', 'http://test.local');
        $response = $stackClient->sendRequest($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['mw1', 'mw2'], $callOrder);
    }
}

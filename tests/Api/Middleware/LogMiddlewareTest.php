<?php

namespace WeDesignIt\Common\Tests\Api\Middleware;

use PHPUnit\Framework\TestCase;
use WeDesignIt\Common\Api\Middleware\LogMiddleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

class LogMiddlewareTest extends TestCase
{
    public function test_logs_request_and_response()
    {
        // Make dummy logger that collects messages in array
        $messages = [];
        $logger = new class ($messages) implements LoggerInterface {
            private $messages;

            public function __construct(&$messages)
            {
                $this->messages = &$messages;
            }

            public function emergency($message, array $context = []): void
            {
                $this->messages[] = $message;
            }

            public function alert($message, array $context = []): void
            {
                $this->messages[] = $message;
            }

            public function critical($message, array $context = []): void
            {
                $this->messages[] = $message;
            }

            public function error($message, array $context = []): void
            {
                $this->messages[] = $message;
            }

            public function warning($message, array $context = []): void
            {
                $this->messages[] = $message;
            }

            public function notice($message, array $context = []): void
            {
                $this->messages[] = $message;
            }

            public function info($message, array $context = []): void
            {
                $this->messages[] = $message;
            }

            public function debug($message, array $context = []): void
            {
                $this->messages[] = $message;
            }

            public function log($level, $message, array $context = []): void
            {
                $this->messages[] = $message;
            }
        };

        $middleware = new LogMiddleware($logger);

        $next = function ($request) {
            return new Response(200, [], 'ok');
        };

        $request = new Request('GET', 'https://example.com/test');
        $response = $middleware->process($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($messages, 'Logger should have received messages');
        // Controleer dat er minimaal één "Request" of "Response" gelogd is
        $found = false;
        foreach ($messages as $msg) {
            if (strpos($msg, 'Request') !== false || strpos($msg, 'Response') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Logger should contain request or response log');
    }
}

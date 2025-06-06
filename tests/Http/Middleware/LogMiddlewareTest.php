<?php

namespace WeDesignIt\Common\Tests\Http\Middleware;

use PHPUnit\Framework\TestCase;
use WeDesignIt\Common\Http\Middleware\LogMiddleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface;

class LogMiddlewareTest extends TestCase
{
    public function test_logs_request_and_response(): void
    {

        /**
         * Dummy logger that collects messages in array
         * @var array<int, string> $messages
         */
        $messages = [];

        $logger = new class implements LoggerInterface {
            /** @var array<int, string> */
            public array $messages = [];

            public function emergency(string|\Stringable $message, array $context = []): void
            {
                $this->messages[] = $message;
            }

            public function alert(string|\Stringable $message, array $context = []): void
            {
                $this->messages[] = $message;
            }

            public function critical(string|\Stringable $message, array $context = []): void
            {
                $this->messages[] = $message;
            }

            public function error(string|\Stringable $message, array $context = []): void
            {
                $this->messages[] = $message;
            }

            public function warning(string|\Stringable $message, array $context = []): void
            {
                $this->messages[] = $message;
            }

            public function notice(string|\Stringable $message, array $context = []): void
            {
                $this->messages[] = $message;
            }

            public function info(string|\Stringable $message, array $context = []): void
            {
                $this->messages[] = $message;
            }

            public function debug(string|\Stringable $message, array $context = []): void
            {
                $this->messages[] = $message;
            }

            public function log($level, string|\Stringable $message, array $context = []): void
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
        $this->assertNotEmpty($logger->messages, 'Logger should have received messages');
        $found = false;
        foreach ($logger->messages as $msg) {
            if (strpos($msg, 'Request') !== false || strpos($msg, 'Response') !== false) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Logger should contain request or response log');
    }
}

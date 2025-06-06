<?php

namespace WeDesignIt\Common\Http\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class LogMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $this->logger->debug('API Request', [
            'method' => $request->getMethod(),
            'uri' => (string)$request->getUri(),
            'headers' => $request->getHeaders(),
        ]);

        try {
            $response = $next($request);

            $this->logger->debug('API Response', [
                'status' => $response->getStatusCode(),
                'body' => (string)$response->getBody(),
            ]);

            return $response;
        } catch (\Throwable $e) {
            $this->logger->error('API Request failed', [
                'exception' => $e,
                'method' => $request->getMethod(),
                'uri' => (string)$request->getUri(),
            ]);
            throw $e;
        }
    }
}

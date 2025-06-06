<?php

namespace WeDesignIt\Common\Api\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class RetryMiddleware implements MiddlewareInterface
{
    protected int $maxAttempts;
    protected int $baseDelayMs;
    /**
     * @var array<int>
     */
    protected array $retryOnStatus;
    /**
     * @var array<string>
     */
    protected array $retryOnException;

    /**
     * @param int $maxAttempts
     * @param int $baseDelayMs
     * @param array<int> $retryOnStatus (e.g. [429, 500, 502, 503, 504])
     * @param array<string> $retryOnException (classnames as string, e.g. [\GuzzleHttp\Exception\ServerException::class])
     */
    public function __construct(int $maxAttempts = 3, int $baseDelayMs = 200, array $retryOnStatus = [429, 500, 502, 503, 504], array $retryOnException = [])
    {
        $this->maxAttempts = $maxAttempts;
        $this->baseDelayMs = $baseDelayMs;
        $this->retryOnStatus = $retryOnStatus;
        $this->retryOnException = $retryOnException;
    }

    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $lastException = null;
        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            try {
                $response = $next($request);
                if (in_array($response->getStatusCode(), $this->retryOnStatus) && $attempt < $this->maxAttempts) {
                    $this->sleep($attempt);
                    continue;
                }
                return $response;
            } catch (\Throwable $e) {
                $shouldRetry = false;
                // If Exception has response: inspect status code
                $response = $this->extractResponseFromException($e);
                if ($response instanceof ResponseInterface) {
                    if (in_array($response->getStatusCode(), $this->retryOnStatus) && $attempt < $this->maxAttempts) {
                        $shouldRetry = true;
                    }
                }
                // Or: retry on specific exceptions
                foreach ($this->retryOnException as $retryException) {
                    if ($e instanceof $retryException && $attempt < $this->maxAttempts) {
                        $shouldRetry = true;
                        break;
                    }
                }
                if ($shouldRetry) {
                    $lastException = $e;
                    $this->sleep($attempt);
                    continue;
                }
                throw $e;
            }
        }
        if ($lastException) {
            throw $lastException;
        }
        throw new \RuntimeException('RetryMiddleware failed without exception');
    }

    protected function sleep(int $attempt): void
    {
        // Exponential backoff in ms
        usleep((int)($this->baseDelayMs * (2 ** ($attempt - 1)) * 1000));
    }

    /**
     * Gets response from Exception (if present). Compatible with Guzzle, HTTPlug/PSR, etc.
     * @param \Throwable $e
     * @return ResponseInterface|null
     */
    protected function extractResponseFromException(\Throwable $e): ?ResponseInterface
    {
        // For Guzzle 7+
        if (method_exists($e, 'getResponse')) {
            $resp = $e->getResponse();
            if ($resp instanceof ResponseInterface) {
                return $resp;
            }
        }
        // Mayhaps add other libraries here in the future, like HTTPlug or PSR-18 compatible clients
        // ...
        return null;
    }
}

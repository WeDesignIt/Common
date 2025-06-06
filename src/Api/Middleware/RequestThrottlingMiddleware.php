<?php

namespace WeDesignIt\Common\Api\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use WeDesignIt\Common\Api\Response\ResponseSerializer;

class RequestThrottlingMiddleware implements MiddlewareInterface
{
    private CacheInterface $cache;
    private int $maxRequests;
    private int $windowSeconds;
    /**
     * @var callable|null
     */
    private $keyGenerator;
    private $responseFactory;

    /**
     * @param CacheInterface $cache
     * @param int $maxRequests
     * @param int $windowSeconds
     * @param callable|null $keyGenerator function(RequestInterface):string
     * @param callable|null $responseFactory function(int $status, array $headers, string $body):ResponseInterface
     */
    public function __construct(CacheInterface $cache, int $maxRequests, int $windowSeconds, mixed $keyGenerator = null, mixed $responseFactory = null)
    {
        $this->cache = $cache;
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;

        if ($keyGenerator !== null && !is_callable($keyGenerator)) {
            throw new \InvalidArgumentException('keyGenerator must be callable or null');
        }
        $this->keyGenerator = $keyGenerator;

        // If no responseFactory was given, take the default ResponseSerializer's factory
        if ($responseFactory !== null && !is_callable($responseFactory)) {
            throw new \InvalidArgumentException('responseFactory must be callable or null');
        }
        if ($responseFactory) {
            $this->responseFactory = $responseFactory;
        } else {
            $this->responseFactory = ResponseSerializer::detectResponseFactory();
        }
    }

    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $key = $this->keyGenerator
            ? call_user_func($this->keyGenerator, $request)
            : 'global';
        $cacheKey = 'throttle|' . sha1($key);

        $count = $this->increment($cacheKey, $this->windowSeconds);

        if ($count > $this->maxRequests) {
            $headers = [
                'Content-Type' => 'application/json',
                'Retry-After' => $this->windowSeconds,
            ];
            $body = json_encode(['message' => 'Rate limit exceeded']);
            return call_user_func($this->responseFactory, 429, $headers, $body);
        }

        return $next($request);
    }

    private function increment(string $cacheKey, int $ttl): int
    {
        if (method_exists($this->cache, 'increment')) {
            $new = $this->cache->increment($cacheKey);
            if ($new === 1) {
                $this->cache->set($cacheKey, 1, $ttl);
            }
            return $new;
        }

        $count = (int)$this->cache->get($cacheKey, 0);
        $count++;
        $this->cache->set($cacheKey, $count, $ttl);
        return $count;
    }
}

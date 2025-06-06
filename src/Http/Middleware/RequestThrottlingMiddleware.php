<?php

namespace WeDesignIt\Common\Http\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use WeDesignIt\Common\Http\Response\ResponseSerializer;

class RequestThrottlingMiddleware implements MiddlewareInterface
{
    private CacheInterface $cache;
    private int $maxRequests;
    private int $windowSeconds;
    /**
     * @var callable|null
     */
    private $keyGenerator;

    /**
     * @var callable
     */
    private $responseFactory;

    /**
     * @param CacheInterface $cache
     * @param int $maxRequests
     * @param int $windowSeconds
     * @param callable|null $keyGenerator function(RequestInterface):string
     * @param callable|null $responseFactory function(int $status, array $headers, string $body):ResponseInterface
     */
    public function __construct(CacheInterface $cache, int $maxRequests, int $windowSeconds, ?callable $keyGenerator = null, ?callable $responseFactory = null)
    {
        $this->cache = $cache;
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;
        $this->keyGenerator = $keyGenerator;
        $this->responseFactory = $responseFactory ?? ResponseSerializer::detectResponseFactory();
    }

    /**
     * @inheritDoc
     */
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
            return ($this->responseFactory)(429, $headers, $body);
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

        $raw = $this->cache->get($cacheKey, 0);
        $count = is_numeric($raw) ? (int)$raw : 0;

        $count++;
        $this->cache->set($cacheKey, $count, $ttl);
        return $count;
    }
}

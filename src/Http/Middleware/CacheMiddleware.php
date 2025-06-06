<?php

namespace WeDesignIt\Common\Http\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use WeDesignIt\Common\Http\Response\ResponseSerializer;

class CacheMiddleware implements MiddlewareInterface
{
    private CacheInterface $cache;
    private int $ttl;
    /**
     * @var array<string>
     */
    private array $bustOnMethods;
    /**
     * @var array<string|callable>
     */
    private array $bustPatterns;
    /**
     * @var callable|null
     */
    private $shouldCache;
    private ResponseSerializer $serializer;

    /**
     * @param CacheInterface $cache
     * @param int $ttl
     * @param array<string> $bustOnMethods
     * @param array<string|callable> $bustPatterns
     * @param callable|null $shouldCache function(RequestInterface):bool
     * @param ResponseSerializer|null $serializer
     */
    public function __construct(CacheInterface $cache, int $ttl = 300, array $bustOnMethods = ['POST', 'PUT', 'PATCH', 'DELETE'], array $bustPatterns = [], mixed $shouldCache = null, ?ResponseSerializer $serializer = null)
    {
        $this->cache = $cache;
        $this->ttl = $ttl;
        $this->bustOnMethods = $bustOnMethods;
        $this->bustPatterns = $bustPatterns;
        $this->shouldCache = $shouldCache;
        $this->serializer = $serializer ?? new ResponseSerializer();
    }

    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $method = strtoupper($request->getMethod());
        $cacheKey = $this->makeCacheKey($request);

        // 1. Cache busting for chaging requests
        if (in_array($method, $this->bustOnMethods, true) && $this->shouldBustCache($request)) {
            $this->cache->delete($cacheKey);
            return $next($request);
        }

        // 2. For now: only cache GET requests
        if ($method !== 'GET') {
            return $next($request);
        }

        // 3. Custom shouldCache callable
        if ($this->shouldCache && !call_user_func($this->shouldCache, $request)) {
            return $next($request);
        }

        // 4. Cache hit?
        if ($this->cache->has($cacheKey)) {
            $cached = $this->cache->get($cacheKey);
            if (is_array($cached) && isset($cached['status'])) {
                return $this->serializer->deserialize($cached);
            }
        }

        // 5. Fetch, serialize & cache if possible
        $response = $next($request);

        if ($response->getStatusCode() === 200) {
            $this->cache->set($cacheKey, $this->serializer->serialize($response), $this->ttl);
        }

        return $response;
    }

    private function makeCacheKey(RequestInterface $request): string
    {
        return 'api|' . sha1($request->getMethod() . '|' . (string)$request->getUri());
    }

    private function shouldBustCache(RequestInterface $request): bool
    {
        $uri = (string)$request->getUri();
        foreach ($this->bustPatterns as $pattern) {
            if ((is_string($pattern) && preg_match($pattern, $uri))
                || (is_callable($pattern) && call_user_func($pattern, $request))
            ) {
                return true;
            }
        }
        return empty($this->bustPatterns);
    }
}

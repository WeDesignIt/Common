<?php

namespace WeDesignIt\Common\Http\Traits;

use Psr\SimpleCache\CacheInterface;

trait UsesTokenCaching
{
    protected ?CacheInterface $tokenCache = null;

    abstract protected function getTokenCacheKey(): string;

    public function setTokenCache(CacheInterface $cache): static
    {
        $this->tokenCache = $cache;
        return $this;
    }

    protected function cacheToken(array $tokenData, int $expiresIn): void
    {
        $cacheKey = $this->getTokenCacheKey();
        if ($this->tokenCache) {
            $tokenData['expires_at'] = time() + $expiresIn;
            $this->tokenCache->set($cacheKey, $tokenData, $expiresIn);
        }
    }

    protected function getCachedToken(): ?array
    {
        $cacheKey = $this->getTokenCacheKey();
        if ($this->tokenCache) {
            $tokenData = $this->tokenCache->get($cacheKey);
            if (is_array($tokenData) && isset($tokenData['access_token'], $tokenData['expires_at']) && $tokenData['expires_at'] > time() + 60) {
                return $tokenData;
            }
        }
        return null;
    }

    public function invalidateTokenCache(): void
    {
        $cacheKey = $this->getTokenCacheKey();
        if ($this->tokenCache) {
            $this->tokenCache->delete($cacheKey);
        }
    }
}

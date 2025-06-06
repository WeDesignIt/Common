<?php

namespace WeDesignit\Common\Api\Traits;

trait UsesIdpToken
{
    protected ?string $idpToken = null;
    protected ?int $idpTokenExpiresAt = null;

    /**
     * Fetches a new token from the Identity Provider (IdP). Required to be implemented by the class using this trait.
     */
    abstract protected function fetchTokenFromIdp(): array;
    // E.g. ['access_token' => '...', 'expires_in' => 3600]

    protected function getToken(): ?string
    {
        if (method_exists($this, 'getCachedToken') && method_exists($this, 'cacheToken')) {
            $tokenData = $this->getCachedToken();
            if ($tokenData) {
                return $tokenData['access_token'];
            }
            $tokenData = $this->fetchTokenFromIdp();
            $this->cacheToken($tokenData, $tokenData['expires_in']);
            return $tokenData['access_token'];
        }

        // In-memory fallback (only for single request lifetime)
        if (isset($this->idpToken, $this->idpTokenExpiresAt) && $this->idpTokenExpiresAt > time() + 60) {
            return $this->idpToken;
        }
        $tokenData = $this->fetchTokenFromIdp();
        $this->idpToken = $tokenData['access_token'];
        $this->idpTokenExpiresAt = time() + $tokenData['expires_in'];
        return $this->idpToken;
    }

    protected function prepareHeaders(array $headers): array
    {
        $token = $this->getToken();
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        return $headers;
    }
}

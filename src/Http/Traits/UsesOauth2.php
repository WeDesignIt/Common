<?php

namespace WeDesignIt\Common\Http\Traits;

trait UsesOauth2
{
    protected ?string $oauthToken = null;
    protected ?int $oauthTokenExpiresAt = null;

    abstract protected function fetchOauth2Token(array $params = []): array;
    // Almost always: ['access_token' => ..., 'expires_in' => ..., 'refresh_token' => ...]

    /**
     * Refreshes the OAuth2 token using the provided refresh token. Required to be implemented by the class using this trait.
     */
    abstract protected function refreshToken(string $refreshToken): array;
    // Almost always: return ['access_token' => ..., 'expires_in' => ..., 'refresh_token' => ...]

    protected function getToken(): ?string
    {
        if (method_exists($this, 'getCachedToken') && method_exists($this, 'cacheToken')) {
            $tokenData = $this->getCachedToken();
            if ($tokenData) {
                // Is it still valid?
                if ($tokenData['expires_at'] > time() + 60) {
                    return $tokenData['access_token'];
                }
                // Expired, but we have a refresh token
                if (!empty($tokenData['refresh_token'])) {
                    $refreshed = $this->refreshToken($tokenData['refresh_token']);
                    $this->cacheToken($refreshed, $refreshed['expires_in']);
                    return $refreshed['access_token'];
                }
            }
            // No cached token or expired, fetch a new one
            $tokenData = $this->fetchOauth2Token();
            $this->cacheToken($tokenData, $tokenData['expires_in']);
            return $tokenData['access_token'];
        }

        // Fallback without caching
        if (isset($this->oauthToken, $this->oauthTokenExpiresAt) && $this->oauthTokenExpiresAt > time() + 60) {
            return $this->oauthToken;
        }
        $tokenData = $this->fetchOauth2Token();
        $this->oauthToken = $tokenData['access_token'];
        $this->oauthTokenExpiresAt = time() + $tokenData['expires_in'];
        return $this->oauthToken;
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

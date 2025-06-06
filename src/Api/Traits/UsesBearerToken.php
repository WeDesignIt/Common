<?php

namespace WeDesignIt\Common\Api\Traits;

trait UsesBearerToken
{
    protected string $bearerToken = '';

    protected function getToken(): ?string
    {
        return $this->bearerToken;
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    protected function prepareHeaders(array $headers): array
    {
        $token = $this->getToken();
        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        return $headers;
    }
}

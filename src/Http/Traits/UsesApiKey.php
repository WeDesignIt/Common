<?php

namespace WeDesignIt\Common\Http\Traits;

trait UsesApiKey
{
    protected string $apiKey = '';
    protected string $apiKeyPlacement = 'header'; // of 'query'
    protected string $apiKeyName = 'X-Api-Key';

    protected function prepareHeaders(array $headers): array
    {
        if ($this->apiKeyPlacement === 'header') {
            $headers[$this->apiKeyName] = $this->apiKey;
        }
        return $headers;
    }

    protected function prepareQuery(array $query): array
    {
        if ($this->apiKeyPlacement === 'query') {
            $query[$this->apiKeyName] = $this->apiKey;
        }
        return $query;
    }
}

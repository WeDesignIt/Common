<?php

namespace WeDesignIt\Common\Api;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use WeDesignIt\Common\Api\Support\DefaultHttpFactory;

abstract class ApiClient
{
    protected ClientInterface $httpClient;
    protected RequestFactoryInterface $requestFactory;
    protected StreamFactoryInterface $streamFactory;
    protected string $baseUri;

    public function __construct(string $baseUri, ?ClientInterface $httpClient = null, ?RequestFactoryInterface $requestFactory = null, ?StreamFactoryInterface $streamFactory = null)
    {
        $this->baseUri = $baseUri;
        $this->httpClient = $httpClient ?? DefaultHttpFactory::makeClient();
        $this->requestFactory = $requestFactory ?? DefaultHttpFactory::makeRequestFactory();
        $this->streamFactory = $streamFactory ?? DefaultHttpFactory::makeStreamFactory();
    }

    /**
     * Makes a request to the API.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string $uri The endpoint URI (without base URI)
     * @param array $query Query parameters
     * @param array $body Request body (for POST/PUT/PATCH)
     * @param array $headers Additional headers
     * @param bool $retry Whether to retry on 401 Unauthorized
     * @return array Decoded JSON response
     * @throws \RuntimeException|\Psr\Http\Client\ClientExceptionInterface If the request fails or returns an error status code
     */
    protected function request(string $method, string $uri, array $query = [], array $body = [], array $headers = [], bool $retry = false): array
    {
        if (method_exists($this, 'prepareQuery')) {
            $query = $this->prepareQuery($query);
        }

        $uri = $this->baseUri . $uri . (!empty($query) ? '?' . http_build_query($query) : '');

        // Let subclasses and/or traits build headers by default (like the Authorization header)
        $headers = $this->prepareHeaders($headers);

        $request = $this->requestFactory->createRequest($method, $uri);
        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }
        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody(
                    $this->streamFactory->createStream(json_encode($body))
                );
        }

        $response = $this->httpClient->sendRequest($request);

        // Automatic retry on 401 Unauthorized
        if ($response->getStatusCode() === 401 && !$retry && method_exists($this, 'invalidateTokenCache')) {
            $this->invalidateTokenCache();
            // Retry one time with fresh token
            return $this->request($method, $uri, $query, $body, $headers, true);
        }

        // Simple error handling
        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException('API Error: ' . $response->getStatusCode());
        }
        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Can be added or overridden by subclasses to prepare headers.
     */
    protected function prepareHeaders(array $headers): array
    {
        return $headers;
    }
}

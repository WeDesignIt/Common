# Examples

## Building ApiClients

Example code for building an ApiClient with bearer token authentication:
```php
namespace App\Api;

use WeDesignIt\Common\Api\ApiClient;
use WeDesignIt\Common\Api\Traits\UsesBearerToken;

class ExampleApiClient extends ApiClient
{
    use UsesBearerToken;

    public function __construct(string $baseUri, string $bearerToken)
    {
        parent::__construct($baseUri);
        $this->setBearerToken($bearerToken);
    }

    /**
     * Simple example call to get the current user.
     */
    public function getCurrentUser(): array
    {
        return $this->request('GET', '/me');
    }
}
```

Example code for building an ApiClient using an identity provider (idp). Note that the `UsesTokenCaching` trait is used to 
cache the token for subsequent requests, and the `UsesIdpToken` trait handles the IDP-specific token fetching logic.
The `UsesTokenCaching` trait is optional but recommended to avoid unnecessary token requests.

```php
namespace App\Api;

use App\Api\ApiClient;
use App\Api\Traits\UsesIdpToken;
use App\Api\Traits\UsesTokenCaching;
use Psr\SimpleCache\CacheInterface;

class IdpApiClient extends ApiClient
{
    use UsesIdpToken, UsesTokenCaching;

    protected string $clientId;
    protected string $clientSecret;

    public function __construct(
        string $baseUri,
        string $clientId,
        string $clientSecret,
        ?CacheInterface $tokenCache = null,
        ?ClientInterface $httpClient = null
    ) {
        parent::__construct($baseUri, $httpClient);
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        if ($tokenCache) {
            $this->setTokenCache($tokenCache);
        }
    }

    /**
     * Generates unique cache key for the IDP token.
     */
    protected function getTokenCacheKey(): string
    {
        return 'api:idp_token:' . sha1($this->baseUri . ':' . $this->clientId);
    }

    protected function fetchTokenFromIdp(): array
    {
        // note things like grant_type, client_id, client_secret, scope, etc. are specific to the IDP you are using.
        $response = $this->httpClient->sendRequest(
            $this->requestFactory->createRequest('POST', 'https://idp.example.com/oauth/token')
                ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody(
                    $this->streamFactory->createStream(http_build_query([
                        'grant_type'    => 'client_credentials',
                        'client_id'     => $this->clientId,
                        'client_secret' => $this->clientSecret,
                        'scope'         => 'api.read', // of de benodigde scopes
                    ]))
                )
        );

        $data = json_decode((string) $response->getBody(), true);

        if (!isset($data['access_token'], $data['expires_in'])) {
            throw new \RuntimeException('Ongeldige IDP token response');
        }

        // If a refresh token is provided, include it in the response
        return [
            'access_token'  => $data['access_token'],
            'expires_in'    => $data['expires_in'],
            'refresh_token' => $data['refresh_token'] ?? null,
        ];
    }

    /**
     * Simple API call to get a "protected resource".
     */
    public function getProtectedResource(): array
    {
        return $this->request('GET', '/resource');
    }
}
```

Stacking multiple middlewares in an API client. In this case the above `IdpApiClient` can be used with the `StackClient` to add additional middlewares like logging or caching.:

```php
// Get dependencies from the service container:
$logger = app(LoggerInterface::class);
$cache = Cache::store('redis');

// Stack middlewares to be used with the API client
$middleware = [
    new LogMiddleware($logger),                          // Log requests and responses
    new CacheMiddleware($cache, ttl: 300),               // Cache GET responses for 5 minutes
    new RequestThrottlingMiddleware(                     // Throttle the client to 100 requests per minute, per API client
        $cache, 100, 60
    ),
    new RetryMiddleware(                                 // Retry on 429, 500, 503 (max 3 times with exponential backoff)
        maxAttempts: 3,
        baseDelayMs: 300,
        retryOnStatus: [429, 500, 502, 503, 504],
        retryOnException: [\RuntimeException::class]
    ),
];

// Use the StackClient and stuff in the middlewares
$httpClient = new StackClient($middleware);

// And let the API client use the stack client
$api = new IdpApiClient(
    'https://api.example.com',
    'your-client-id',
    'your-client-secret',
    $cache,            // Token cache
    $httpClient        // PSR-18 client met middleware stack
);

// Now use the API client to make requests
$data = $api->getProtectedResource();
```
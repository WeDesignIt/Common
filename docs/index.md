# Client
The Stack client is a PSR-18 compliant HTTP client that can be used to make requests to APIs. You can insert any
PSR-18 compliant client, such as Guzzle, into the Stack client. The Stack client allows you to add middlewares to the
request and response cycle.
Examples of PSR-18 compliant clients are:
- Guzzle
- Laravel HTTP Client
- Symfony HTTP Client
- HTTPlug
- Zend HTTP Client

## Subsections
- [Middlewares](middlewares.md)
- [Traits](traits.md)
- [Examples](examples.md)

## Note on middlewares
Please note that the order of middlewares is important. The first middleware in the stack will be executed first, 
and the last middleware will be executed last. So an example correct order of middlewares would be:

`Logging > Caching > Throttling > Retry`

such that all attempts are logged, including retries, and retries only happen after throttle/cache checks.

## Client usage

example usage for Laravel with Guzzle:
```php
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;

use WeDesignIt\Common\Api\Middleware\StackClient;
use WeDesignIt\Common\Api\Middleware\RetryMiddleware;
use WeDesignIt\Common\Api\Middleware\LoggingMiddleware;

$httpClient = new GuzzleClient();
$factory = new HttpFactory();

$middleware = [
    new RetryMiddleware(3, 250),
    new LoggingMiddleware($app['log']),
    // Add more middlewares as needed
];

$stack = new StackClient($httpClient, $middleware);
```

## Registering to the service container

You can register the Stack client to the service container in your `AppServiceProvider` or any other service provider:

```php 
use App\YourFurtherNamespace\ApiClient;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use WeDesignIt\Common\Api\Middleware\StackClient;
use WeDesignIt\Common\Api\Middleware\RetryMiddleware;
use WeDesignIt\Common\Api\Middleware\LoggingMiddleware;

public function register()
{
    $this->app->singleton(ApiClient::class, function ($app) {
        $httpClient = new GuzzleClient();
        $factory = new HttpFactory();

        $middleware = [
            new RetryMiddleware(3, 250),
            new LoggingMiddleware($app['log']),
            // More middlewares can be added here
        ];

        $stack = new StackClient($httpClient, $middleware);

        return new ApiClient(
            $stack,
            $factory,
            $factory,
            config('services.yourapi.base_uri'),
            config('services.yourapi.api_key')
        );
    });
}
```
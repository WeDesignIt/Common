# Middlewares
Guzzle has a powerful middleware system that allows you to modify requests and responses.
However, that middleware system is not PSR-15 compliant, so it cannot be used with the PSR-15 middleware stack.
Therefore, we provide a set of middlewares that can be used with the Stack client which you can define in almost a
similar way as you would with Guzzle.

The following middlewares are provided by the `wedesignit/common` package:

## CacheMiddleware
Is used to cache the response of a request.

example usage:
```php
use WeDesignIt\Common\Api\Middleware\ResponseSerializer;
use WeDesignIt\Common\Api\Middleware\CacheMiddleware;

$serializer = new ResponseSerializer();

$middleware[] = new CacheMiddleware(
    Cache::store('redis'), // PSR-16 cache to use, Laravel's cache is PSR-16 compliant
    600, // cache duration in seconds
    ['POST', 'PUT'], // methods on which to bust the present cache
    ['#^/products#'], // optional regex patterns to match against the request URI to bust the cache
    function($request) { // optional custom cache logic. In this case: only cache requests without an Authorization header
        return !$request->hasHeader('Authorization');
    },
    $serializer, // optional response serializer, if not provided the default response serializer will be used
);
```

## LogMiddleware
Used to log requests and responses.

example usage:
```php
use WeDesignIt\Common\Api\Middleware\LoggingMiddleware;

$middleware[] = new LoggingMiddleware($app['log']);
```


## RequestThrottlingMiddleware
Is used to limit the number of requests from a client in a given time frame.

example usage:
```php
use WeDesignIt\Common\Api\Middleware\ResponseSerializer;
use WeDesignIt\Common\Api\Middleware\RequestThrottlingMiddleware;

// examples of key generators
$keyGenerator = null; // leaving null will use a global throttling key api-wide
$keyGenerator = fn($req) => $req->ip(); // use the client's IP address as the key
$keyGenerator = fn($req) => $req->getMethod() . ':' . (string)$req->getUri(); // uri based key
$keyGenerator = fn($req) => $req->getHeaderLine('Authorization') ?: 'anon'; // "user-based" key, using the Authorization header or 'anon' if not present

// leaving null will use the default response factory
$responseFactory = null;
// or
$serializer = new ResponseSerializer();
$responseFactory = [$serializer, 'responseFactory'];

$middleware[] = new RequestThrottlingMiddleware(
    Cache::store('redis'), // cache store to use
    100, // max requests per "window"
    60, // window in seconds
    $keyGenerator,
    $responseFactory
);
```

## RetryMiddleware
Is used to retry requests that fail due to transient errors.

example usage:
```php
$middleware[] = new RetryMiddleware(
    $maxAttempts = 3,  // maximum number of retries
    $baseDelayMs = 250,  // base delay in milliseconds for exponential backoff between retries
    $retryOnStatus = [429, 500, 502, 503, 504] // HTTP status codes to retry on
    $retryOnException = [\GuzzleHttp\Exception\ServerException::class] // Exception classes to retry on (actually status code retries will be used earlier)
);
```

## Writing your own middleware

You can write your own middleware by implementing the `WeDesignIt\Common\Api\Middleware\MiddlewareInterface` interface.
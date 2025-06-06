<?php

namespace WeDesignIt\Common\Api\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class DefaultHttpFactory
{
    public static function makeClient(): ClientInterface
    {
        if (class_exists('\GuzzleHttp\Client')) {
            return new \GuzzleHttp\Client();
        }
        if (class_exists('\Illuminate\Http\Client\Factory')) {
            // Laravel HTTP client via PSR-18 bridge package (optional, https://github.com/laravel/laravel-psr18)
            return new \Laravel\Psr18\HttpClientAdapter(app('http'));
        }
        throw new \RuntimeException('No suitable HTTP client found');
    }

    public static function makeRequestFactory(): RequestFactoryInterface
    {
        if (class_exists('\GuzzleHttp\Psr7\HttpFactory')) {
            return new \GuzzleHttp\Psr7\HttpFactory();
        }
        if (class_exists('\Nyholm\Psr7\Factory\Psr17Factory')) {
            return new \Nyholm\Psr7\Factory\Psr17Factory();
        }
        throw new \RuntimeException('No PSR-17 RequestFactory found');
    }

    public static function makeStreamFactory(): StreamFactoryInterface
    {
        if (class_exists('\GuzzleHttp\Psr7\HttpFactory')) {
            return new \GuzzleHttp\Psr7\HttpFactory();
        }
        if (class_exists('\Nyholm\Psr7\Factory\Psr17Factory')) {
            return new \Nyholm\Psr7\Factory\Psr17Factory();
        }
        throw new \RuntimeException('No PSR-17 StreamFactory found');
    }
}

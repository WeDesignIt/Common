# Common

Some common code which can be used by other packages.
For now it is limited to a PSR-18 compliant HTTP client with a middleware stack, and some traits for common API client functionality.

Used standards:
- PSR-7: request-object (universal request-object)
- PSR-15: middleware stack (universal middleware stack)
- PSR-16: cache interface (universal cache interface)
- PSR-17: request-factory (universal request-object)
- PSR-18: client interface as typehint (not hardcoding Guzzle)x

For more detailed docs, see the [docs folder](docs/index.md).

## Installing
Via Composer

``` bash
composer require wedesignit/common
```

## Testing

``` bash
composer test
```

## Credits

- [Patrick van Kouteren][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](license.md) for more information.

## About WeDesignIt

[WeDesignIt][link-wdi] is a web agency from Oude-Tonge (reserve-Zeeland), the Netherlands specialized in custom web applications, API development, and integrations.


[link-author]: https://github.com/pvankouteren
[link-contributors]: ../../contributors
[link-wdi]: https://www.wedesignit.nl
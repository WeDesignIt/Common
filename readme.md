# Common
[![Latest Version on Packagist][icon-version]][link-packagist]
[![Software License][icon-license]](license.md)
[![Build Status][icon-github-actions]][link-github-actions]
[![Total Downloads][icon-downloads]][link-downloads]

Some common code which can be used by other packages.
For now it is limited to a PSR-18 compliant HTTP client with a middleware stack, and some traits for common API client functionality.

Used standards:
- PSR-7: request-object (universal request-object)
- PSR-15: middleware stack (universal middleware stack)
- PSR-16: cache interface (universal cache interface)
- PSR-17: request-factory (universal request-object)
- PSR-18: client interface as typehint (not hardcoding Guzzle)

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

[icon-version]: https://img.shields.io/packagist/v/WeDesignIt/Common?style=flat-square
[icon-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[icon-github-actions]: https://img.shields.io/github/actions/workflow/status/wedesignit/common/ci.yml?label=tests&branch=master&style=flat-square
[icon-downloads]: https://img.shields.io/packagist/dt/wedesignit/common?style=flat-square

[link-packagist]: https://packagist.org/packages/wedesignit/common
[link-github-actions]: https://github.com/wedesignit/common/actions/workflows/ci.yml
[link-downloads]: https://packagist.org/packages/wedesignit/common

[link-author]: https://github.com/pvankouteren
[link-contributors]: ../../contributors
[link-wdi]: https://www.wedesignit.nl
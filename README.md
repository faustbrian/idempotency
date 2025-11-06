[![GitHub Workflow Status][ico-tests]][link-tests]
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

------

# Idempotency

Generate reproducible idempotency keys from any data format (JSON, XML, YAML, arrays, objects) regardless of key order.

## Requirements

> **Requires [PHP 8.4+](https://php.net/releases/)**

## Installation

```bash
composer require cline/idempotency
```

## Quick Start

```php
use Cline\Idempotency\IdempotencyKey;

// Generate a key from any data
$key = IdempotencyKey::create(['name' => 'John', 'age' => 30]);
echo $key->toString(); // "e4a7f8b3c2d1a9f6..."

// Key order doesn't matter - same data produces same key
$key1 = IdempotencyKey::create(['name' => 'John', 'age' => 30]);
$key2 = IdempotencyKey::create(['age' => 30, 'name' => 'John']);
$key1->equals($key2); // true
```

## Documentation

- **[Basic Usage](cookbook/basic-usage.md)** - Getting started with idempotency keys
- **[Supported Formats](cookbook/supported-formats.md)** - JSON, XML, YAML, arrays, objects, and scalars
- **[Hash Algorithms](cookbook/hash-algorithms.md)** - SHA-256, SHA-512, SHA-1, and MD5
- **[Output Formats](cookbook/output-formats.md)** - Hex, binary, Base64, Base62, UUID, and versioned strings
- **[Custom Normalizers](cookbook/custom-normalizers.md)** - Preprocessing data with custom normalizers
- **[Advanced Examples](cookbook/advanced-examples.md)** - Real-world usage patterns and techniques

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please use the [GitHub security reporting form][link-security] rather than the issue queue.

## Credits

- [Brian Faust][link-maintainer]
- [All Contributors][link-contributors]

## License

The MIT License. Please see [License File](LICENSE.md) for more information.

[ico-tests]: https://github.com/faustbrian/idempotency/actions/workflows/quality-assurance.yaml/badge.svg
[ico-version]: https://img.shields.io/packagist/v/cline/idempotency.svg
[ico-license]: https://img.shields.io/badge/License-MIT-green.svg
[ico-downloads]: https://img.shields.io/packagist/dt/cline/idempotency.svg

[link-tests]: https://github.com/faustbrian/idempotency/actions
[link-packagist]: https://packagist.org/packages/cline/idempotency
[link-downloads]: https://packagist.org/packages/cline/idempotency
[link-security]: https://github.com/faustbrian/idempotency/security
[link-maintainer]: https://github.com/faustbrian
[link-contributors]: ../../contributors

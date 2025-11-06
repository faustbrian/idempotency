# Basic Usage

The `IdempotencyKey` class generates consistent cryptographic hashes from any data structure, automatically normalizing inputs so that identical data produces identical keys regardless of key order.

## Quick Start

The simplest way to use the library is with the static `generate()` method:

```php
use Cline\Idempotency\IdempotencyKey;

$key = IdempotencyKey::create(['name' => 'John', 'age' => 30]);
echo $key->toString(); // "e4a7f8b3c2d1a9f6e5b4c3d2a1f9e8b7..."
```

## Key Order Independence

The most important feature is that key order doesn't matter. Same data always produces the same key:

```php
$key1 = IdempotencyKey::create(['name' => 'John', 'age' => 30]);
$key2 = IdempotencyKey::create(['age' => 30, 'name' => 'John']);

$key1->equals($key2); // true
```

This works with deeply nested structures:

```php
$data1 = [
    'user' => ['name' => 'John', 'age' => 30],
    'location' => ['city' => 'NYC', 'country' => 'USA'],
];

$data2 = [
    'location' => ['country' => 'USA', 'city' => 'NYC'],
    'user' => ['age' => 30, 'name' => 'John'],
];

IdempotencyKey::create($data1)->equals(
    IdempotencyKey::create($data2)
); // true
```

## Working with Keys

The `IdempotencyKey` object provides several methods:

```php
$key = IdempotencyKey::create(['foo' => 'bar']);

// Get the hash string
echo $key->toString();
echo (string) $key;  // Same as toString()

// Compare keys
$other = IdempotencyKey::create(['foo' => 'bar']);
$key->equals($other); // true
$key->equals('a1b2c3...'); // Compare with raw hash string

// Check if data matches
$key->matches(['foo' => 'bar']); // true
$key->matches(['bar' => 'foo']); // false

// Get metadata
$key->getAlgorithm(); // HashAlgorithm::SHA256
$key->getVersion(); // 1
```

## Default Hash Algorithm

By default, `generate()` uses SHA-256, which produces 64-character hexadecimal strings:

```php
$key = IdempotencyKey::create('test');
strlen($key->toString()); // 64
```

See [Hash Algorithms](hash-algorithms.md) for other options.

## String Casting

The class implements `Stringable`, so you can use it anywhere a string is expected:

```php
$key = IdempotencyKey::create(['user_id' => 123]);

// These are equivalent
echo $key->toString();
echo (string) $key;
echo $key;

// Use in string operations
$header = "Idempotency-Key: {$key}";
```

## Validation

You can validate hash strings:

```php
use Cline\Idempotency\HashAlgorithm;

$validSha256 = 'a1b2c3d4e5f6...'; // 64 hex chars
IdempotencyKey::isValid($validSha256); // true
IdempotencyKey::isValid($validSha256, HashAlgorithm::SHA256); // true

$invalid = 'not-a-hash';
IdempotencyKey::isValid($invalid); // false
```

## Truncation

For shorter identifiers (with increased collision risk):

```php
$key = IdempotencyKey::create(['id' => 123]);
$short = $key->truncate(16); // First 16 characters
```

**Warning**: Shorter lengths significantly increase collision probability. Use full-length keys for production.

## Table of Contents

1. [Basic Usage](#doc-docs-basic-usage) (`docs/basic-usage.md`)
2. [Supported Formats](#doc-docs-supported-formats) (`docs/supported-formats.md`)
3. [Hash Algorithms](#doc-docs-hash-algorithms) (`docs/hash-algorithms.md`)
4. [Output Formats](#doc-docs-output-formats) (`docs/output-formats.md`)
5. [Custom Normalizers](#doc-docs-custom-normalizers) (`docs/custom-normalizers.md`)
6. [Advanced Examples](#doc-docs-advanced-examples) (`docs/advanced-examples.md`)
<a id="doc-docs-basic-usage"></a>

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

See [Hash Algorithms](#doc-docs-hash-algorithms) for other options.

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

<a id="doc-docs-supported-formats"></a>

The `IdempotencyKey` class automatically detects and normalizes various data formats. All formats are converted to a canonical representation before hashing.

## Arrays

Both associative and indexed arrays are supported:

```php
use Cline\Idempotency\IdempotencyKey;

// Associative arrays
$key = IdempotencyKey::create(['name' => 'John', 'age' => 30]);

// Indexed arrays
$key = IdempotencyKey::create(['apple', 'banana', 'cherry']);

// Mixed arrays
$key = IdempotencyKey::create([
    'items' => ['apple', 'banana'],
    'count' => 2,
]);
```

## Objects

Standard PHP objects are converted to arrays:

```php
$obj = new stdClass();
$obj->name = 'John';
$obj->age = 30;

$key = IdempotencyKey::create($obj);

// Produces same key as array
$same = IdempotencyKey::create(['name' => 'John', 'age' => 30]);
$key->equals($same); // true
```

## JSON Strings

JSON is automatically detected and parsed:

```php
$json = '{"name":"John","age":30}';
$key = IdempotencyKey::create($json);

// Key order in JSON doesn't matter
$json2 = '{"age":30,"name":"John"}';
IdempotencyKey::create($json)->equals(
    IdempotencyKey::create($json2)
); // true

// Arrays
$jsonArray = '["apple","banana","cherry"]';
IdempotencyKey::create($jsonArray);

// Nested structures
$nested = '{
    "user": {"name":"John","age":30},
    "location": {"city":"NYC","country":"USA"}
}';
IdempotencyKey::create($nested);
```

## XML Strings

XML is parsed using the Saloon XML Wrangler:

```php
// Simple XML
$xml = '<user name="John" age="30"/>';
$key = IdempotencyKey::create($xml);

// Attribute order doesn't matter
$xml2 = '<user age="30" name="John"/>';
IdempotencyKey::create($xml)->equals(
    IdempotencyKey::create($xml2)
); // true

// Nested XML
$nested = '
<data>
    <user>
        <name>John</name>
        <age>30</age>
    </user>
    <location>
        <city>NYC</city>
        <country>USA</country>
    </location>
</data>
';
IdempotencyKey::create($nested);
```

## YAML Strings

YAML is parsed using Symfony YAML component:

```php
$yaml = <<<YAML
user:
  name: John
  age: 30
location:
  city: NYC
  country: USA
YAML;

$key = IdempotencyKey::create($yaml);

// Key order doesn't matter in YAML either
$yaml2 = <<<YAML
location:
  country: USA
  city: NYC
user:
  age: 30
  name: John
YAML;

IdempotencyKey::create($yaml)->equals(
    IdempotencyKey::create($yaml2)
); // true
```

## Scalars

Scalar values are automatically wrapped:

```php
// Strings
$key = IdempotencyKey::create('plain string');

// Integers
$key = IdempotencyKey::create(42);

// Floats
$key = IdempotencyKey::create(3.14);

// Booleans
$key = IdempotencyKey::create(true);
$key = IdempotencyKey::create(false);

// Null
$key = IdempotencyKey::create(null);
```

Note: Plain strings that don't parse as JSON, XML, or YAML are treated as scalar values.

## Format Detection Order

When a string is provided, detection happens in this order:

1. **JSON** - Attempts `json_decode()`
2. **XML** - Checks for `<` as first character
3. **YAML** - Attempts `Yaml::parse()`
4. **Scalar** - Falls back to treating as plain string

```php
// JSON detected
IdempotencyKey::create('{"key":"value"}');

// XML detected
IdempotencyKey::create('<root><key>value</key></root>');

// YAML detected
IdempotencyKey::create("key: value\nother: data");

// Plain string (no format detected)
IdempotencyKey::create('just a plain string');
```

## Cross-Format Consistency

The same logical data produces the same key regardless of format:

```php
$array = ['name' => 'John', 'age' => 30];
$json = '{"name":"John","age":30}';
$xml = '<data name="John" age="30"/>';
$yaml = "name: John\nage: 30";

$k1 = IdempotencyKey::create($array);
$k2 = IdempotencyKey::create($json);
$k3 = IdempotencyKey::create($xml);
$k4 = IdempotencyKey::create($yaml);

// Note: XML and YAML may differ from JSON/array due to structure differences
// But same format with same data always produces same key
```

## Type Preservation

During normalization, types are preserved:

```php
$data = [
    'string' => 'hello',
    'int' => 42,
    'float' => 3.14,
    'bool' => true,
    'null' => null,
    'array' => [1, 2, 3],
];

$key = IdempotencyKey::create($data);
```

Different types produce different keys:

```php
IdempotencyKey::create(['value' => 42])->equals(
    IdempotencyKey::create(['value' => '42'])
); // false - int vs string
```

<a id="doc-docs-hash-algorithms"></a>

The library supports multiple cryptographic hash algorithms, each with different security and performance characteristics.

## Available Algorithms

```php
use Cline\Idempotency\HashAlgorithm;
use Cline\Idempotency\IdempotencyKey;

// SHA-256 (default, recommended)
$key = IdempotencyKey::create($data, HashAlgorithm::SHA256);
echo strlen($key->toString()); // 64 characters

// SHA-512 (most secure, larger output)
$key = IdempotencyKey::create($data, HashAlgorithm::SHA512);
echo strlen($key->toString()); // 128 characters

// SHA-1 (faster, but cryptographically weak)
$key = IdempotencyKey::create($data, HashAlgorithm::SHA1);
echo strlen($key->toString()); // 40 characters

// MD5 (fastest, but cryptographically weak)
$key = IdempotencyKey::create($data, HashAlgorithm::MD5);
echo strlen($key->toString()); // 32 characters
```

## Algorithm Comparison

| Algorithm | Length (hex) | Security | Speed | Use Case |
|-----------|--------------|----------|-------|----------|
| SHA-256 | 64 chars | Strong | Fast | **Recommended default** |
| SHA-512 | 128 chars | Strongest | Fast | Maximum security |
| SHA-1 | 40 chars | Weak | Faster | Legacy compatibility only |
| MD5 | 32 chars | Weak | Fastest | Non-security use cases |

## Default Algorithm

If no algorithm is specified, SHA-256 is used:

```php
// These are equivalent
$key1 = IdempotencyKey::create($data);
$key2 = IdempotencyKey::create($data, HashAlgorithm::SHA256);

$key1->equals($key2); // true
```

## Choosing an Algorithm

### Use SHA-256 when:
- You need a good balance of security and performance (most cases)
- Working with modern systems
- No specific requirements dictate otherwise

```php
$key = IdempotencyKey::create($data, HashAlgorithm::SHA256);
```

### Use SHA-512 when:
- Maximum security is required
- Larger key space needed
- Storage/bandwidth for 128 chars is acceptable

```php
$key = IdempotencyKey::create($data, HashAlgorithm::SHA512);
```

### Use SHA-1 when:
- **Not recommended for new code**
- Only for legacy system compatibility
- Security is not a concern

```php
$key = IdempotencyKey::create($data, HashAlgorithm::SHA1);
```

### Use MD5 when:
- **Not recommended for new code**
- Only for non-cryptographic checksums
- Performance is critical and security doesn't matter

```php
$key = IdempotencyKey::create($data, HashAlgorithm::MD5);
```

## Algorithm from String

Create algorithm from string representation:

```php
use Cline\Idempotency\HashAlgorithm;

// Standard names
$sha256 = HashAlgorithm::fromString('sha256');
$sha512 = HashAlgorithm::fromString('sha512');
$sha1 = HashAlgorithm::fromString('sha1');
$md5 = HashAlgorithm::fromString('md5');

// Hyphenated variants (also supported)
$sha256 = HashAlgorithm::fromString('sha-256');
$sha512 = HashAlgorithm::fromString('sha-512');
$sha1 = HashAlgorithm::fromString('sha-1');

// Case insensitive
$sha256 = HashAlgorithm::fromString('SHA256');
$sha256 = HashAlgorithm::fromString('SHA-256');
```

## Algorithm Properties

Get hash output length:

```php
HashAlgorithm::MD5->length();    // 32
HashAlgorithm::SHA1->length();   // 40
HashAlgorithm::SHA256->length(); // 64
HashAlgorithm::SHA512->length(); // 128
```

Get algorithm name:

```php
HashAlgorithm::SHA256->value; // "sha256"
HashAlgorithm::SHA512->value; // "sha512"
```

## Validation with Algorithms

Validate hash strings for specific algorithms:

```php
use Cline\Idempotency\IdempotencyKey;
use Cline\Idempotency\HashAlgorithm;

$sha256Hash = '0123456789abcdef...'; // 64 chars
$sha512Hash = 'fedcba9876543210...'; // 128 chars

// Validate against specific algorithm
IdempotencyKey::isValid($sha256Hash, HashAlgorithm::SHA256); // true
IdempotencyKey::isValid($sha256Hash, HashAlgorithm::SHA512); // false (wrong length)

IdempotencyKey::isValid($sha512Hash, HashAlgorithm::SHA512); // true
IdempotencyKey::isValid($sha512Hash, HashAlgorithm::SHA256); // false (wrong length)
```

## Algorithm in Versioned Strings

Algorithms are preserved in versioned string format:

```php
$key = IdempotencyKey::create($data, HashAlgorithm::SHA512);
$versioned = $key->toVersionedString();
// "v1:sha512:fedcba9876543210..."

$restored = IdempotencyKey::fromVersionedString($versioned);
$restored->getAlgorithm(); // HashAlgorithm::SHA512
```

## Performance Considerations

For high-throughput systems:

```php
// Benchmark different algorithms
$data = ['large' => str_repeat('data', 1000)];

// SHA-256: ~0.05ms per hash (recommended)
$start = microtime(true);
IdempotencyKey::create($data, HashAlgorithm::SHA256);
$sha256Time = microtime(true) - $start;

// SHA-512: ~0.06ms per hash (slightly slower)
$start = microtime(true);
IdempotencyKey::create($data, HashAlgorithm::SHA512);
$sha512Time = microtime(true) - $start;

// MD5: ~0.03ms per hash (fastest, but insecure)
$start = microtime(true);
IdempotencyKey::create($data, HashAlgorithm::MD5);
$md5Time = microtime(true) - $start;
```

**Note**: Performance differences are typically negligible for most use cases. Choose based on security requirements.

## Security Best Practices

1. **Use SHA-256 or SHA-512** for any security-sensitive applications
2. **Avoid MD5 and SHA-1** for new code (both have known vulnerabilities)
3. **Don't use truncated hashes** for security purposes (use full length)
4. **Consider SHA-512** when collision resistance is critical

```php
// Good: Secure default
$key = IdempotencyKey::create($sensitiveData);

// Good: Maximum security
$key = IdempotencyKey::create($sensitiveData, HashAlgorithm::SHA512);

// Bad: Insecure for sensitive data
$key = IdempotencyKey::create($sensitiveData, HashAlgorithm::MD5);

// Bad: Truncation reduces security
$key = IdempotencyKey::create($sensitiveData);
$short = $key->truncate(8); // Don't use for security
```

## Algorithm Consistency

Always use the same algorithm for the same purpose:

```php
class CacheKeyGenerator
{
    // Good: Consistent algorithm
    private const ALGORITHM = HashAlgorithm::SHA256;

    public function generateKey(array $data): string
    {
        return IdempotencyKey::create($data, self::ALGORITHM)
            ->toString();
    }
}

// Bad: Mixing algorithms breaks idempotency
$key1 = IdempotencyKey::create($data, HashAlgorithm::SHA256);
$key2 = IdempotencyKey::create($data, HashAlgorithm::SHA512);
$key1->equals($key2); // false - different algorithms
```

<a id="doc-docs-output-formats"></a>

Idempotency keys can be converted to various output formats for different use cases. Each format offers different characteristics in terms of length, readability, and compatibility.

## Available Formats

The `IdempotencyKey` class provides methods for converting keys to different formats:

```php
use Cline\Idempotency\IdempotencyKey;

$key = IdempotencyKey::create(['user_id' => 123]);

// Hexadecimal (default)
echo $key->toString(); // "a1b2c3d4e5f6..." (64 chars)

// Binary
$binary = $key->toBinary(); // Raw bytes

// Base64
echo $key->toBase64(); // "obLDs..." (44 chars with padding)

// Base62
echo $key->toBase62(); // "2T3kLmN..." (~43 chars)

// UUID
echo $key->toUuid(); // "550e8400-e29b-5d4f-a716-446655440000"

// Versioned string
echo $key->toVersionedString(); // "v1:sha256:a1b2c3d4e5f6..."
```

## Hexadecimal Format

**Default format**. Lowercase hexadecimal representation of the hash.

```php
$key = IdempotencyKey::create($data);
$hex = $key->toString(); // or (string) $key

// SHA-256: 64 characters
// SHA-512: 128 characters
// SHA-1: 40 characters
// MD5: 32 characters
```

**Use cases:**
- Default choice for most scenarios
- Most readable format
- Compatible with hash validation tools
- Standard database storage

```php
// Store in database
DB::table('requests')->insert([
    'idempotency_key' => $key->toString(),
    'data' => json_encode($requestData),
]);
```

## Binary Format

Raw binary representation (most compact).

```php
$key = IdempotencyKey::create($data);
$binary = $key->toBinary();

// SHA-256: 32 bytes
// SHA-512: 64 bytes
// SHA-1: 20 bytes
// MD5: 16 bytes
```

**Use cases:**
- Compact storage when space is critical
- Binary protocols
- File formats requiring binary data

```php
// Write to binary file
file_put_contents('keys.bin', $key->toBinary());

// Use in binary protocol
$packet = pack('N', $userId) . $key->toBinary();
```

## Base64 Format

Standard Base64 encoding (shorter than hex, URL-unfriendly).

```php
$key = IdempotencyKey::create($data);
$base64 = $key->toBase64();

// SHA-256: ~44 characters (with padding)
// Includes: A-Z, a-z, 0-9, +, /, =
```

**Use cases:**
- Compact text representation
- JSON APIs (if URL encoding not needed)
- Email or text transmission

```php
// JSON response
return response()->json([
    'data' => $result,
    'idempotency_key' => $key->toBase64(),
]);

// Note: Contains +, /, = which require URL encoding
$url = "/api/request?key=" . urlencode($key->toBase64());
```

## Base62 Format

Alphanumeric encoding (URL-safe, compact).

```php
$key = IdempotencyKey::create($data);
$base62 = $key->toBase62();

// SHA-256: ~43 characters
// Characters: 0-9, A-Z, a-z (no special chars)
```

**Use cases:**
- URL-safe identifiers
- Short, readable keys
- No escaping needed

```php
// Use in URLs directly (no encoding needed)
$url = "/api/request/{$key->toBase62()}";

// Short, shareable links
$shareUrl = "https://example.com/r/{$key->toBase62()}";
```

## UUID Format

RFC 4122 compliant UUID v5 format.

```php
$key = IdempotencyKey::create($data);
$uuid = $key->toUuid();

// Fixed format: "xxxxxxxx-xxxx-5xxx-yxxx-xxxxxxxxxxxx"
// Always 36 characters (32 hex + 4 hyphens)
```

**Use cases:**
- Systems expecting UUID format
- Database UUID columns
- Compatibility with UUID-based systems

```php
// Store as UUID in database
Schema::create('requests', function (Blueprint $table) {
    $table->uuid('idempotency_key')->primary();
});

DB::table('requests')->insert([
    'idempotency_key' => $key->toUuid(),
    'data' => $requestData,
]);
```

**Note**: Uses first 128 bits of hash (truncated for hashes longer than 128 bits).

## Versioned String Format

Includes version, algorithm, and hash for forward compatibility.

```php
$key = IdempotencyKey::create($data, HashAlgorithm::SHA256);
$versioned = $key->toVersionedString();

// Format: "v{version}:{algorithm}:{hash}"
// Example: "v1:sha256:a1b2c3d4e5f6..."
```

**Use cases:**
- Long-term storage
- Ensuring algorithm compatibility
- Migrating between hash algorithms

```php
// Store with algorithm info
$versioned = $key->toVersionedString();
Cache::put("key:{$id}", $versioned, 3600);

// Restore later (preserves algorithm)
$restored = IdempotencyKey::fromVersionedString($versioned);
$restored->getAlgorithm(); // HashAlgorithm::SHA256
```

## Truncation

Create shorter identifiers (with collision risk):

```php
$key = IdempotencyKey::create($data);

$short = $key->truncate(16); // First 16 chars
$tiny = $key->truncate(8);   // First 8 chars
```

**Use cases:**
- Display identifiers
- Short correlation IDs
- Non-critical deduplication

**Warning**: Collision probability increases exponentially with shorter lengths.

```php
// Safe: Display ID (low collision risk acceptable)
echo "Request ID: " . $key->truncate(12);

// Risky: Deduplication with short key
// Don't do this in production!
$cache->remember($key->truncate(8), fn() => $expensiveOperation());

// Better: Use full-length key
$cache->remember($key->toString(), fn() => $expensiveOperation());
```

## Format Comparison

| Format | Length (SHA-256) | URL-Safe | Readable | Storage | Use Case |
|--------|------------------|----------|----------|---------|----------|
| Hex | 64 chars | Mostly | High | Good | Default choice |
| Binary | 32 bytes | No | No | Best | Space-critical |
| Base64 | ~44 chars | No | Medium | Good | Compact text |
| Base62 | ~43 chars | Yes | Medium | Good | URLs, short IDs |
| UUID | 36 chars | Yes | High | Good | UUID systems |
| Versioned | 73+ chars | Mostly | High | Verbose | Long-term storage |

## Choosing the Right Format

### Use **Hex** (default) when:
- Standard database storage
- General-purpose use
- Readability matters
- No specific constraints

```php
$key->toString()
```

### Use **Binary** when:
- Storage space is critical
- Binary protocols
- File formats

```php
$key->toBinary()
```

### Use **Base64** when:
- Compact text representation
- JSON APIs (non-URL)
- Email transmission

```php
$key->toBase64()
```

### Use **Base62** when:
- URL parameters or paths
- Short, shareable links
- Avoiding special characters

```php
$key->toBase62()
```

### Use **UUID** when:
- UUID columns in database
- Compatibility required
- Standard UUID format expected

```php
$key->toUuid()
```

### Use **Versioned** when:
- Long-term storage
- Algorithm changes possible
- Full metadata needed

```php
$key->toVersionedString()
```

## Practical Examples

### HTTP Headers

```php
// Use hex for standard header
$response->header('X-Idempotency-Key', $key->toString());

// Use base62 for shorter header
$response->header('X-Request-ID', $key->toBase62());
```

### Database Storage

```php
// Standard column
Schema::create('requests', function (Blueprint $table) {
    $table->string('idempotency_key', 64); // Hex SHA-256
});

// UUID column
Schema::create('requests', function (Blueprint $table) {
    $table->uuid('idempotency_key');
});

// Store
DB::table('requests')->insert([
    'idempotency_key' => $key->toString(), // or ->toUuid()
]);
```

### URL Parameters

```php
// Base62 for clean URLs
$url = route('status', ['key' => $key->toBase62()]);
// https://example.com/status/2T3kLmNop4qR

// Hex requires no encoding (no special chars)
$url = route('status', ['key' => $key->toString()]);
// https://example.com/status/a1b2c3d4e5f6...
```

### File Names

```php
// Base62 for clean file names
$filename = "{$key->toBase62()}.json";
Storage::put($filename, $data);

// Hex also works (no special chars)
$filename = "{$key->toString()}.json";
Storage::put($filename, $data);
```

<a id="doc-docs-custom-normalizers"></a>

Custom normalizers allow you to preprocess data before the standard normalization pipeline. This is useful for domain-specific transformations, filtering sensitive data, or normalizing complex business objects.

## The NormalizerInterface

Implement the `NormalizerInterface` to create custom normalizers:

```php
namespace Cline\Idempotency\Contracts;

interface NormalizerInterface
{
    public function normalize(mixed $data): mixed;
}
```

## Basic Custom Normalizer

Create a simple normalizer:

```php
use Cline\Idempotency\Contracts\NormalizerInterface;
use Cline\Idempotency\IdempotencyKey;

class UppercaseNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data): mixed
    {
        if (is_string($data)) {
            return strtoupper($data);
        }

        return $data;
    }
}

// Usage
$normalizer = new UppercaseNormalizer();

$key1 = IdempotencyKey::create('hello', normalizer: $normalizer);
$key2 = IdempotencyKey::create('HELLO', normalizer: $normalizer);

$key1->equals($key2); // true - both normalized to "HELLO"
```

## Filtering Sensitive Fields

Remove sensitive data before generating keys:

```php
use Cline\Idempotency\Contracts\NormalizerInterface;

class SensitiveFieldFilter implements NormalizerInterface
{
    public function __construct(
        private array $excludeFields = ['password', 'api_key', 'token', 'secret']
    ) {}

    public function normalize(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        return $this->filterArray($data);
    }

    private function filterArray(array $data): array
    {
        foreach ($this->excludeFields as $field) {
            unset($data[$field]);
        }

        // Recursively filter nested arrays
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->filterArray($value);
            }
        }

        return $data;
    }
}

// Usage
$userData = [
    'username' => 'john',
    'email' => 'john@example.com',
    'password' => 'secret123',
    'api_key' => 'sk_live_abc123',
];

$normalizer = new SensitiveFieldFilter();
$key = IdempotencyKey::create($userData, normalizer: $normalizer);

// Key generated from: ['username' => 'john', 'email' => 'john@example.com']
// password and api_key excluded
```

## Timestamp Normalization

Normalize timestamps to ensure consistency:

```php
use Cline\Idempotency\Contracts\NormalizerInterface;
use DateTimeInterface;

class TimestampNormalizer implements NormalizerInterface
{
    public function __construct(
        private string $precision = 'minute'
    ) {}

    public function normalize(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        return $this->normalizeArray($data);
    }

    private function normalizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $data[$key] = $this->roundTimestamp($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->normalizeArray($value);
            }
        }

        return $data;
    }

    private function roundTimestamp(DateTimeInterface $dt): string
    {
        return match ($this->precision) {
            'second' => $dt->format('Y-m-d H:i:s'),
            'minute' => $dt->format('Y-m-d H:i:00'),
            'hour' => $dt->format('Y-m-d H:00:00'),
            'day' => $dt->format('Y-m-d 00:00:00'),
            default => $dt->format('Y-m-d H:i:s'),
        };
    }
}

// Usage
$normalizer = new TimestampNormalizer('minute');

$data1 = ['created_at' => new DateTime('2025-01-15 10:30:15')];
$data2 = ['created_at' => new DateTime('2025-01-15 10:30:45')];

$key1 = IdempotencyKey::create($data1, normalizer: $normalizer);
$key2 = IdempotencyKey::create($data2, normalizer: $normalizer);

// Both rounded to '2025-01-15 10:30:00'
$key1->equals($key2); // true
```

## Domain Object Normalizer

Transform domain objects into canonical format:

```php
use Cline\Idempotency\Contracts\NormalizerInterface;

class PaymentNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data): mixed
    {
        if ($data instanceof Payment) {
            return [
                'amount' => $data->getAmount()->getAmount(),
                'currency' => $data->getAmount()->getCurrency()->getCode(),
                'customer_id' => $data->getCustomer()->getId(),
                'items' => array_map(
                    fn($item) => [
                        'sku' => $item->getSku(),
                        'quantity' => $item->getQuantity(),
                    ],
                    $data->getItems()
                ),
            ];
        }

        return $data;
    }
}

// Usage
$payment = new Payment(
    amount: new Money(10000, Currency::USD()),
    customer: $customer,
    items: $items
);

$normalizer = new PaymentNormalizer();
$key = IdempotencyKey::create($payment, normalizer: $normalizer);
```

## Composite Normalizer

Chain multiple normalizers:

```php
use Cline\Idempotency\Contracts\NormalizerInterface;

class CompositeNormalizer implements NormalizerInterface
{
    public function __construct(
        private array $normalizers = []
    ) {}

    public function add(NormalizerInterface $normalizer): self
    {
        $this->normalizers[] = $normalizer;
        return $this;
    }

    public function normalize(mixed $data): mixed
    {
        foreach ($this->normalizers as $normalizer) {
            $data = $normalizer->normalize($data);
        }

        return $data;
    }
}

// Usage
$normalizer = new CompositeNormalizer();
$normalizer
    ->add(new SensitiveFieldFilter())
    ->add(new TimestampNormalizer('minute'))
    ->add(new PaymentNormalizer());

$key = IdempotencyKey::create($paymentData, normalizer: $normalizer);
```

## Sorting Normalizer

Ensure array elements are in consistent order:

```php
use Cline\Idempotency\Contracts\NormalizerInterface;

class SortNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        // Sort indexed arrays by value
        if (array_is_list($data)) {
            sort($data);
            return array_map(fn($v) => $this->normalize($v), $data);
        }

        // Sort associative arrays by key
        ksort($data);
        return array_map(fn($v) => $this->normalize($v), $data);
    }
}

// Usage
$data1 = ['items' => ['banana', 'apple', 'cherry']];
$data2 = ['items' => ['cherry', 'banana', 'apple']];

$normalizer = new SortNormalizer();

$key1 = IdempotencyKey::create($data1, normalizer: $normalizer);
$key2 = IdempotencyKey::create($data2, normalizer: $normalizer);

$key1->equals($key2); // true - both sorted to ['apple', 'banana', 'cherry']
```

**Note**: The built-in normalization already sorts associative array keys. This normalizer is useful if you also need to sort array values.

## Whitespace Normalizer

Normalize whitespace in strings:

```php
use Cline\Idempotency\Contracts\NormalizerInterface;

class WhitespaceNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data): mixed
    {
        if (is_string($data)) {
            // Trim and normalize multiple spaces to single space
            return trim(preg_replace('/\s+/', ' ', $data));
        }

        if (is_array($data)) {
            return array_map(fn($v) => $this->normalize($v), $data);
        }

        return $data;
    }
}

// Usage
$normalizer = new WhitespaceNormalizer();

$text1 = "Hello    World\n\n";
$text2 = "Hello World";

$key1 = IdempotencyKey::create($text1, normalizer: $normalizer);
$key2 = IdempotencyKey::create($text2, normalizer: $normalizer);

$key1->equals($key2); // true - both normalized to "Hello World"
```

## Case-Insensitive Normalizer

Make string comparisons case-insensitive:

```php
use Cline\Idempotency\Contracts\NormalizerInterface;

class CaseInsensitiveNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data): mixed
    {
        if (is_string($data)) {
            return mb_strtolower($data);
        }

        if (is_array($data)) {
            return array_map(fn($v) => $this->normalize($v), $data);
        }

        return $data;
    }
}

// Usage
$normalizer = new CaseInsensitiveNormalizer();

$key1 = IdempotencyKey::create('HELLO', normalizer: $normalizer);
$key2 = IdempotencyKey::create('hello', normalizer: $normalizer);

$key1->equals($key2); // true
```

## Best Practices

1. **Keep normalizers focused** - Each normalizer should handle one concern
2. **Make them composable** - Use CompositeNormalizer to chain multiple normalizers
3. **Document transformations** - Clearly document what each normalizer does
4. **Test thoroughly** - Ensure normalizers produce consistent output
5. **Avoid lossy transformations** - Be careful when removing or transforming data

```php
// Good: Focused, single-purpose normalizer
class EmailNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data): mixed
    {
        if (is_string($data) && filter_var($data, FILTER_VALIDATE_EMAIL)) {
            return strtolower($data);
        }
        return $data;
    }
}

// Good: Compose multiple normalizers
$normalizer = new CompositeNormalizer();
$normalizer
    ->add(new EmailNormalizer())
    ->add(new WhitespaceNormalizer())
    ->add(new SensitiveFieldFilter());
```

<a id="doc-docs-advanced-examples"></a>

Real-world usage patterns and advanced techniques for the idempotency library.

## API Request Deduplication

Prevent duplicate API requests by generating idempotency keys from request data:

```php
use Cline\Idempotency\IdempotencyKey;

class PaymentService
{
    private array $processedKeys = [];

    public function processPayment(array $paymentData): PaymentResult
    {
        // Generate key from payment data
        $key = IdempotencyKey::create($paymentData);
        $keyString = $key->toString();

        // Check if already processed
        if (isset($this->processedKeys[$keyString])) {
            return $this->processedKeys[$keyString];
        }

        // Process payment
        $result = $this->executePayment($paymentData);

        // Cache result
        $this->processedKeys[$keyString] = $result;

        return $result;
    }
}

// Usage
$payment = [
    'amount' => 100.00,
    'currency' => 'USD',
    'customer_id' => 'cust_123',
    'timestamp' => '2025-01-15T10:30:00Z',
];

// These produce the same key, preventing duplicate charge
$service->processPayment($payment);
$service->processPayment($payment); // Returns cached result
```

## HTTP Idempotency Headers

Use with HTTP Idempotency-Key headers:

```php
use Cline\Idempotency\IdempotencyKey;

class ApiController
{
    public function handleRequest(Request $request): Response
    {
        // Extract relevant data (exclude timestamps, request IDs, etc.)
        $requestData = [
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'body' => $request->json(),
        ];

        // Generate idempotency key
        $key = IdempotencyKey::create($requestData);

        // Use truncated version for header (shorter, still unique enough)
        $response = new Response();
        $response->header('Idempotency-Key', $key->truncate(32));

        return $response;
    }
}
```

## Database Record Deduplication

Ensure unique records based on content:

```php
use Cline\Idempotency\IdempotencyKey;
use Illuminate\Support\Facades\DB;

class UserImporter
{
    public function importUser(array $userData): void
    {
        // Generate content-based hash
        $key = IdempotencyKey::create([
            'email' => $userData['email'],
            'name' => $userData['name'],
            'company' => $userData['company'],
        ]);

        // Use as unique constraint
        DB::table('users')->insertOrIgnore([
            'idempotency_key' => $key->toString(),
            'email' => $userData['email'],
            'name' => $userData['name'],
            'company' => $userData['company'],
            'created_at' => now(),
        ]);
    }
}

// Migration
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('idempotency_key', 64)->unique();
    $table->string('email');
    $table->string('name');
    $table->string('company');
    $table->timestamps();
});
```

## Webhook Event Deduplication

Prevent processing duplicate webhook events:

```php
use Cline\Idempotency\IdempotencyKey;
use Illuminate\Support\Facades\Cache;

class WebhookHandler
{
    public function handle(array $webhookPayload): void
    {
        // Generate key from payload (excluding metadata)
        $key = IdempotencyKey::create([
            'event_type' => $webhookPayload['type'],
            'data' => $webhookPayload['data'],
            'resource_id' => $webhookPayload['resource_id'],
        ]);

        $keyString = $key->toString();

        // Check if already processed (with 24h TTL)
        if (Cache::has("webhook:{$keyString}")) {
            return; // Already processed, skip
        }

        // Process webhook
        $this->processWebhook($webhookPayload);

        // Mark as processed
        Cache::put("webhook:{$keyString}", true, now()->addDay());
    }
}
```

## Content-Addressable Storage

Use idempotency keys as content addresses:

```php
use Cline\Idempotency\IdempotencyKey;
use Illuminate\Support\Facades\Storage;

class ContentStore
{
    public function store(string $content): string
    {
        // Generate key from content
        $key = IdempotencyKey::create($content);
        $address = $key->toString();

        // Store only if not exists (automatic deduplication)
        if (!Storage::exists("content/{$address}")) {
            Storage::put("content/{$address}", $content);
        }

        return $address;
    }

    public function retrieve(string $address): ?string
    {
        return Storage::get("content/{$address}");
    }

    public function verify(string $address, string $content): bool
    {
        $key = IdempotencyKey::create($content);
        return $key->equals($address);
    }
}

// Usage
$store = new ContentStore();

$content = 'Lorem ipsum dolor sit amet...';
$address = $store->store($content); // "a1b2c3d4..."

// Same content stores once
$address2 = $store->store($content); // Same address
assert($address === $address2);

// Verify integrity
$retrieved = $store->retrieve($address);
$store->verify($address, $retrieved); // true
```

## Caching with Content-Based Keys

Generate cache keys based on query parameters:

```php
use Cline\Idempotency\IdempotencyKey;
use Illuminate\Support\Facades\Cache;

class ReportGenerator
{
    public function generateReport(array $filters): Report
    {
        // Generate cache key from filters
        $key = IdempotencyKey::create($filters);
        $cacheKey = "report:{$key->truncate(32)}";

        return Cache::remember($cacheKey, 3600, function () use ($filters) {
            return $this->buildReport($filters);
        });
    }
}

// Usage
$filters = [
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-31',
    'department' => 'sales',
    'region' => 'north',
];

// These generate same cache key regardless of order
$report1 = $generator->generateReport($filters);
$report2 = $generator->generateReport([
    'region' => 'north',
    'department' => 'sales',
    'end_date' => '2025-01-31',
    'start_date' => '2025-01-01',
]); // Returns cached result
```

## ETags for HTTP Caching

Generate ETags from response data:

```php
use Cline\Idempotency\IdempotencyKey;

class ApiController
{
    public function getResource(Request $request): Response
    {
        $resource = $this->fetchResource($request->route('id'));

        // Generate ETag from resource data
        $key = IdempotencyKey::create($resource->toArray());
        $etag = $key->truncate(16);

        // Check If-None-Match header
        if ($request->header('If-None-Match') === $etag) {
            return response()->noContent(304);
        }

        return response()->json($resource)->header('ETag', $etag);
    }
}
```

## Distributed Lock Keys

Create deterministic lock keys for distributed systems:

```php
use Cline\Idempotency\IdempotencyKey;
use Illuminate\Support\Facades\Cache;

class DistributedLock
{
    public function executeOnce(array $taskData, callable $callback): mixed
    {
        // Generate deterministic lock key
        $key = IdempotencyKey::create($taskData);
        $lockKey = "lock:{$key->toString()}";

        $lock = Cache::lock($lockKey, 60);

        if ($lock->get()) {
            try {
                return $callback();
            } finally {
                $lock->release();
            }
        }

        throw new LockException('Could not acquire lock');
    }
}

// Usage - prevents concurrent execution of same task
$lock = new DistributedLock();

$taskData = [
    'user_id' => 123,
    'action' => 'send_email',
    'template' => 'welcome',
];

$lock->executeOnce($taskData, function () use ($taskData) {
    // Only one instance across all servers will execute this
    $this->sendEmail($taskData);
});
```

## Versioned Keys with Prefixes

Namespace keys by version and context:

```php
use Cline\Idempotency\IdempotencyKey;

class ApiVersioning
{
    public function generateRequestKey(string $version, array $requestData): string
    {
        // Use prefix to namespace by API version
        $key = IdempotencyKey::create(
            data: $requestData,
            prefix: "api.{$version}"
        );

        return $key->toVersionedString();
    }
}

// Usage
$api = new ApiVersioning();

$requestData = ['user_id' => 123, 'action' => 'update'];

$v1Key = $api->generateRequestKey('v1', $requestData);
// "v1:sha256:abc123..."

$v2Key = $api->generateRequestKey('v2', $requestData);
// "v1:sha256:def456..." (different hash due to different prefix)

// Different versions produce different keys
assert($v1Key !== $v2Key);
```

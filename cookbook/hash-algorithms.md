# Hash Algorithms

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

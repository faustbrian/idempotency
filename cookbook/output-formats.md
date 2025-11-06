# Output Formats

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

<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Idempotency;

use Cline\Idempotency\Actions\DataCanonicalizer;
use Cline\Idempotency\Actions\DataNormalizer;
use Cline\Idempotency\Actions\ObjectNormalizer;
use Cline\Idempotency\Actions\RecursiveSorter;
use Cline\Idempotency\Actions\StringNormalizer;
use Cline\Idempotency\Contracts\NormalizerInterface;
use InvalidArgumentException;
use Stringable;

use function base64_encode;
use function count;
use function dechex;
use function explode;
use function gmp_cmp;
use function gmp_div_q;
use function gmp_init;
use function gmp_intval;
use function gmp_mod;
use function hash;
use function hex2bin;
use function hexdec;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function sprintf;
use function str_starts_with;
use function throw_if;
use function throw_unless;

/**
 * Creates deterministic idempotency keys from arbitrary data.
 *
 * An idempotency key is a unique identifier created from data that remains
 * consistent across multiple creations of the same data. This class provides:
 * - Data normalization (objects, arrays, strings, scalars)
 * - Cryptographic hashing (MD5, SHA1, SHA256, SHA512)
 * - Multiple output formats (hex, base64, base62, UUID, binary)
 * - Versioned string format for storage and retrieval
 * - Custom normalization support via NormalizerInterface
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class IdempotencyKey implements Stringable
{
    /**
     * The current version of the idempotency key format.
     *
     * Used in versioned string representations to ensure forward compatibility.
     */
    private const int VERSION = 1;

    /**
     * Private constructor to enforce factory method usage.
     *
     * @param string        $key       The raw hash value in hexadecimal
     * @param HashAlgorithm $algorithm The hash algorithm used
     * @param null|string   $prefix    Optional prefix included in hash generation
     */
    private function __construct(
        private string $key,
        private HashAlgorithm $algorithm,
        private ?string $prefix,
    ) {}

    /**
     * Converts the idempotency key to its string representation.
     *
     * @return string The hexadecimal hash value
     */
    public function __toString(): string
    {
        return $this->key;
    }

    /**
     * Creates an idempotency key from arbitrary data.
     *
     * The creation process:
     * 1. Applies custom normalizer if provided
     * 2. Includes prefix in hash input if provided
     * 3. Normalizes data into canonical array format
     * 4. Converts to deterministic JSON string
     * 5. Hashes the canonical string
     *
     * @param  mixed                    $data       The data to create a key from
     * @param  null|HashAlgorithm       $algorithm  Hash algorithm (default: SHA256)
     * @param  null|string              $prefix     Optional prefix to namespace the key
     * @param  null|NormalizerInterface $normalizer Custom normalizer for preprocessing
     * @return self                     The created idempotency key
     */
    public static function create(
        mixed $data,
        ?HashAlgorithm $algorithm = null,
        ?string $prefix = null,
        ?NormalizerInterface $normalizer = null,
    ): self {
        $algorithm ??= HashAlgorithm::SHA256;
        $prefix = $prefix === '' ? null : $prefix;

        $recursiveSorter = new RecursiveSorter();
        $canonicalizer = new DataCanonicalizer($recursiveSorter);
        $stringNormalizer = new StringNormalizer();
        $objectNormalizer = new ObjectNormalizer();
        $dataNormalizer = new DataNormalizer($objectNormalizer, $stringNormalizer);

        // Apply custom normalizer first if provided
        $processedData = $normalizer instanceof NormalizerInterface ? $normalizer->normalize($data) : $data;

        // Include prefix in hash input if provided
        if ($prefix !== null) {
            $processedData = ['__prefix__' => $prefix, '__data__' => $processedData];
        }

        $normalized = $dataNormalizer->normalize($processedData);
        $canonical = $canonicalizer->canonicalize($normalized);
        $hash = hash($algorithm->value, $canonical);

        return new self($hash, $algorithm, $prefix);
    }

    /**
     * Creates an IdempotencyKey from a versioned string representation.
     *
     * The versioned string format is: "v{version}:{algorithm}:{hash}"
     * Example: "v1:sha256:a1b2c3d4..."
     *
     * @param string $versionedString The versioned string to parse
     *
     * @throws InvalidArgumentException If the string format is invalid, version is unsupported, or hash is invalid
     *
     * @return self The reconstructed idempotency key
     */
    public static function fromVersionedString(string $versionedString): self
    {
        $parts = explode(':', $versionedString, 3);

        throw_if(count($parts) !== 3, InvalidArgumentException::class, 'Invalid versioned key format');

        [$version, $algorithmName, $hash] = $parts;

        throw_unless(str_starts_with($version, 'v'), InvalidArgumentException::class, 'Version must start with "v"');

        $versionNumber = (int) mb_substr($version, 1);

        if ($versionNumber !== self::VERSION) {
            throw new InvalidArgumentException(sprintf('Unsupported version: %d', $versionNumber));
        }

        $algorithm = HashAlgorithm::fromString($algorithmName);

        throw_unless(self::isValid($hash, $algorithm), InvalidArgumentException::class, 'Invalid hash in versioned string');

        return new self($hash, $algorithm, null);
    }

    /**
     * Validates whether a string is a valid hash for the given algorithm.
     *
     * Validation checks:
     * - Length matches the algorithm's expected output length
     * - Contains only lowercase hexadecimal characters [a-f0-9]
     *
     * @param  string             $key       The hash string to validate
     * @param  null|HashAlgorithm $algorithm The hash algorithm (default: SHA256)
     * @return bool               True if the hash is valid, false otherwise
     */
    public static function isValid(string $key, ?HashAlgorithm $algorithm = null): bool
    {
        $algorithm ??= HashAlgorithm::SHA256;
        $expectedLength = $algorithm->length();

        if (mb_strlen($key) !== $expectedLength) {
            return false;
        }

        // Must be lowercase hex
        return preg_match('/^[a-f0-9]{'.$expectedLength.'}$/', $key) === 1;
    }

    /**
     * Returns the hexadecimal string representation of the key.
     *
     * @return string The hash in hexadecimal format
     */
    public function toString(): string
    {
        return $this->key;
    }

    /**
     * Returns the hash algorithm used for this key.
     *
     * @return HashAlgorithm The hash algorithm
     */
    public function getAlgorithm(): HashAlgorithm
    {
        return $this->algorithm;
    }

    /**
     * Returns the prefix used during key generation, if any.
     *
     * @return null|string The prefix or null if none was used
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * Returns the version number of the key format.
     *
     * @return int The version number
     */
    public function getVersion(): int
    {
        return self::VERSION;
    }

    /**
     * Converts the hexadecimal key to binary format.
     *
     * @throws InvalidArgumentException If the hex to binary conversion fails
     *
     * @return string The binary representation of the hash
     */
    public function toBinary(): string
    {
        $binary = hex2bin($this->key);

        throw_if($binary === false, InvalidArgumentException::class, 'Failed to convert hex to binary');

        return $binary;
    }

    /**
     * Converts the key to Base64 encoding.
     *
     * Useful for shorter string representations in URLs or compact storage.
     *
     * @return string The Base64-encoded hash
     */
    public function toBase64(): string
    {
        return base64_encode($this->toBinary());
    }

    /**
     * Converts the key to Base62 encoding.
     *
     * Base62 uses [0-9A-Za-z] and is URL-safe without encoding.
     * Produces the shortest alphanumeric representation.
     *
     * @return string The Base62-encoded hash
     */
    public function toBase62(): string
    {
        $this->toBinary();
        $base62 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $result = '';

        // Convert binary to decimal (as string for large numbers)
        $decimal = gmp_init('0x'.$this->key);

        if (gmp_cmp($decimal, 0) === 0) {
            return '0';
        }

        while (gmp_cmp($decimal, 0) > 0) {
            [$decimal, $remainder] = [
                gmp_div_q($decimal, 62),
                gmp_intval(gmp_mod($decimal, 62)),
            ];
            $result = $base62[$remainder].$result;
        }

        return $result;
    }

    /**
     * Converts the key to UUID v5 format.
     *
     * Uses the first 16 bytes (32 hex chars) of the hash and formats them
     * as a RFC 4122 compliant UUID v5 (name-based with SHA-1).
     *
     * Format: xxxxxxxx-xxxx-5xxx-yxxx-xxxxxxxxxxxx
     *
     * @return string The UUID representation
     */
    public function toUuid(): string
    {
        // Use first 16 bytes of hash for UUID v5 format
        $hex = mb_substr($this->key, 0, 32);

        // Format as UUID v5: xxxxxxxx-xxxx-5xxx-yxxx-xxxxxxxxxxxx
        $uuid = sprintf(
            '%s-%s-5%s-%s%s-%s',
            mb_substr($hex, 0, 8),
            mb_substr($hex, 8, 4),
            mb_substr($hex, 13, 3),
            dechex(hexdec(mb_substr($hex, 16, 1)) & 0x3 | 0x8),
            mb_substr($hex, 17, 3),
            mb_substr($hex, 20, 12),
        );

        return $uuid;
    }

    /**
     * Converts the key to a versioned string format for storage.
     *
     * Format: "v{version}:{algorithm}:{hash}"
     * Example: "v1:sha256:a1b2c3d4e5f6..."
     *
     * This format can be parsed back using fromVersionedString().
     *
     * @return string The versioned string representation
     */
    public function toVersionedString(): string
    {
        return sprintf('v%d:%s:%s', self::VERSION, $this->algorithm->value, $this->key);
    }

    /**
     * Returns a truncated portion of the key.
     *
     * Useful for creating short identifiers when full hash length isn't needed.
     * Note: Shorter lengths increase collision probability.
     *
     * @param int $length The number of characters to return (must be > 0)
     *
     * @throws InvalidArgumentException If length is not greater than 0
     *
     * @return string The truncated hash
     */
    public function truncate(int $length): string
    {
        throw_if($length <= 0, InvalidArgumentException::class, 'Truncate length must be greater than 0');

        return mb_substr($this->key, 0, $length);
    }

    /**
     * Checks if this key equals another key or hash string.
     *
     * Performs a constant-time string comparison of the hash values.
     *
     * @param  self|string $other Another IdempotencyKey or raw hash string
     * @return bool        True if the keys are equal, false otherwise
     */
    public function equals(self|string $other): bool
    {
        $otherKey = $other instanceof self ? $other->key : $other;

        return $this->key === $otherKey;
    }

    /**
     * Checks if this key matches the key that would be created from data.
     *
     * Creates a new key from the provided data using this key's algorithm
     * and prefix, then compares it to this key.
     *
     * @param  mixed $data The data to compare against
     * @return bool  True if the created key matches this key
     */
    public function matches(mixed $data): bool
    {
        $generatedKey = self::create($data, $this->algorithm, $this->prefix);

        return $this->equals($generatedKey);
    }
}

<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Idempotency;

use InvalidArgumentException;

use function mb_strtolower;

/**
 * Supported cryptographic hash algorithms for idempotency key generation.
 *
 * This enum defines the available hash algorithms and their properties:
 * - MD5: Fast but cryptographically weak (32 hex chars)
 * - SHA1: Faster, cryptographically weak (40 hex chars)
 * - SHA256: Secure and recommended default (64 hex chars)
 * - SHA512: Most secure, larger output (128 hex chars)
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum HashAlgorithm: string
{
    case MD5 = 'md5';
    case SHA1 = 'sha1';
    case SHA256 = 'sha256';
    case SHA512 = 'sha512';

    /**
     * Creates a HashAlgorithm from a string representation.
     *
     * Accepts both lowercase and hyphenated variants:
     * - "md5" -> MD5
     * - "sha1" or "sha-1" -> SHA1
     * - "sha256" or "sha-256" -> SHA256
     * - "sha512" or "sha-512" -> SHA512
     *
     * @param string $algorithm The algorithm name (case-insensitive)
     *
     * @throws InvalidArgumentException If the algorithm is not supported
     *
     * @return self The matching HashAlgorithm enum case
     */
    public static function fromString(string $algorithm): self
    {
        return match (mb_strtolower($algorithm)) {
            'md5' => self::MD5,
            'sha1', 'sha-1' => self::SHA1,
            'sha256', 'sha-256' => self::SHA256,
            'sha512', 'sha-512' => self::SHA512,
            default => throw new InvalidArgumentException('Unsupported hash algorithm: '.$algorithm),
        };
    }

    /**
     * Returns the expected length of the hash output in hexadecimal characters.
     *
     * Length mappings:
     * - MD5: 32 characters
     * - SHA1: 40 characters
     * - SHA256: 64 characters
     * - SHA512: 128 characters
     *
     * @return int The hash length in hexadecimal characters
     */
    public function length(): int
    {
        return match ($this) {
            self::MD5 => 32,
            self::SHA1 => 40,
            self::SHA256 => 64,
            self::SHA512 => 128,
        };
    }
}

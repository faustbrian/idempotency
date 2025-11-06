<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Idempotency;

/**
 * Defines available output format representations for idempotency keys.
 *
 * Each format offers different characteristics:
 *
 * - Hex: Lowercase hexadecimal (default, longest readable format)
 *        Example: "a1b2c3d4e5f6..."
 *
 * - Binary: Raw binary bytes (shortest, not human-readable)
 *           Useful for storage efficiency
 *
 * - Base64: Standard Base64 encoding (compact, URL-unfriendly without encoding)
 *           Example: "SGVsbG8gV29ybGQ="
 *
 * - Base62: Alphanumeric [0-9A-Za-z] (URL-safe, compact)
 *           Example: "2T3kLmNop4qR"
 *
 * - UUID: RFC 4122 compliant UUID v5 format (standard, widely compatible)
 *         Example: "550e8400-e29b-5d4f-a716-446655440000"
 *
 * @author Brian Faust <brian@cline.sh>
 */
enum OutputFormat
{
    /**
     * Hexadecimal format (lowercase).
     *
     * The default and most common format. Full hash length.
     */
    case Hex;

    /**
     * Binary format (raw bytes).
     *
     * Most compact representation but not human-readable.
     */
    case Binary;

    /**
     * Base64 encoding.
     *
     * Compact and widely supported, but may require URL encoding.
     */
    case Base64;

    /**
     * Base62 encoding (alphanumeric).
     *
     * URL-safe without encoding, shorter than hex, longer than binary.
     */
    case Base62;

    /**
     * UUID v5 format (RFC 4122).
     *
     * Standard UUID format with dashes, uses first 128 bits of hash.
     */
    case UUID;
}

<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Idempotency\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when a versioned key string has an invalid format.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidVersionedKeyFormatException extends InvalidArgumentException implements IdempotencyException
{
    public static function invalidFormat(): self
    {
        return new self('Invalid versioned key format');
    }
}

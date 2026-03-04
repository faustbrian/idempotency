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
 * Exception thrown when the hash portion of a versioned string is invalid.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidHashInVersionedStringException extends InvalidArgumentException implements IdempotencyException
{
    public static function invalid(): self
    {
        return new self('Invalid hash in versioned string');
    }
}

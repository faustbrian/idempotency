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
 * Exception thrown when truncate length is invalid.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidTruncateLengthException extends InvalidArgumentException implements IdempotencyException
{
    public static function mustBeGreaterThanZero(): self
    {
        return new self('Truncate length must be greater than 0');
    }
}

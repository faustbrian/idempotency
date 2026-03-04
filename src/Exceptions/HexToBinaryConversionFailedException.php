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
 * Exception thrown when hexadecimal to binary conversion fails.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class HexToBinaryConversionFailedException extends InvalidArgumentException implements IdempotencyException
{
    public static function failed(): self
    {
        return new self('Failed to convert hex to binary');
    }
}

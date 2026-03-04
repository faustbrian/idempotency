<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Idempotency\Exceptions;

use InvalidArgumentException;

use function get_debug_type;

/**
 * Exception thrown when attempting to normalize an unsupported data type.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnsupportedDataTypeException extends InvalidArgumentException implements IdempotencyException
{
    public static function fromValue(mixed $data): self
    {
        return new self('Unsupported data type: '.get_debug_type($data));
    }
}

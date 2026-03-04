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
 * Exception thrown when a version prefix is missing or invalid.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class InvalidVersionPrefixException extends InvalidArgumentException implements IdempotencyException
{
    public static function mustStartWithV(): self
    {
        return new self('Version must start with "v"');
    }
}

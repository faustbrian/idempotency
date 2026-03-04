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
 * Exception thrown when an unsupported hash algorithm is specified.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnsupportedHashAlgorithmException extends InvalidArgumentException implements IdempotencyException
{
    public static function forAlgorithm(string $algorithm): self
    {
        return new self('Unsupported hash algorithm: '.$algorithm);
    }
}

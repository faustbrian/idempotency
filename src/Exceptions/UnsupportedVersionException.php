<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Idempotency\Exceptions;

use InvalidArgumentException;

use function sprintf;

/**
 * Exception thrown when an unsupported version number is encountered.
 *
 * @author Brian Faust <brian@cline.sh>
 */
final class UnsupportedVersionException extends InvalidArgumentException implements IdempotencyException
{
    public static function forVersion(int $version): self
    {
        return new self(sprintf('Unsupported version: %d', $version));
    }
}

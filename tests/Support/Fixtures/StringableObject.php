<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Support\Fixtures;

use Stringable;

/**
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class StringableObject implements Stringable
{
    public function __construct(
        private string $json = '{"name":"John","age":30}',
    ) {}

    public function __toString(): string
    {
        return $this->json;
    }
}

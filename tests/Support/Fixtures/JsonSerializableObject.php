<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Support\Fixtures;

use JsonSerializable;

/**
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class JsonSerializableObject implements JsonSerializable
{
    public function __construct(
        private array $data = ['name' => 'John',
            'age' => 30],
    ) {}

    public function jsonSerialize(): array
    {
        return $this->data;
    }
}

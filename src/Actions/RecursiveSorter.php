<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Idempotency\Actions;

use const SORT_STRING;

use function is_array;
use function ksort;

/**
 * Recursively sorts arrays by keys to ensure deterministic ordering.
 *
 * This class traverses nested array structures and sorts all keys
 * alphabetically using SORT_STRING. Non-array values are returned unchanged.
 *
 * The recursive sorting ensures that equivalent data structures always
 * produce the same key ordering, which is critical for generating
 * consistent idempotency keys.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class RecursiveSorter
{
    /**
     * Recursively sorts an array by its keys.
     *
     * For arrays, this method:
     * 1. Recursively sorts all nested arrays
     * 2. Sorts the current array's keys using string comparison
     *
     * Non-array values are returned unchanged.
     *
     * @param  mixed $value The value to sort (array or scalar)
     * @return mixed The sorted array or unchanged value
     */
    public function sort(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $result = [];

        foreach ($value as $key => $item) {
            $result[$key] = $this->sort($item);
        }

        ksort($result, SORT_STRING);

        return $result;
    }
}

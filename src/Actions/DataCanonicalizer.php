<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Idempotency\Actions;

use JsonException;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

use function json_encode;

/**
 * Converts normalized data into a canonical string representation.
 *
 * This class transforms an array of data into a deterministic JSON string by:
 * 1. Recursively sorting all keys in the data structure
 * 2. Encoding the sorted data as JSON with consistent formatting
 *
 * The canonical representation ensures that identical data always produces
 * the same output string, regardless of original key ordering.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class DataCanonicalizer
{
    /**
     * Creates a new data canonicalizer instance.
     *
     * @param RecursiveSorter $recursiveSorter Sorter for recursively organizing array keys
     */
    public function __construct(
        private RecursiveSorter $recursiveSorter,
    ) {}

    /**
     * Converts an array into a canonical JSON string.
     *
     * The data is first recursively sorted by keys to ensure consistent ordering,
     * then encoded as JSON with unescaped Unicode and slashes for maximum
     * compatibility and readability.
     *
     * @param array<string, mixed> $data The data to canonicalize
     *
     * @throws JsonException If JSON encoding fails
     *
     * @return string The canonical JSON representation
     */
    public function canonicalize(array $data): string
    {
        $sorted = $this->recursiveSorter->sort($data);

        return json_encode($sorted, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

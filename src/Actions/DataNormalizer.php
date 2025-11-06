<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Idempotency\Actions;

use InvalidArgumentException;

use function get_debug_type;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;

/**
 * Normalizes mixed data into a consistent array representation.
 *
 * This class handles normalization of various PHP data types including:
 * - Arrays (passed through as-is)
 * - Objects (normalized via ObjectNormalizer)
 * - Strings (parsed and normalized via StringNormalizer)
 * - Scalars and null (wrapped in a value array)
 *
 * The normalization process ensures all data can be consistently processed
 * for idempotency key generation.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class DataNormalizer
{
    /**
     * Creates a new data normalizer instance.
     *
     * @param ObjectNormalizer $objectNormalizer Normalizer for object data
     * @param StringNormalizer $stringNormalizer Normalizer for string data
     */
    public function __construct(
        private ObjectNormalizer $objectNormalizer,
        private StringNormalizer $stringNormalizer,
    ) {}

    /**
     * Normalizes mixed data into an array representation.
     *
     * The normalization strategy depends on the input type:
     * - Arrays are returned unchanged
     * - Objects are normalized using their structure or interfaces
     * - Strings are parsed (JSON, XML, YAML) or wrapped
     * - Scalars and null are wrapped in a value array
     *
     * @param mixed $data The data to normalize
     *
     * @throws InvalidArgumentException If the data type is not supported
     *
     * @return array<string, mixed> The normalized array representation
     */
    public function normalize(mixed $data): array
    {
        if (is_array($data)) {
            // @phpstan-ignore-next-line return.type
            return $data;
        }

        if (is_object($data)) {
            return $this->objectNormalizer->normalize($data);
        }

        if (is_string($data)) {
            return $this->stringNormalizer->normalize($data);
        }

        if (null === $data || is_bool($data) || is_int($data) || is_float($data)) {
            return ['value' => $data];
        }

        throw new InvalidArgumentException('Unsupported data type: '.get_debug_type($data));
    }
}

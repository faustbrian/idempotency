<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Idempotency\Actions;

use JsonSerializable;
use ReflectionObject;
use Stringable;

use function is_array;
use function is_string;

/**
 * Normalizes PHP objects into array representations.
 *
 * This class handles object normalization using multiple strategies:
 * 1. JsonSerializable objects: Uses jsonSerialize() method
 * 2. Stringable objects: Converts to string and normalizes
 * 3. Plain objects: Uses reflection to extract initialized properties
 *
 * The normalization process preserves object data in a consistent array
 * format suitable for hashing.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class ObjectNormalizer
{
    /**
     * Normalizes an object into an array representation.
     *
     * Priority order:
     * 1. JsonSerializable::jsonSerialize() - uses the object's custom serialization
     * 2. Stringable::__toString() - converts to string and parses if structured
     * 3. Reflection - extracts all initialized properties
     *
     * @param  object               $object The object to normalize
     * @return array<string, mixed> The normalized array representation
     */
    public function normalize(object $object): array
    {
        if ($object instanceof JsonSerializable) {
            $value = $object->jsonSerialize();

            // @phpstan-ignore-next-line return.type
            return match (true) {
                is_array($value) => $value,
                is_string($value) => new StringNormalizer()->normalize($value),
                default => ['value' => $value],
            };
        }

        if ($object instanceof Stringable) {
            return new StringNormalizer()->normalize((string) $object);
        }

        $reflection = new ReflectionObject($object);

        /** @var array<string, mixed> */
        $data = [];

        foreach ($reflection->getProperties() as $property) {
            if ($property->isInitialized($object)) {
                $data[$property->getName()] = $property->getValue($object);
            }
        }

        return $data;
    }
}

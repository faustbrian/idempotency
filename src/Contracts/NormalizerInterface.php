<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Idempotency\Contracts;

/**
 * Contract for custom data normalizers.
 *
 * Implementations of this interface can be provided to the IdempotencyKey::create()
 * method to apply custom normalization logic before the standard normalization pipeline.
 *
 * This allows for domain-specific data transformations such as:
 * - Filtering sensitive fields
 * - Normalizing timestamps or dates
 * - Transforming business objects
 * - Applying custom serialization rules
 *
 * @author Brian Faust <brian@cline.sh>
 */
interface NormalizerInterface
{
    /**
     * Normalizes the given data into a consistent representation.
     *
     * This method is called before the standard normalization pipeline,
     * allowing custom transformations of the input data.
     *
     * @param  mixed $data The data to normalize
     * @return mixed The normalized data (typically an array or scalar)
     */
    public function normalize(mixed $data): mixed;
}

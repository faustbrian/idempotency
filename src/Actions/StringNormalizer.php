<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cline\Idempotency\Actions;

use JsonException;
use Symfony\Component\Yaml\Yaml;
use Throwable;

use const JSON_THROW_ON_ERROR;

use function count;
use function is_array;
use function json_decode;
use function mb_trim;
use function str_contains;
use function str_starts_with;
use function VeeWee\Xml\Encoding\xml_decode;

/**
 * Normalizes strings into array representations by parsing structured formats.
 *
 * This class attempts to parse strings as structured data in the following order:
 * 1. JSON - strings starting with { or [
 * 2. XML - strings starting with <
 * 3. YAML - strings containing : with newlines
 * 4. Plain strings - wrapped in a value array
 *
 * The normalization process ensures that structured string data is converted
 * to a consistent array format for idempotency key generation.
 *
 * @author Brian Faust <brian@cline.sh>
 *
 * @psalm-immutable
 */
final readonly class StringNormalizer
{
    /**
     * Normalizes a string into an array representation.
     *
     * Parsing attempts (in order):
     * 1. JSON: Tries json_decode() for { or [ prefixed strings
     * 2. XML: Tries XML decoding for < prefixed strings
     * 3. YAML: Tries Yaml::parse() for colon-containing multiline strings
     * 4. Plain: Wraps the original string in a value array
     *
     * All parsing failures are silently caught and the next format is attempted.
     *
     * @param  string               $string The string to normalize
     * @return array<string, mixed> The normalized array representation
     */
    public function normalize(string $string): array
    {
        $trimmed = mb_trim($string);

        // Try JSON
        if (str_starts_with($trimmed, '{') || str_starts_with($trimmed, '[')) {
            try {
                $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);

                if (is_array($decoded)) {
                    // @phpstan-ignore-next-line return.type
                    return $decoded;
                }
            } catch (JsonException) {
                // Not JSON, continue
            }
        }

        // Try XML
        if (str_starts_with($trimmed, '<')) {
            try {
                $decoded = xml_decode($trimmed);

                /** @var array<string, mixed> $decoded */
                return $this->normalizeXml($decoded);
            } catch (Throwable) {
                // Not XML, continue
            }
        }

        // Try YAML (basic detection)
        if (str_contains($trimmed, ':') && (str_contains($trimmed, "\n") || str_contains($trimmed, "\r"))) {
            try {
                $parsed = Yaml::parse($trimmed);

                if (is_array($parsed)) {
                    // @phpstan-ignore-next-line return.type
                    return $parsed;
                }
            } catch (Throwable) {
                // Not YAML, continue
            }
        }

        // Plain string
        return ['value' => $string];
    }

    /**
     * @param  array<string, mixed> $decoded
     * @return array<string, mixed>
     */
    private function normalizeXml(array $decoded): array
    {
        foreach ($decoded as $key => $value) {
            $decoded[$key] = $this->normalizeXmlValue($value);
        }

        return $decoded;
    }

    private function normalizeXmlValue(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        unset($value['@attributes'], $value['@namespaces']);

        if (count($value) === 1 && isset($value['@value'])) {
            return $value['@value'];
        }

        foreach ($value as $key => $nestedValue) {
            $value[$key] = $this->normalizeXmlValue($nestedValue);
        }

        return $value;
    }
}

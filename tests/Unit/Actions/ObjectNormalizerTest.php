<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Idempotency\Actions\ObjectNormalizer;
use Tests\Support\Fixtures\EmptyObject;
use Tests\Support\Fixtures\JsonSerializableObject;
use Tests\Support\Fixtures\SimpleObject;
use Tests\Support\Fixtures\StringableObject;
use Tests\Support\Fixtures\UninitializedPropertiesObject;

covers(ObjectNormalizer::class);

/*
|--------------------------------------------------------------------------
| Happy Path Tests - JsonSerializable Objects
|--------------------------------------------------------------------------
*/

test('normalizes JsonSerializable object returning array', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new JsonSerializableObject(['name' => 'John', 'age' => 30]);

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe(['name' => 'John', 'age' => 30]);
})->group('happy-path', 'json-serializable');

test('normalizes JsonSerializable object with different key order', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object1 = new JsonSerializableObject(['name' => 'John', 'age' => 30]);
    $object2 = new JsonSerializableObject(['age' => 30, 'name' => 'John']);

    // Act
    $result1 = $normalizer->normalize($object1);
    $result2 = $normalizer->normalize($object2);

    // Assert
    expect($result1)->toBe(['name' => 'John', 'age' => 30]);
    expect($result2)->toBe(['age' => 30, 'name' => 'John']);
})->group('happy-path', 'json-serializable');

test('normalizes JsonSerializable object returning string with JSON', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new class() implements JsonSerializable
    {
        public function jsonSerialize(): string
        {
            return '{"name":"John","age":30}';
        }
    };

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe(['name' => 'John', 'age' => 30]);
})->group('happy-path', 'json-serializable');

test('normalizes JsonSerializable object returning scalar', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new class() implements JsonSerializable
    {
        public function jsonSerialize(): int
        {
            return 42;
        }
    };

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe(['value' => 42]);
})->group('happy-path', 'json-serializable');

test('normalizes JsonSerializable object returning null', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new class() implements JsonSerializable
    {
        public function jsonSerialize(): null
        {
            return null;
        }
    };

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe(['value' => null]);
})->group('happy-path', 'json-serializable');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Stringable Objects
|--------------------------------------------------------------------------
*/

test('normalizes Stringable object with JSON content', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new StringableObject('{"name":"John","age":30}');

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe(['name' => 'John', 'age' => 30]);
})->group('happy-path', 'stringable');

test('normalizes Stringable object with plain text', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new StringableObject('hello world');

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe(['value' => 'hello world']);
})->group('happy-path', 'stringable');

test('normalizes Stringable object with XML content', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new StringableObject('<user name="John" age="30"/>');

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBeArray()
        ->toHaveKey('user');
})->group('happy-path', 'stringable');

test('normalizes Stringable object with empty string', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new StringableObject('');

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe(['value' => '']);
})->group('happy-path', 'stringable');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Plain Objects with Reflection
|--------------------------------------------------------------------------
*/

test('normalizes plain object with public properties', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new SimpleObject();

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe(['name' => 'John', 'age' => 30]);
})->group('happy-path', 'plain-objects');

test('normalizes plain object extracts all property types', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new class()
    {
        public string $publicProp = 'public';

        protected string $protectedProp = 'protected';
    };

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe([
        'publicProp' => 'public',
        'protectedProp' => 'protected',
    ]);
})->group('happy-path', 'plain-objects');

test('normalizes empty object with no properties', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new EmptyObject();

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe([]);
})->group('happy-path', 'plain-objects');

test('normalizes object with various property types', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new class()
    {
        public string $string = 'text';

        public int $int = 42;

        public float $float = 3.14;

        public bool $bool = true;

        public ?string $nullable = null;

        public array $array = ['a', 'b'];
    };

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe([
        'string' => 'text',
        'int' => 42,
        'float' => 3.14,
        'bool' => true,
        'nullable' => null,
        'array' => ['a', 'b'],
    ]);
})->group('happy-path', 'plain-objects');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Uninitialized Properties
|--------------------------------------------------------------------------
*/

test('normalizes object skipping uninitialized properties', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new UninitializedPropertiesObject();

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe(['initialized' => 'value'])
        ->not->toHaveKey('uninitialized');
})->group('happy-path', 'uninitialized');

test('normalizes object with all uninitialized properties', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new class()
    {
        public string $prop1;

        public int $prop2;

        public ?string $prop3 = null;
    };

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe(['prop3' => null]);
})->group('happy-path', 'uninitialized');

test('normalizes object with mixed initialized and uninitialized properties', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new class()
    {
        public string $initialized = 'value';

        public string $uninitialized;

        public int $alsoInitialized = 42;

        public int $alsoUninitialized;
    };

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe([
        'initialized' => 'value',
        'alsoInitialized' => 42,
    ])
        ->not->toHaveKey('uninitialized')
        ->not->toHaveKey('alsoUninitialized');
})->group('happy-path', 'uninitialized');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Nested Objects
|--------------------------------------------------------------------------
*/

test('normalizes object with nested object properties', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $inner = new SimpleObject();
    $object = new readonly class($inner)
    {
        public function __construct(
            public object $nested,
        ) {}
    };

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBeArray()
        ->toHaveKey('nested')
        ->and($result['nested'])->toBeObject();
})->group('edge-case', 'nested');

test('normalizes object with array of values', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new class()
    {
        public array $items = ['a', 'b', 'c'];

        public array $nested = ['key' => 'value'];
    };

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe([
        'items' => ['a', 'b', 'c'],
        'nested' => ['key' => 'value'],
    ]);
})->group('edge-case', 'arrays');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Special Property Names
|--------------------------------------------------------------------------
*/

test('normalizes object with numeric property names', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new class()
    {
        public string $prop0 = 'zero';

        public string $prop1 = 'one';

        public string $prop2 = 'two';
    };

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe([
        'prop0' => 'zero',
        'prop1' => 'one',
        'prop2' => 'two',
    ]);
})->group('edge-case', 'property-names');

test('normalizes object with underscore-prefixed property names', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new class()
    {
        public string $_private = 'underscore';

        public string $normal = 'normal';
    };

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe([
        '_private' => 'underscore',
        'normal' => 'normal',
    ]);
})->group('edge-case', 'property-names');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Property Order
|--------------------------------------------------------------------------
*/

test('preserves property declaration order', function (): void {
    // Arrange
    $normalizer = new ObjectNormalizer();
    $object = new class()
    {
        public string $zebra = 'z';

        public string $alpha = 'a';

        public string $beta = 'b';
    };

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBe([
        'zebra' => 'z',
        'alpha' => 'a',
        'beta' => 'b',
    ]);
    expect(array_keys($result))->toBe(['zebra', 'alpha', 'beta']);
})->group('edge-case', 'order');

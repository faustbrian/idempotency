<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Idempotency\Actions\DataNormalizer;
use Cline\Idempotency\Actions\ObjectNormalizer;
use Cline\Idempotency\Actions\StringNormalizer;
use Tests\Support\Fixtures\SimpleObject;

covers(DataNormalizer::class);

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Array Handling
|--------------------------------------------------------------------------
*/

test('normalizes array data without modification', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );
    $data = ['name' => 'John', 'age' => 30];

    // Act
    $result = $normalizer->normalize($data);

    // Assert
    expect($result)->toBe($data);
})->group('happy-path', 'arrays');

test('normalizes nested array data', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );
    $data = [
        'user' => ['name' => 'John', 'age' => 30],
        'meta' => ['created' => '2024-01-01'],
    ];

    // Act
    $result = $normalizer->normalize($data);

    // Assert
    expect($result)->toBe($data);
})->group('happy-path', 'arrays');

test('normalizes empty array', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );
    $data = [];

    // Act
    $result = $normalizer->normalize($data);

    // Assert
    expect($result)->toBe([]);
})->group('happy-path', 'arrays');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Object Handling
|--------------------------------------------------------------------------
*/

test('normalizes objects by delegating to ObjectNormalizer', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );
    $object = new SimpleObject();

    // Act
    $result = $normalizer->normalize($object);

    // Assert
    expect($result)->toBeArray()
        ->toHaveKey('name')
        ->toHaveKey('age');
})->group('happy-path', 'objects');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - String Handling
|--------------------------------------------------------------------------
*/

test('normalizes JSON string by delegating to StringNormalizer', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );
    $json = '{"name":"John","age":30}';

    // Act
    $result = $normalizer->normalize($json);

    // Assert
    expect($result)->toBe(['name' => 'John', 'age' => 30]);
})->group('happy-path', 'strings');

test('normalizes plain string to value wrapper', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );
    $string = 'hello world';

    // Act
    $result = $normalizer->normalize($string);

    // Assert
    expect($result)->toBe(['value' => 'hello world']);
})->group('happy-path', 'strings');

test('normalizes empty string to value wrapper', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );
    $string = '';

    // Act
    $result = $normalizer->normalize($string);

    // Assert
    expect($result)->toBe(['value' => '']);
})->group('happy-path', 'strings');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Scalar Values
|--------------------------------------------------------------------------
*/

test('normalizes null to value wrapper', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );

    // Act
    $result = $normalizer->normalize(null);

    // Assert
    expect($result)->toBe(['value' => null]);
})->group('happy-path', 'scalars');

test('normalizes boolean true to value wrapper', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );

    // Act
    $result = $normalizer->normalize(true);

    // Assert
    expect($result)->toBe(['value' => true]);
})->group('happy-path', 'scalars');

test('normalizes boolean false to value wrapper', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );

    // Act
    $result = $normalizer->normalize(false);

    // Assert
    expect($result)->toBe(['value' => false]);
})->group('happy-path', 'scalars');

test('normalizes integer to value wrapper', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );

    // Act
    $result = $normalizer->normalize(42);

    // Assert
    expect($result)->toBe(['value' => 42]);
})->group('happy-path', 'scalars');

test('normalizes zero to value wrapper', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );

    // Act
    $result = $normalizer->normalize(0);

    // Assert
    expect($result)->toBe(['value' => 0]);
})->group('happy-path', 'scalars');

test('normalizes negative integer to value wrapper', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );

    // Act
    $result = $normalizer->normalize(-42);

    // Assert
    expect($result)->toBe(['value' => -42]);
})->group('happy-path', 'scalars');

test('normalizes float to value wrapper', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );

    // Act
    $result = $normalizer->normalize(3.14);

    // Assert
    expect($result)->toBe(['value' => 3.14]);
})->group('happy-path', 'scalars');

test('normalizes zero float to value wrapper', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );

    // Act
    $result = $normalizer->normalize(0.0);

    // Assert
    expect($result)->toBe(['value' => 0.0]);
})->group('happy-path', 'scalars');

test('normalizes scientific notation to value wrapper', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );

    // Act
    $result = $normalizer->normalize(1.23e-10);

    // Assert
    expect($result)->toBe(['value' => 1.23e-10]);
})->group('happy-path', 'scalars');

/*
|--------------------------------------------------------------------------
| Sad Path Tests - Invalid Data Types
|--------------------------------------------------------------------------
*/

test('throws exception for resource type', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );
    $resource = fopen('php://memory', 'rb');

    // Act & Assert
    expect(fn (): array => $normalizer->normalize($resource))
        ->toThrow(InvalidArgumentException::class, 'Unsupported data type: resource');

    fclose($resource);
})->group('sad-path', 'invalid-input');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Boundary Values
|--------------------------------------------------------------------------
*/

test('normalizes large integer to value wrapper', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );
    $largeInt = 9_999_999_999_999_999;

    // Act
    $result = $normalizer->normalize($largeInt);

    // Assert
    expect($result)->toBe(['value' => $largeInt]);
})->group('edge-case', 'boundaries');

test('normalizes very small float to value wrapper', function (): void {
    // Arrange
    $normalizer = new DataNormalizer(
        new ObjectNormalizer(),
        new StringNormalizer(),
    );
    $smallFloat = 0.000_000_001;

    // Act
    $result = $normalizer->normalize($smallFloat);

    // Assert
    expect($result)->toBe(['value' => $smallFloat]);
})->group('edge-case', 'boundaries');

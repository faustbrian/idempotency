<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Idempotency\Actions\DataCanonicalizer;
use Cline\Idempotency\Actions\RecursiveSorter;

covers(DataCanonicalizer::class);

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Basic Canonicalization
|--------------------------------------------------------------------------
*/

test('canonicalizes simple array to JSON string', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['name' => 'John', 'age' => 30];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBe('{"age":30,"name":"John"}');
})->group('happy-path', 'basic');

test('canonicalizes nested array to JSON string', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = [
        'user' => ['name' => 'John', 'age' => 30],
        'meta' => ['created' => '2024-01-01'],
    ];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBe('{"meta":{"created":"2024-01-01"},"user":{"age":30,"name":"John"}}');
})->group('happy-path', 'basic');

test('canonicalizes empty array to empty JSON object', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = [];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBe('[]');
})->group('happy-path', 'basic');

test('canonicalizes indexed array to JSON array', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['apple', 'banana', 'cherry'];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBe('["apple","banana","cherry"]');
})->group('happy-path', 'basic');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Key Sorting
|--------------------------------------------------------------------------
*/

test('sorts keys alphabetically', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['zebra' => 1, 'apple' => 2, 'mango' => 3];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBe('{"apple":2,"mango":3,"zebra":1}');
})->group('happy-path', 'sorting');

test('sorts nested keys alphabetically', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = [
        'user' => ['zebra' => 1, 'apple' => 2],
        'meta' => ['beta' => 3, 'alpha' => 4],
    ];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBe('{"meta":{"alpha":4,"beta":3},"user":{"apple":2,"zebra":1}}');
})->group('happy-path', 'sorting');

test('produces same output regardless of input key order', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data1 = ['name' => 'John', 'age' => 30, 'city' => 'NYC'];
    $data2 = ['city' => 'NYC', 'name' => 'John', 'age' => 30];
    $data3 = ['age' => 30, 'city' => 'NYC', 'name' => 'John'];

    // Act
    $result1 = $canonicalizer->canonicalize($data1);
    $result2 = $canonicalizer->canonicalize($data2);
    $result3 = $canonicalizer->canonicalize($data3);

    // Assert
    expect($result1)->toBe($result2)->toBe($result3);
})->group('happy-path', 'sorting');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - JSON Encoding Flags
|--------------------------------------------------------------------------
*/

test('encodes unicode characters without escaping', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['name' => 'José', 'emoji' => '🚀', 'chinese' => '中文'];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toContain('José')
        ->toContain('🚀')
        ->toContain('中文')
        ->not->toContain('\u');
})->group('happy-path', 'encoding-flags');

test('encodes slashes without escaping', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['url' => 'https://example.com/path', 'path' => '/home/user'];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toContain('https://example.com/path')
        ->toContain('/home/user')
        ->not->toContain('\/');
})->group('happy-path', 'encoding-flags');

test('preserves special characters properly', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['quote' => 'He said "hello"', 'newline' => "line1\nline2"];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBeString()
        ->toContain('"He said \"hello\""')
        ->toContain('\n');
})->group('happy-path', 'encoding-flags');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Value Types
|--------------------------------------------------------------------------
*/

test('canonicalizes null values', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['name' => 'John', 'middle' => null, 'age' => 30];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBe('{"age":30,"middle":null,"name":"John"}');
})->group('happy-path', 'value-types');

test('canonicalizes boolean values', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['active' => true, 'deleted' => false];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBe('{"active":true,"deleted":false}');
})->group('happy-path', 'value-types');

test('canonicalizes integer values', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['positive' => 42, 'zero' => 0, 'negative' => -10];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBe('{"negative":-10,"positive":42,"zero":0}');
})->group('happy-path', 'value-types');

test('canonicalizes float values', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['pi' => 3.14, 'small' => 0.001, 'scientific' => 1.23e-10];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBeString()
        ->toContain('"pi":3.14')
        ->toContain('"small":0.001');
})->group('happy-path', 'value-types');

test('canonicalizes string values', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['text' => 'hello', 'empty' => '', 'special' => 'with "quotes"'];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toContain('"text":"hello"')
        ->toContain('"empty":""')
        ->toContain('with \"quotes\"');
})->group('happy-path', 'value-types');

test('canonicalizes mixed value types', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = [
        'string' => 'text',
        'int' => 42,
        'float' => 3.14,
        'bool' => true,
        'null' => null,
        'array' => ['a', 'b'],
    ];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBeString()
        ->toContain('"string":"text"')
        ->toContain('"int":42')
        ->toContain('"float":3.14')
        ->toContain('"bool":true')
        ->toContain('"null":null')
        ->toContain('"array":["a","b"]');
})->group('happy-path', 'value-types');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Deep Nesting
|--------------------------------------------------------------------------
*/

test('canonicalizes deeply nested structures', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = [
        'level1' => [
            'level2' => [
                'level3' => [
                    'level4' => [
                        'value' => 'deep',
                    ],
                ],
            ],
        ],
    ];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBe('{"level1":{"level2":{"level3":{"level4":{"value":"deep"}}}}}');
})->group('edge-case', 'nesting');

test('canonicalizes deeply nested arrays with mixed keys', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = [
        'users' => [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
        ],
        'meta' => [
            'total' => 2,
        ],
    ];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBe('{"meta":{"total":2},"users":[{"age":30,"name":"John"},{"age":25,"name":"Jane"}]}');
})->group('edge-case', 'nesting');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Empty Values
|--------------------------------------------------------------------------
*/

test('canonicalizes array with empty string', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['value' => ''];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBe('{"value":""}');
})->group('edge-case', 'empty');

test('canonicalizes array with empty nested array', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['data' => [], 'meta' => []];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBe('{"data":[],"meta":[]}');
})->group('edge-case', 'empty');

test('canonicalizes array with empty nested object', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['user' => ['data' => []]];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBe('{"user":{"data":[]}}');
})->group('edge-case', 'empty');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Key Types
|--------------------------------------------------------------------------
*/

test('canonicalizes numeric string keys', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['0' => 'a', '1' => 'b', '2' => 'c'];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBe('["a","b","c"]');
})->group('edge-case', 'key-types');

test('canonicalizes mixed key types', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['name' => 'John', 0 => 'first', 'age' => 30, 1 => 'second'];

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBeString()
        ->toContain('"name":"John"')
        ->toContain('"age":30');
})->group('edge-case', 'key-types');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Large Data
|--------------------------------------------------------------------------
*/

test('canonicalizes large arrays efficiently', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = [];

    for ($i = 0; $i < 100; ++$i) {
        $data['key'.$i] = 'value'.$i;
    }

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBeString()
        ->toContain('"key0":"value0"')
        ->toContain('"key99":"value99"');
})->group('edge-case', 'large-data');

test('canonicalizes nested large arrays', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = [];

    for ($i = 0; $i < 10; ++$i) {
        $data['group'.$i] = [
            'id' => $i,
            'items' => array_fill(0, 10, 'item'.$i),
        ];
    }

    // Act
    $result = $canonicalizer->canonicalize($data);

    // Assert
    expect($result)->toBeString()
        ->toContain('"group0"')
        ->toContain('"group9"');
})->group('edge-case', 'large-data');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Consistency
|--------------------------------------------------------------------------
*/

test('produces identical output for identical data', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data = ['name' => 'John', 'age' => 30, 'city' => 'NYC'];

    // Act
    $result1 = $canonicalizer->canonicalize($data);
    $result2 = $canonicalizer->canonicalize($data);
    $result3 = $canonicalizer->canonicalize($data);

    // Assert
    expect($result1)->toBe($result2)->toBe($result3);
})->group('edge-case', 'consistency');

test('produces identical output for data with different key order', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data1 = ['name' => 'John', 'age' => 30];
    $data2 = ['age' => 30, 'name' => 'John'];

    // Act
    $result1 = $canonicalizer->canonicalize($data1);
    $result2 = $canonicalizer->canonicalize($data2);

    // Assert
    expect($result1)->toBe($result2);
})->group('edge-case', 'consistency');

test('produces different output for different values', function (): void {
    // Arrange
    $canonicalizer = new DataCanonicalizer(
        new RecursiveSorter(),
    );
    $data1 = ['name' => 'John', 'age' => 30];
    $data2 = ['name' => 'Jane', 'age' => 30];

    // Act
    $result1 = $canonicalizer->canonicalize($data1);
    $result2 = $canonicalizer->canonicalize($data2);

    // Assert
    expect($result1)->not->toBe($result2);
})->group('edge-case', 'consistency');

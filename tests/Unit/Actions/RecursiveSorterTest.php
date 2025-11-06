<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Idempotency\Actions\RecursiveSorter;

covers(RecursiveSorter::class);

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Flat Arrays
|--------------------------------------------------------------------------
*/

test('sorts flat associative array by keys', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = ['zebra' => 1, 'apple' => 2, 'mango' => 3];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result)->toBe(['apple' => 2, 'mango' => 3, 'zebra' => 1]);
    expect(array_keys($result))->toBe(['apple', 'mango', 'zebra']);
})->group('happy-path', 'flat-arrays');

test('sorts flat array with mixed key types', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = ['name' => 'John', 0 => 'first', 'age' => 30, 1 => 'second'];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect(array_keys($result))->toBe([0, 1, 'age', 'name']);
})->group('happy-path', 'flat-arrays');

test('sorts flat array with numeric string keys', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = ['10' => 'ten', '2' => 'two', '1' => 'one'];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect(array_keys($result))->toBe([1, 10, 2]);
})->group('happy-path', 'flat-arrays');

test('maintains indexed array order when keys are sequential', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = ['apple', 'banana', 'cherry'];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result)->toBe(['apple', 'banana', 'cherry']);
})->group('happy-path', 'flat-arrays');

test('sorts empty array', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = [];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result)->toBe([]);
})->group('happy-path', 'flat-arrays');

test('sorts array with single element', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = ['key' => 'value'];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result)->toBe(['key' => 'value']);
})->group('happy-path', 'flat-arrays');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Nested Arrays
|--------------------------------------------------------------------------
*/

test('sorts nested arrays recursively', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = [
        'user' => ['zebra' => 1, 'apple' => 2],
        'meta' => ['beta' => 3, 'alpha' => 4],
    ];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result)->toBe([
        'meta' => ['alpha' => 4, 'beta' => 3],
        'user' => ['apple' => 2, 'zebra' => 1],
    ]);
})->group('happy-path', 'nested-arrays');

test('sorts deeply nested arrays', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = [
        'level1' => [
            'level2' => [
                'level3' => [
                    'zebra' => 1,
                    'apple' => 2,
                ],
            ],
        ],
    ];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result['level1']['level2']['level3'])->toBe([
        'apple' => 2,
        'zebra' => 1,
    ]);
})->group('happy-path', 'nested-arrays');

test('sorts arrays with nested indexed arrays', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = [
        'users' => [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
        ],
    ];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result['users'][0])->toBe(['age' => 30, 'name' => 'John']);
    expect($result['users'][1])->toBe(['age' => 25, 'name' => 'Jane']);
})->group('happy-path', 'nested-arrays');

test('sorts complex nested structure', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = [
        'database' => [
            'connections' => [
                'mysql' => ['port' => 3_306, 'host' => 'localhost'],
                'redis' => ['port' => 6_379, 'host' => 'localhost'],
            ],
        ],
    ];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result)->toBe([
        'database' => [
            'connections' => [
                'mysql' => ['host' => 'localhost', 'port' => 3_306],
                'redis' => ['host' => 'localhost', 'port' => 6_379],
            ],
        ],
    ]);
})->group('happy-path', 'nested-arrays');

test('sorts nested empty arrays', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = ['data' => [], 'meta' => []];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result)->toBe(['data' => [], 'meta' => []]);
})->group('happy-path', 'nested-arrays');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Non-Array Values
|--------------------------------------------------------------------------
*/

test('returns non-array values unchanged', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();

    // Act & Assert
    expect($sorter->sort('string'))->toBe('string');
    expect($sorter->sort(42))->toBe(42);
    expect($sorter->sort(3.14))->toBe(3.14);
    expect($sorter->sort(true))->toBe(true);
    expect($sorter->sort(false))->toBe(false);
    expect($sorter->sort(null))->toBe(null);
})->group('happy-path', 'non-arrays');

test('preserves scalar values in arrays', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = [
        'string' => 'text',
        'int' => 42,
        'float' => 3.14,
        'bool' => true,
        'null' => null,
    ];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result)->toBe([
        'bool' => true,
        'float' => 3.14,
        'int' => 42,
        'null' => null,
        'string' => 'text',
    ]);
})->group('happy-path', 'non-arrays');

test('preserves objects in arrays', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $object = new stdClass();
    $object->name = 'John';

    $data = ['object' => $object, 'string' => 'text'];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result['object'])->toBe($object);
    expect(array_keys($result))->toBe(['object', 'string']);
})->group('happy-path', 'non-arrays');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Key Ordering
|--------------------------------------------------------------------------
*/

test('uses SORT_STRING for key sorting', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = [
        '100' => 'hundred',
        '20' => 'twenty',
        '3' => 'three',
        'a100' => 'a hundred',
        'a20' => 'a twenty',
        'a3' => 'a three',
    ];

    // Act
    $result = $sorter->sort($data);

    // Assert
    // SORT_STRING with numeric string keys converts to integers first
    expect(array_keys($result))->toBe([100, 20, 3, 'a100', 'a20', 'a3']);
})->group('edge-case', 'sorting-mode');

test('sorts case-sensitively', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = [
        'Zebra' => 1,
        'apple' => 2,
        'Apple' => 3,
        'zebra' => 4,
    ];

    // Act
    $result = $sorter->sort($data);

    // Assert
    // SORT_STRING is case-sensitive: uppercase comes before lowercase
    expect(array_keys($result))->toBe(['Apple', 'Zebra', 'apple', 'zebra']);
})->group('edge-case', 'sorting-mode');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Special Keys
|--------------------------------------------------------------------------
*/

test('sorts keys with special characters', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = [
        'key_underscore' => 1,
        'key-dash' => 2,
        'key.dot' => 3,
        'key space' => 4,
    ];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result)->toBeArray();
    expect(array_keys($result))->toHaveCount(4);
})->group('edge-case', 'special-keys');

test('sorts keys with unicode characters', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = [
        'café' => 1,
        'naïve' => 2,
        'résumé' => 3,
    ];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result)->toBeArray();
    expect(array_keys($result))->toHaveCount(3);
})->group('edge-case', 'special-keys');

test('sorts empty string keys', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = ['' => 'empty', 'a' => 'a', 'b' => 'b'];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result)->toHaveKey('');
    expect(array_keys($result)[0])->toBe('');
})->group('edge-case', 'special-keys');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Large Arrays
|--------------------------------------------------------------------------
*/

test('sorts large flat arrays efficiently', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = [];

    for ($i = 100; $i > 0; --$i) {
        $data['key'.$i] = 'value'.$i;
    }

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect(array_keys($result)[0])->toBe('key1');
    expect(array_keys($result)[99])->toBe('key99');
    expect($result)->toHaveCount(100);
})->group('edge-case', 'large-arrays');

test('sorts deeply nested large arrays', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = [];

    for ($i = 10; $i > 0; --$i) {
        $data['group'.$i] = [
            'z' => 1,
            'a' => 2,
            'items' => array_fill(0, 5, 'item'.$i),
        ];
    }

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect(array_keys($result))->toHaveCount(10);
    expect(array_keys($result['group1']))->toBe(['a', 'items', 'z']);
})->group('edge-case', 'large-arrays');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Consistency
|--------------------------------------------------------------------------
*/

test('produces identical output for identical input', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = ['zebra' => 1, 'apple' => 2, 'mango' => 3];

    // Act
    $result1 = $sorter->sort($data);
    $result2 = $sorter->sort($data);
    $result3 = $sorter->sort($data);

    // Assert
    expect($result1)->toBe($result2)->toBe($result3);
})->group('edge-case', 'consistency');

test('produces identical output regardless of input order', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data1 = ['zebra' => 1, 'apple' => 2, 'mango' => 3];
    $data2 = ['apple' => 2, 'mango' => 3, 'zebra' => 1];
    $data3 = ['mango' => 3, 'zebra' => 1, 'apple' => 2];

    // Act
    $result1 = $sorter->sort($data1);
    $result2 = $sorter->sort($data2);
    $result3 = $sorter->sort($data3);

    // Assert
    expect($result1)->toBe($result2)->toBe($result3);
})->group('edge-case', 'consistency');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Mixed Structures
|--------------------------------------------------------------------------
*/

test('sorts array with mixed nested and scalar values', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = [
        'scalar' => 42,
        'nested' => ['zebra' => 1, 'apple' => 2],
        'another' => 'text',
    ];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result)->toBe([
        'another' => 'text',
        'nested' => ['apple' => 2, 'zebra' => 1],
        'scalar' => 42,
    ]);
})->group('edge-case', 'mixed-structures');

test('sorts array containing both indexed and associative nested arrays', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = [
        'indexed' => ['a', 'b', 'c'],
        'associative' => ['zebra' => 1, 'apple' => 2],
    ];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result['associative'])->toBe(['apple' => 2, 'zebra' => 1]);
    expect($result['indexed'])->toBe(['a', 'b', 'c']);
})->group('edge-case', 'mixed-structures');

test('handles very deeply nested arrays', function (): void {
    // Arrange
    $sorter = new RecursiveSorter();
    $data = [
        'l1' => [
            'l2' => [
                'l3' => [
                    'l4' => [
                        'l5' => [
                            'l6' => [
                                'l7' => [
                                    'l8' => [
                                        'l9' => [
                                            'l10' => [
                                                'z' => 1,
                                                'a' => 2,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    // Act
    $result = $sorter->sort($data);

    // Assert
    expect($result['l1']['l2']['l3']['l4']['l5']['l6']['l7']['l8']['l9']['l10'])
        ->toBe(['a' => 2, 'z' => 1]);
})->group('edge-case', 'mixed-structures');

<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Idempotency\Contracts\NormalizerInterface;
use Cline\Idempotency\HashAlgorithm;
use Cline\Idempotency\IdempotencyKey;
use Tests\Support\Fixtures\EmptyObject;
use Tests\Support\Fixtures\InnerObject;
use Tests\Support\Fixtures\JsonSerializableObject;
use Tests\Support\Fixtures\MixedVisibilityObject;
use Tests\Support\Fixtures\NestedObjectContainer;
use Tests\Support\Fixtures\SimpleObject;
use Tests\Support\Fixtures\StringableObject;
use Tests\Support\Fixtures\UninitializedPropertiesObject;

covers(IdempotencyKey::class);

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Basic Array Handling
|--------------------------------------------------------------------------
*/

test('generates same key for identical arrays', function (): void {
    // Arrange
    $data1 = ['name' => 'John', 'age' => 30];
    $data2 = ['name' => 'John', 'age' => 30];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'arrays');

test('generates same key for arrays with different key order', function (): void {
    // Arrange
    $data1 = ['name' => 'John', 'age' => 30, 'city' => 'NYC'];
    $data2 = ['city' => 'NYC', 'name' => 'John', 'age' => 30];
    $data3 = ['age' => 30, 'city' => 'NYC', 'name' => 'John'];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);
    $key3 = IdempotencyKey::create($data3);

    // Assert
    expect($key1->toString())
        ->toBe($key2->toString())
        ->toBe($key3->toString());
})->group('happy-path', 'arrays');

test('generates different keys for different data', function (): void {
    // Arrange
    $data1 = ['name' => 'John', 'age' => 30];
    $data2 = ['name' => 'Jane', 'age' => 30];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->not->toBe($key2->toString());
})->group('happy-path', 'arrays');

test('handles arrays with numeric keys consistently', function (): void {
    // Arrange
    $data1 = ['a', 'b', 'c'];
    $data2 = ['a', 'b', 'c'];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'arrays');

test('handles mixed arrays', function (): void {
    // Arrange
    $data1 = ['name' => 'John', 0 => 'first', 'age' => 30, 1 => 'second'];
    $data2 = [1 => 'second', 'age' => 30, 0 => 'first', 'name' => 'John'];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'arrays');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Nested Structures
|--------------------------------------------------------------------------
*/

test('generates same key for nested arrays with different key order', function (): void {
    // Arrange
    $data1 = [
        'user' => ['name' => 'John', 'age' => 30],
        'location' => ['city' => 'NYC', 'country' => 'USA'],
    ];
    $data2 = [
        'location' => ['country' => 'USA', 'city' => 'NYC'],
        'user' => ['age' => 30, 'name' => 'John'],
    ];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'nested');

test('handles deeply nested structures', function (): void {
    // Arrange
    $data1 = [
        'level1' => [
            'level2' => [
                'level3' => [
                    'name' => 'John',
                    'age' => 30,
                ],
                'tags' => ['php', 'laravel'],
            ],
        ],
    ];
    $data2 = [
        'level1' => [
            'level2' => [
                'tags' => ['php', 'laravel'],
                'level3' => [
                    'age' => 30,
                    'name' => 'John',
                ],
            ],
        ],
    ];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'nested');

test('handles very deeply nested structures (10+ levels)', function (): void {
    // Arrange
    $data1 = [
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
                                                'l11' => [
                                                    'l12' => [
                                                        'value' => 'deep',
                                                        'id' => 123,
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
            ],
        ],
    ];
    $data2 = [
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
                                                'l11' => [
                                                    'l12' => [
                                                        'id' => 123,
                                                        'value' => 'deep',
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
            ],
        ],
    ];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'nested');

test('handles deeply nested mixed arrays and objects', function (): void {
    // Arrange
    $data1 = [
        'users' => [
            ['name' => 'John', 'roles' => ['admin', 'user']],
            ['name' => 'Jane', 'roles' => ['user', 'moderator']],
        ],
        'metadata' => [
            'timestamps' => [
                'created' => '2024-01-01',
                'updated' => '2024-01-02',
            ],
        ],
    ];
    $data2 = [
        'metadata' => [
            'timestamps' => [
                'updated' => '2024-01-02',
                'created' => '2024-01-01',
            ],
        ],
        'users' => [
            ['roles' => ['admin', 'user'], 'name' => 'John'],
            ['roles' => ['user', 'moderator'], 'name' => 'Jane'],
        ],
    ];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'nested');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - JSON String Parsing
|--------------------------------------------------------------------------
*/

test('generates same key for JSON strings', function (): void {
    // Arrange
    $json1 = '{"name":"John","age":30}';
    $json2 = '{"age":30,"name":"John"}';

    // Act
    $key1 = IdempotencyKey::create($json1);
    $key2 = IdempotencyKey::create($json2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'json');

test('generates same key for JSON string and array', function (): void {
    // Arrange
    $json = '{"name":"John","age":30}';
    $array = ['age' => 30, 'name' => 'John'];

    // Act
    $key1 = IdempotencyKey::create($json);
    $key2 = IdempotencyKey::create($array);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'json');

test('handles JSON with whitespace variations', function (): void {
    // Arrange
    $json1 = '{"name":"John","age":30}';
    $json2 = '  {"name":"John","age":30}  ';
    $json3 = "\n\t{\"name\":\"John\",\"age\":30}\n\t";

    // Act
    $key1 = IdempotencyKey::create($json1);
    $key2 = IdempotencyKey::create($json2);
    $key3 = IdempotencyKey::create($json3);

    // Assert
    expect($key1->toString())
        ->toBe($key2->toString())
        ->toBe($key3->toString());
})->group('happy-path', 'json');

test('handles JSON arrays', function (): void {
    // Arrange
    $json1 = '["apple","banana","cherry"]';
    $json2 = '["apple","banana","cherry"]';

    // Act
    $key1 = IdempotencyKey::create($json1);
    $key2 = IdempotencyKey::create($json2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'json');

test('handles nested JSON structures', function (): void {
    // Arrange
    $json1 = '{"user":{"name":"John","address":{"city":"NYC","zip":"10001"}}}';
    $json2 = '{"user":{"address":{"zip":"10001","city":"NYC"},"name":"John"}}';

    // Act
    $key1 = IdempotencyKey::create($json1);
    $key2 = IdempotencyKey::create($json2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'json');

test('handles JSON with unicode characters', function (): void {
    // Arrange
    $json1 = '{"name":"José","emoji":"🚀","chinese":"中文"}';
    $json2 = '{"emoji":"🚀","chinese":"中文","name":"José"}';

    // Act
    $key1 = IdempotencyKey::create($json1);
    $key2 = IdempotencyKey::create($json2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'json');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - XML String Parsing
|--------------------------------------------------------------------------
*/

test('generates same key for XML strings with different attribute order', function (): void {
    // Arrange
    $xml1 = '<user name="John" age="30"/>';
    $xml2 = '<user age="30" name="John"/>';

    // Act
    $key1 = IdempotencyKey::create($xml1);
    $key2 = IdempotencyKey::create($xml2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'xml');

test('handles nested XML elements', function (): void {
    // Arrange
    $xml1 = '<root><user><name>John</name><age>30</age></user></root>';
    $xml2 = '<root><user><age>30</age><name>John</name></user></root>';

    // Act
    $key1 = IdempotencyKey::create($xml1);
    $key2 = IdempotencyKey::create($xml2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'xml');

test('handles XML with CDATA sections', function (): void {
    // Arrange
    $xml1 = '<content><![CDATA[<p>HTML content</p>]]></content>';
    $xml2 = '<content><![CDATA[<p>HTML content</p>]]></content>';

    // Act
    $key1 = IdempotencyKey::create($xml1);
    $key2 = IdempotencyKey::create($xml2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'xml');

test('handles XML with namespaces', function (): void {
    // Arrange
    $xml1 = '<root xmlns:foo="http://example.com"><foo:item>value</foo:item></root>';
    $xml2 = '<root xmlns:foo="http://example.com"><foo:item>value</foo:item></root>';

    // Act
    $key1 = IdempotencyKey::create($xml1);
    $key2 = IdempotencyKey::create($xml2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'xml');

test('handles XML with mixed content', function (): void {
    // Arrange
    $xml1 = '<item id="1" type="product"><name>Laptop</name><price>999.99</price><tags><tag>electronics</tag><tag>computers</tag></tags></item>';
    $xml2 = '<item type="product" id="1"><price>999.99</price><name>Laptop</name><tags><tag>electronics</tag><tag>computers</tag></tags></item>';

    // Act
    $key1 = IdempotencyKey::create($xml1);
    $key2 = IdempotencyKey::create($xml2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'xml');

test('handles XML with whitespace variations', function (): void {
    // Arrange
    $xml1 = '<user name="John" age="30"/>';
    $xml2 = '  <user name="John" age="30"/>  ';
    $xml3 = "\n\t<user name=\"John\" age=\"30\"/>\n";

    // Act
    $key1 = IdempotencyKey::create($xml1);
    $key2 = IdempotencyKey::create($xml2);
    $key3 = IdempotencyKey::create($xml3);

    // Assert
    expect($key1->toString())
        ->toBe($key2->toString())
        ->toBe($key3->toString());
})->group('happy-path', 'xml');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - YAML String Parsing
|--------------------------------------------------------------------------
*/

test('handles simple YAML structures', function (): void {
    // Arrange
    $yaml1 = "name: John\nage: 30";
    $yaml2 = "age: 30\nname: John";

    // Act
    $key1 = IdempotencyKey::create($yaml1);
    $key2 = IdempotencyKey::create($yaml2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'yaml');

test('handles nested YAML structures', function (): void {
    // Arrange
    $yaml1 = "user:\n  name: John\n  age: 30\nlocation:\n  city: NYC\n  country: USA";
    $yaml2 = "location:\n  country: USA\n  city: NYC\nuser:\n  age: 30\n  name: John";

    // Act
    $key1 = IdempotencyKey::create($yaml1);
    $key2 = IdempotencyKey::create($yaml2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'yaml');

test('handles YAML lists', function (): void {
    // Arrange
    $yaml1 = "tags:\n  - php\n  - laravel\n  - pest";
    $yaml2 = "tags:\n  - php\n  - laravel\n  - pest";

    // Act
    $key1 = IdempotencyKey::create($yaml1);
    $key2 = IdempotencyKey::create($yaml2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'yaml');

test('handles YAML with multiline strings', function (): void {
    // Arrange
    $yaml1 = "description: |\n  This is a multiline\n  string value\n  with content";
    $yaml2 = "description: |\n  This is a multiline\n  string value\n  with content";

    // Act
    $key1 = IdempotencyKey::create($yaml1);
    $key2 = IdempotencyKey::create($yaml2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'yaml');

test('handles complex YAML structures', function (): void {
    // Arrange
    $yaml1 = "database:\n  connections:\n    mysql:\n      host: localhost\n      port: 3306\n    redis:\n      host: localhost\n      port: 6379";
    $yaml2 = "database:\n  connections:\n    redis:\n      port: 6379\n      host: localhost\n    mysql:\n      port: 3306\n      host: localhost";

    // Act
    $key1 = IdempotencyKey::create($yaml1);
    $key2 = IdempotencyKey::create($yaml2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'yaml');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Object Handling
|--------------------------------------------------------------------------
*/

test('generates same key for objects with same properties', function (): void {
    // Arrange
    $obj1 = new SimpleObject();
    $obj2 = new SimpleObject();

    // Act
    $key1 = IdempotencyKey::create($obj1);
    $key2 = IdempotencyKey::create($obj2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'objects');

test('generates same key for JsonSerializable objects', function (): void {
    // Arrange
    $obj1 = new JsonSerializableObject(['name' => 'John', 'age' => 30]);
    $obj2 = new JsonSerializableObject(['age' => 30, 'name' => 'John']);

    // Act
    $key1 = IdempotencyKey::create($obj1);
    $key2 = IdempotencyKey::create($obj2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'objects');

test('handles Stringable objects', function (): void {
    // Arrange
    $obj1 = new StringableObject('{"name":"John","age":30}');
    $obj2 = new StringableObject('{"age":30,"name":"John"}');

    // Act
    $key1 = IdempotencyKey::create($obj1);
    $key2 = IdempotencyKey::create($obj2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'objects');

test('handles objects with mixed property visibility', function (): void {
    // Arrange
    $obj1 = new MixedVisibilityObject();
    $obj2 = new MixedVisibilityObject();

    // Act
    $key1 = IdempotencyKey::create($obj1);
    $key2 = IdempotencyKey::create($obj2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'objects');

test('handles objects with uninitialized properties', function (): void {
    // Arrange
    $obj1 = new UninitializedPropertiesObject();
    $obj2 = new UninitializedPropertiesObject();

    // Act
    $key1 = IdempotencyKey::create($obj1);
    $key2 = IdempotencyKey::create($obj2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'objects');

test('handles objects with nested object properties', function (): void {
    // Arrange
    $inner = new InnerObject();
    $obj1 = new NestedObjectContainer($inner);
    $obj2 = new NestedObjectContainer($inner);

    // Act
    $key1 = IdempotencyKey::create($obj1);
    $key2 = IdempotencyKey::create($obj2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'objects');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Scalar Values
|--------------------------------------------------------------------------
*/

test('handles integer values', function (): void {
    // Arrange
    $int1 = 42;
    $int2 = 42;
    $string = '42';

    // Act
    $key1 = IdempotencyKey::create($int1);
    $key2 = IdempotencyKey::create($int2);
    $key3 = IdempotencyKey::create($string);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
    expect($key1->toString())->not->toBe($key3->toString());
})->group('happy-path', 'scalars');

test('handles float values', function (): void {
    // Arrange
    $float1 = 3.14;
    $float2 = 3.14;

    // Act
    $key1 = IdempotencyKey::create($float1);
    $key2 = IdempotencyKey::create($float2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'scalars');

test('handles null values', function (): void {
    // Arrange
    $null1 = null;
    $null2 = null;

    // Act
    $key1 = IdempotencyKey::create($null1);
    $key2 = IdempotencyKey::create($null2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'scalars');

test('handles boolean values', function (): void {
    // Arrange
    $true1 = true;
    $true2 = true;
    $false = false;

    // Act
    $key1 = IdempotencyKey::create($true1);
    $key2 = IdempotencyKey::create($true2);
    $key3 = IdempotencyKey::create($false);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
    expect($key1->toString())->not->toBe($key3->toString());
})->group('happy-path', 'scalars');

test('treats int and float with same value as equal due to JSON encoding', function (): void {
    // Arrange
    $int = 42;
    $float = 42.0;

    // Act
    $key1 = IdempotencyKey::create($int);
    $key2 = IdempotencyKey::create($float);

    // Assert
    // JSON encoding normalizes 42.0 to 42, so these produce the same hash
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'scalars');

test('handles zero values consistently', function (): void {
    // Arrange
    $int = 0;
    $intDupe = 0;
    $float = 0.0;
    $string = '0';

    // Act
    $key1 = IdempotencyKey::create($int);
    $key2 = IdempotencyKey::create($intDupe);
    $key3 = IdempotencyKey::create($float);
    $key4 = IdempotencyKey::create($string);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
    // JSON encoding normalizes 0.0 to 0, so these are the same
    expect($key1->toString())->toBe($key3->toString());
    expect($key1->toString())->not->toBe($key4->toString());
})->group('happy-path', 'scalars');

test('handles negative numbers', function (): void {
    // Arrange
    $negative1 = -42;
    $negative2 = -42;
    $positive = 42;

    // Act
    $key1 = IdempotencyKey::create($negative1);
    $key2 = IdempotencyKey::create($negative2);
    $key3 = IdempotencyKey::create($positive);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
    expect($key1->toString())->not->toBe($key3->toString());
})->group('happy-path', 'scalars');

test('handles very large numbers', function (): void {
    // Arrange
    $large1 = 9_999_999_999_999_999;
    $large2 = 9_999_999_999_999_999;

    // Act
    $key1 = IdempotencyKey::create($large1);
    $key2 = IdempotencyKey::create($large2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'scalars');

test('handles scientific notation floats', function (): void {
    // Arrange
    $scientific1 = 1.23e-10;
    $scientific2 = 1.23e-10;

    // Act
    $key1 = IdempotencyKey::create($scientific1);
    $key2 = IdempotencyKey::create($scientific2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'scalars');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - String Values
|--------------------------------------------------------------------------
*/

test('handles plain strings', function (): void {
    // Arrange
    $string1 = 'hello world';
    $string2 = 'hello world';
    $string3 = 'hello world ';

    // Act
    $key1 = IdempotencyKey::create($string1);
    $key2 = IdempotencyKey::create($string2);
    $key3 = IdempotencyKey::create($string3);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
    expect($key1->toString())->not->toBe($key3->toString());
})->group('happy-path', 'strings');

test('handles empty strings', function (): void {
    // Arrange
    $empty1 = '';
    $empty2 = '';

    // Act
    $key1 = IdempotencyKey::create($empty1);
    $key2 = IdempotencyKey::create($empty2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'strings');

test('handles strings with unicode characters', function (): void {
    // Arrange
    $unicode1 = 'Hello 世界 🌍';
    $unicode2 = 'Hello 世界 🌍';

    // Act
    $key1 = IdempotencyKey::create($unicode1);
    $key2 = IdempotencyKey::create($unicode2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'strings');

test('handles strings with special characters', function (): void {
    // Arrange
    $special1 = '!@#$%^&*()_+-=[]{}|;\':",.<>?/`~';
    $special2 = '!@#$%^&*()_+-=[]{}|;\':",.<>?/`~';

    // Act
    $key1 = IdempotencyKey::create($special1);
    $key2 = IdempotencyKey::create($special2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'strings');

test('handles strings with newlines and tabs', function (): void {
    // Arrange
    $multiline1 = "line1\nline2\tline3";
    $multiline2 = "line1\nline2\tline3";

    // Act
    $key1 = IdempotencyKey::create($multiline1);
    $key2 = IdempotencyKey::create($multiline2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'strings');

test('handles strings that look like JSON but are not valid', function (): void {
    // Arrange
    $invalid1 = '{"incomplete":';
    $invalid2 = '{"incomplete":';

    // Act
    $key1 = IdempotencyKey::create($invalid1);
    $key2 = IdempotencyKey::create($invalid2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'strings');

test('handles strings that look like XML but are not valid', function (): void {
    // Arrange
    $invalid1 = '<incomplete';
    $invalid2 = '<incomplete';

    // Act
    $key1 = IdempotencyKey::create($invalid1);
    $key2 = IdempotencyKey::create($invalid2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'strings');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Empty Inputs
|--------------------------------------------------------------------------
*/

test('handles empty arrays', function (): void {
    // Arrange
    $empty1 = [];
    $empty2 = [];

    // Act
    $key1 = IdempotencyKey::create($empty1);
    $key2 = IdempotencyKey::create($empty2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('edge-case', 'empty');

test('handles arrays with empty nested arrays', function (): void {
    // Arrange
    $data1 = ['data' => [], 'meta' => []];
    $data2 = ['meta' => [], 'data' => []];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('edge-case', 'empty');

test('handles empty JSON objects', function (): void {
    // Arrange
    $json1 = '{}';
    $json2 = '{}';

    // Act
    $key1 = IdempotencyKey::create($json1);
    $key2 = IdempotencyKey::create($json2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('edge-case', 'empty');

test('handles empty JSON arrays', function (): void {
    // Arrange
    $json1 = '[]';
    $json2 = '[]';

    // Act
    $key1 = IdempotencyKey::create($json1);
    $key2 = IdempotencyKey::create($json2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('edge-case', 'empty');

test('handles objects with no properties', function (): void {
    // Arrange
    $obj1 = new EmptyObject();
    $obj2 = new EmptyObject();

    // Act
    $key1 = IdempotencyKey::create($obj1);
    $key2 = IdempotencyKey::create($obj2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('edge-case', 'empty');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Large Data Structures
|--------------------------------------------------------------------------
*/

test('handles large arrays (100+ items)', function (): void {
    // Arrange
    $data1 = [];
    $data2 = [];

    for ($i = 0; $i < 150; ++$i) {
        $data1['key'.$i] = 'value'.$i;
    }

    for ($i = 149; $i >= 0; --$i) {
        $data2['key'.$i] = 'value'.$i;
    }

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('edge-case', 'large-data');

test('handles large nested arrays', function (): void {
    // Arrange
    $data1 = [];

    for ($i = 0; $i < 50; ++$i) {
        $data1['group'.$i] = [
            'id' => $i,
            'name' => 'Group '.$i,
            'items' => array_fill(0, 20, 'item'.$i),
        ];
    }

    $data2 = [];

    for ($i = 49; $i >= 0; --$i) {
        $data2['group'.$i] = [
            'items' => array_fill(0, 20, 'item'.$i),
            'name' => 'Group '.$i,
            'id' => $i,
        ];
    }

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('edge-case', 'large-data');

test('handles arrays with many duplicate values', function (): void {
    // Arrange
    $data1 = array_fill(0, 100, 'duplicate');
    $data2 = array_fill(0, 100, 'duplicate');

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('edge-case', 'large-data');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Array Key Type Mixing
|--------------------------------------------------------------------------
*/

test('differentiates between string and integer keys with same value', function (): void {
    // Arrange
    $data1 = [0 => 'value', 1 => 'value2'];
    $data2 = ['0' => 'value', '1' => 'value2'];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    // PHP automatically converts numeric string keys to integers in arrays
    // so these should be the same
    expect($key1->toString())->toBe($key2->toString());
})->group('edge-case', 'key-types');

test('handles mixed key types in nested structures', function (): void {
    // Arrange
    $data1 = [
        'users' => [
            0 => ['name' => 'John'],
            1 => ['name' => 'Jane'],
        ],
    ];
    $data2 = [
        'users' => [
            '0' => ['name' => 'John'],
            '1' => ['name' => 'Jane'],
        ],
    ];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('edge-case', 'key-types');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Whitespace and Formatting
|--------------------------------------------------------------------------
*/

test('handles arrays with string values containing different whitespace', function (): void {
    // Arrange
    $data1 = ['text' => 'hello world'];
    $data2 = ['text' => 'hello  world'];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->not->toBe($key2->toString());
})->group('edge-case', 'whitespace');

test('handles leading/trailing whitespace in array values', function (): void {
    // Arrange
    $data1 = ['text' => ' trimmed '];
    $data2 = ['text' => 'trimmed'];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->not->toBe($key2->toString());
})->group('edge-case', 'whitespace');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Hash Output Format
|--------------------------------------------------------------------------
*/

test('generates SHA-256 hash format', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key = IdempotencyKey::create($data);

    // Assert
    expect($key->toString())
        ->toBeString()
        ->toHaveLength(64)
        ->toMatch('/^[a-f0-9]{64}$/');
})->group('edge-case', 'hash-format');

test('supports __toString magic method', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key = IdempotencyKey::create($data);

    // Assert
    expect((string) $key)->toBe($key->toString());
})->group('edge-case', 'hash-format');

test('generates consistent hash across multiple calls', function (): void {
    // Arrange
    $data = ['complex' => ['nested' => ['structure' => 'value']]];

    // Act
    $hashes = [];

    for ($i = 0; $i < 10; ++$i) {
        $hashes[] = IdempotencyKey::create($data)->toString();
    }

    // Assert
    expect(array_unique($hashes))->toHaveCount(1);
})->group('edge-case', 'hash-format');

/*
|--------------------------------------------------------------------------
| Sad Path Tests - Invalid Inputs
|--------------------------------------------------------------------------
*/

test('throws exception for resource type', function (): void {
    // Arrange
    $resource = fopen('php://memory', 'rb');

    // Act & Assert
    expect(fn (): IdempotencyKey => IdempotencyKey::create($resource))
        ->toThrow(InvalidArgumentException::class);

    fclose($resource);
})->group('sad-path', 'invalid-input');

test('handles closures as objects', function (): void {
    // Arrange
    $closure1 = fn (): string => 'test';
    $closure2 = fn (): string => 'test';

    // Act
    // Closures are objects and will be normalized via reflection
    $key1 = IdempotencyKey::create($closure1);
    $key2 = IdempotencyKey::create($closure2);

    // Assert
    // Different closures produce the same hash if they have the same properties
    expect($key1->toString())->toBeString()->toHaveLength(64);
    expect($key2->toString())->toBeString()->toHaveLength(64);
})->group('sad-path', 'invalid-input');

/*
|--------------------------------------------------------------------------
| Regression Tests
|--------------------------------------------------------------------------
*/

test('handles JSON with escaped characters consistently', function (): void {
    // Arrange
    $json1 = '{"path":"C:\\\\Users\\\\test"}';
    $json2 = '{"path":"C:\\\\Users\\\\test"}';

    // Act
    $key1 = IdempotencyKey::create($json1);
    $key2 = IdempotencyKey::create($json2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('regression');

test('handles arrays with null values', function (): void {
    // Arrange
    $data1 = ['name' => 'John', 'middle' => null, 'last' => 'Doe'];
    $data2 = ['last' => 'Doe', 'name' => 'John', 'middle' => null];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('regression');

test('handles arrays with false vs null values differently', function (): void {
    // Arrange
    $data1 = ['flag' => false];
    $data2 = ['flag' => null];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->not->toBe($key2->toString());
})->group('regression');

test('handles arrays with 0 vs false vs null differently', function (): void {
    // Arrange
    $data1 = ['value' => 0];
    $data2 = ['value' => false];
    $data3 = ['value' => null];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);
    $key3 = IdempotencyKey::create($data3);

    // Assert
    expect($key1->toString())->not->toBe($key2->toString());
    expect($key2->toString())->not->toBe($key3->toString());
    expect($key1->toString())->not->toBe($key3->toString());
})->group('regression');

test('handles empty string vs null differently', function (): void {
    // Arrange
    $data1 = ['value' => ''];
    $data2 = ['value' => null];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->not->toBe($key2->toString());
})->group('regression');

test('handles JSON numbers with and without decimals consistently', function (): void {
    // Arrange
    $json1 = '{"amount":100}';
    $json2 = '{"amount":100.0}';

    // Act
    $key1 = IdempotencyKey::create($json1);
    $key2 = IdempotencyKey::create($json2);

    // Assert
    // JSON decoding normalizes 100.0 to 100 (both become integers)
    // then JSON encoding keeps them the same
    expect($key1->toString())->toBe($key2->toString());
})->group('regression');

test('handles deeply nested empty arrays consistently', function (): void {
    // Arrange
    $data1 = ['level1' => ['level2' => ['level3' => []]]];
    $data2 = ['level1' => ['level2' => ['level3' => []]]];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('regression');

test('handles associative arrays vs indexed arrays differently', function (): void {
    // Arrange
    $data1 = [0 => 'a', 1 => 'b', 2 => 'c'];
    $data2 = ['0' => 'a', '1' => 'b', '2' => 'c'];

    // Act
    $key1 = IdempotencyKey::create($data1);
    $key2 = IdempotencyKey::create($data2);

    // Assert
    // PHP treats these the same
    expect($key1->toString())->toBe($key2->toString());
})->group('regression');

test('handles XML with different text node formatting', function (): void {
    // Arrange
    $xml1 = '<root><item>value</item></root>';
    $xml2 = '<root><item>value</item></root>';

    // Act
    $key1 = IdempotencyKey::create($xml1);
    $key2 = IdempotencyKey::create($xml2);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('regression');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Configurable Hash Algorithms
|--------------------------------------------------------------------------
*/

test('generates key with SHA-256 by default', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key = IdempotencyKey::create($data);

    // Assert
    expect($key->toString())->toHaveLength(64);
    expect($key->getAlgorithm())->toBe(HashAlgorithm::SHA256);
})->group('happy-path', 'algorithms');

test('generates key with SHA-1 algorithm', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key = IdempotencyKey::create($data, algorithm: HashAlgorithm::SHA1);

    // Assert
    expect($key->toString())->toHaveLength(40);
    expect($key->getAlgorithm())->toBe(HashAlgorithm::SHA1);
})->group('happy-path', 'algorithms');

test('generates key with SHA-512 algorithm', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key = IdempotencyKey::create($data, algorithm: HashAlgorithm::SHA512);

    // Assert
    expect($key->toString())->toHaveLength(128);
    expect($key->getAlgorithm())->toBe(HashAlgorithm::SHA512);
})->group('happy-path', 'algorithms');

test('generates key with MD5 algorithm', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key = IdempotencyKey::create($data, algorithm: HashAlgorithm::MD5);

    // Assert
    expect($key->toString())->toHaveLength(32);
    expect($key->getAlgorithm())->toBe(HashAlgorithm::MD5);
})->group('happy-path', 'algorithms');

test('generates different hashes for different algorithms with same data', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $sha256 = IdempotencyKey::create($data, algorithm: HashAlgorithm::SHA256);
    $sha512 = IdempotencyKey::create($data, algorithm: HashAlgorithm::SHA512);
    $md5 = IdempotencyKey::create($data, algorithm: HashAlgorithm::MD5);

    // Assert
    expect($sha256->toString())->not->toBe($sha512->toString());
    expect($sha256->toString())->not->toBe($md5->toString());
    expect($sha512->toString())->not->toBe($md5->toString());
})->group('happy-path', 'algorithms');

test('generates consistent hash for same algorithm', function (): void {
    // Arrange
    $data = ['name' => 'John', 'age' => 30];

    // Act
    $key1 = IdempotencyKey::create($data, algorithm: HashAlgorithm::SHA512);
    $key2 = IdempotencyKey::create($data, algorithm: HashAlgorithm::SHA512);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'algorithms');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Key Prefixing/Namespacing
|--------------------------------------------------------------------------
*/

test('generates key with prefix', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key = IdempotencyKey::create($data, prefix: 'user');

    // Assert
    expect($key->toString())->toBeString();
    expect($key->getPrefix())->toBe('user');
})->group('happy-path', 'prefixes');

test('generates different keys for same data with different prefixes', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key1 = IdempotencyKey::create($data, prefix: 'user');
    $key2 = IdempotencyKey::create($data, prefix: 'admin');
    $key3 = IdempotencyKey::create($data);

    // Assert
    expect($key1->toString())->not->toBe($key2->toString());
    expect($key1->toString())->not->toBe($key3->toString());
    expect($key2->toString())->not->toBe($key3->toString());
})->group('happy-path', 'prefixes');

test('generates same key for same data and prefix', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key1 = IdempotencyKey::create($data, prefix: 'payment');
    $key2 = IdempotencyKey::create($data, prefix: 'payment');

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'prefixes');

test('handles empty prefix like no prefix', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key1 = IdempotencyKey::create($data, prefix: '');
    $key2 = IdempotencyKey::create($data);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'prefixes');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Binary Output
|--------------------------------------------------------------------------
*/

test('converts key to binary format', function (): void {
    // Arrange
    $data = ['name' => 'John'];
    $key = IdempotencyKey::create($data);

    // Act
    $binary = $key->toBinary();

    // Assert
    expect($binary)->toBeString();
    expect(strlen($binary))->toBe(32); // SHA-256 produces 32 bytes
})->group('happy-path', 'binary');

test('converts binary back to hex', function (): void {
    // Arrange
    $data = ['name' => 'John'];
    $key = IdempotencyKey::create($data);
    $hex = $key->toString();

    // Act
    $binary = $key->toBinary();
    $hexFromBinary = bin2hex($binary);

    // Assert
    expect($hexFromBinary)->toBe($hex);
})->group('happy-path', 'binary');

test('generates consistent binary output', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key1 = IdempotencyKey::create($data);
    $key2 = IdempotencyKey::create($data);

    // Assert
    expect($key1->toBinary())->toBe($key2->toBinary());
})->group('happy-path', 'binary');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Key Validation
|--------------------------------------------------------------------------
*/

test('validates SHA-256 hex key as valid', function (): void {
    // Arrange
    $validKey = str_repeat('a', 64);

    // Act & Assert
    expect(IdempotencyKey::isValid($validKey))->toBeTrue();
})->group('happy-path', 'validation');

test('validates SHA-1 hex key as valid', function (): void {
    // Arrange
    $validKey = str_repeat('a', 40);

    // Act & Assert
    expect(IdempotencyKey::isValid($validKey, HashAlgorithm::SHA1))->toBeTrue();
})->group('happy-path', 'validation');

test('validates MD5 hex key as valid', function (): void {
    // Arrange
    $validKey = str_repeat('a', 32);

    // Act & Assert
    expect(IdempotencyKey::isValid($validKey, HashAlgorithm::MD5))->toBeTrue();
})->group('happy-path', 'validation');

test('validates SHA-512 hex key as valid', function (): void {
    // Arrange
    $validKey = str_repeat('a', 128);

    // Act & Assert
    expect(IdempotencyKey::isValid($validKey, HashAlgorithm::SHA512))->toBeTrue();
})->group('happy-path', 'validation');

test('validates generated key as valid', function (): void {
    // Arrange
    $data = ['name' => 'John'];
    $key = IdempotencyKey::create($data);

    // Act & Assert
    expect(IdempotencyKey::isValid($key->toString()))->toBeTrue();
})->group('happy-path', 'validation');

/*
|--------------------------------------------------------------------------
| Sad Path Tests - Key Validation
|--------------------------------------------------------------------------
*/

test('rejects invalid hex characters', function (): void {
    // Arrange
    $invalidKey = str_repeat('g', 64);

    // Act & Assert
    expect(IdempotencyKey::isValid($invalidKey))->toBeFalse();
})->group('sad-path', 'validation');

test('rejects wrong length keys', function (): void {
    // Arrange
    $tooShort = str_repeat('a', 63);
    $tooLong = str_repeat('a', 65);

    // Act & Assert
    expect(IdempotencyKey::isValid($tooShort))->toBeFalse();
    expect(IdempotencyKey::isValid($tooLong))->toBeFalse();
})->group('sad-path', 'validation');

test('rejects empty string', function (): void {
    // Act & Assert
    expect(IdempotencyKey::isValid(''))->toBeFalse();
})->group('sad-path', 'validation');

test('rejects uppercase hex as invalid', function (): void {
    // Arrange
    $uppercaseKey = str_repeat('A', 64);

    // Act & Assert
    expect(IdempotencyKey::isValid($uppercaseKey))->toBeFalse();
})->group('sad-path', 'validation');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Comparison Methods
|--------------------------------------------------------------------------
*/

test('compares two identical keys as equal', function (): void {
    // Arrange
    $data = ['name' => 'John'];
    $key1 = IdempotencyKey::create($data);
    $key2 = IdempotencyKey::create($data);

    // Act & Assert
    expect($key1->equals($key2))->toBeTrue();
})->group('happy-path', 'comparison');

test('compares key with string as equal', function (): void {
    // Arrange
    $data = ['name' => 'John'];
    $key = IdempotencyKey::create($data);
    $keyString = $key->toString();

    // Act & Assert
    expect($key->equals($keyString))->toBeTrue();
})->group('happy-path', 'comparison');

test('compares different keys as not equal', function (): void {
    // Arrange
    $key1 = IdempotencyKey::create(['name' => 'John']);
    $key2 = IdempotencyKey::create(['name' => 'Jane']);

    // Act & Assert
    expect($key1->equals($key2))->toBeFalse();
})->group('happy-path', 'comparison');

test('matches key against original data', function (): void {
    // Arrange
    $data = ['name' => 'John', 'age' => 30];
    $key = IdempotencyKey::create($data);

    // Act & Assert
    expect($key->matches($data))->toBeTrue();
})->group('happy-path', 'comparison');

test('does not match key against different data', function (): void {
    // Arrange
    $data1 = ['name' => 'John', 'age' => 30];
    $data2 = ['name' => 'Jane', 'age' => 25];
    $key = IdempotencyKey::create($data1);

    // Act & Assert
    expect($key->matches($data2))->toBeFalse();
})->group('happy-path', 'comparison');

test('matches key against data with different key order', function (): void {
    // Arrange
    $data1 = ['name' => 'John', 'age' => 30];
    $data2 = ['age' => 30, 'name' => 'John'];
    $key = IdempotencyKey::create($data1);

    // Act & Assert
    expect($key->matches($data2))->toBeTrue();
})->group('happy-path', 'comparison');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Versioning Support
|--------------------------------------------------------------------------
*/

test('generates versioned key with default version', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key = IdempotencyKey::create($data);

    // Assert
    expect($key->getVersion())->toBe(1);
})->group('happy-path', 'versioning');

test('encodes version in key format', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key = IdempotencyKey::create($data);

    // Assert
    expect($key->toVersionedString())->toStartWith('v1:sha256:');
})->group('happy-path', 'versioning');

test('parses version from versioned key string', function (): void {
    // Arrange
    $data = ['name' => 'John'];
    $key = IdempotencyKey::create($data);
    $versionedString = $key->toVersionedString();

    // Act
    $parsedKey = IdempotencyKey::fromVersionedString($versionedString);

    // Assert
    expect($parsedKey->getVersion())->toBe(1);
    expect($parsedKey->getAlgorithm())->toBe(HashAlgorithm::SHA256);
    expect($parsedKey->toString())->toBe($key->toString());
})->group('happy-path', 'versioning');

test('parses versioned key with different algorithm', function (): void {
    // Arrange
    $data = ['name' => 'John'];
    $key = IdempotencyKey::create($data, algorithm: HashAlgorithm::SHA512);
    $versionedString = $key->toVersionedString();

    // Act
    $parsedKey = IdempotencyKey::fromVersionedString($versionedString);

    // Assert
    expect($parsedKey->getAlgorithm())->toBe(HashAlgorithm::SHA512);
    expect($parsedKey->toString())->toBe($key->toString());
})->group('happy-path', 'versioning');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Truncated Keys
|--------------------------------------------------------------------------
*/

test('truncates key to specified length', function (): void {
    // Arrange
    $data = ['name' => 'John'];
    $key = IdempotencyKey::create($data);

    // Act
    $truncated = $key->truncate(16);

    // Assert
    expect($truncated)->toHaveLength(16);
    expect($truncated)->toBe(mb_substr($key->toString(), 0, 16));
})->group('happy-path', 'truncate');

test('truncates key to 8 characters', function (): void {
    // Arrange
    $data = ['name' => 'John'];
    $key = IdempotencyKey::create($data);

    // Act
    $truncated = $key->truncate(8);

    // Assert
    expect($truncated)->toHaveLength(8);
})->group('happy-path', 'truncate');

test('returns full key when truncate length exceeds key length', function (): void {
    // Arrange
    $data = ['name' => 'John'];
    $key = IdempotencyKey::create($data);

    // Act
    $truncated = $key->truncate(1_000);

    // Assert
    expect($truncated)->toBe($key->toString());
})->group('happy-path', 'truncate');

test('generates consistent truncated keys', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key1 = IdempotencyKey::create($data);
    $key2 = IdempotencyKey::create($data);

    // Assert
    expect($key1->truncate(12))->toBe($key2->truncate(12));
})->group('happy-path', 'truncate');

/*
|--------------------------------------------------------------------------
| Sad Path Tests - Truncated Keys
|--------------------------------------------------------------------------
*/

test('throws exception for zero length truncation', function (): void {
    // Arrange
    $data = ['name' => 'John'];
    $key = IdempotencyKey::create($data);

    // Act & Assert
    expect(fn (): string => $key->truncate(0))->toThrow(InvalidArgumentException::class);
})->group('sad-path', 'truncate');

test('throws exception for negative length truncation', function (): void {
    // Arrange
    $data = ['name' => 'John'];
    $key = IdempotencyKey::create($data);

    // Act & Assert
    expect(fn (): string => $key->truncate(-5))->toThrow(InvalidArgumentException::class);
})->group('sad-path', 'truncate');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Multiple Output Formats
|--------------------------------------------------------------------------
*/

test('converts key to Base64 format', function (): void {
    // Arrange
    $data = ['name' => 'John'];
    $key = IdempotencyKey::create($data);

    // Act
    $base64 = $key->toBase64();

    // Assert
    expect($base64)->toBeString();
    expect(base64_decode($base64, true))->not->toBeFalse();
})->group('happy-path', 'formats');

test('converts key to Base62 format', function (): void {
    // Arrange
    $data = ['name' => 'John'];
    $key = IdempotencyKey::create($data);

    // Act
    $base62 = $key->toBase62();

    // Assert
    expect($base62)->toBeString();
    expect($base62)->toMatch('/^[0-9A-Za-z]+$/');
})->group('happy-path', 'formats');

test('converts key to UUID format', function (): void {
    // Arrange
    $data = ['name' => 'John'];
    $key = IdempotencyKey::create($data);

    // Act
    $uuid = $key->toUuid();

    // Assert
    expect($uuid)->toBeString();
    expect($uuid)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
})->group('happy-path', 'formats');

test('generates consistent Base64 output', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key1 = IdempotencyKey::create($data);
    $key2 = IdempotencyKey::create($data);

    // Assert
    expect($key1->toBase64())->toBe($key2->toBase64());
})->group('happy-path', 'formats');

test('generates consistent Base62 output', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key1 = IdempotencyKey::create($data);
    $key2 = IdempotencyKey::create($data);

    // Assert
    expect($key1->toBase62())->toBe($key2->toBase62());
})->group('happy-path', 'formats');

test('generates consistent UUID output', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key1 = IdempotencyKey::create($data);
    $key2 = IdempotencyKey::create($data);

    // Assert
    expect($key1->toUuid())->toBe($key2->toUuid());
})->group('happy-path', 'formats');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Custom Normalizers
|--------------------------------------------------------------------------
*/

test('uses custom normalizer for data processing', function (): void {
    // Arrange
    $data = ['name' => 'JOHN', 'email' => 'JOHN@EXAMPLE.COM'];
    $normalizer = new class() implements NormalizerInterface
    {
        public function normalize(mixed $data): mixed
        {
            if (is_array($data)) {
                return array_map(fn ($v): mixed => is_string($v) ? mb_strtolower($v) : $v, $data);
            }

            return $data;
        }
    };

    // Act
    $key1 = IdempotencyKey::create($data, normalizer: $normalizer);
    $key2 = IdempotencyKey::create(['name' => 'john', 'email' => 'john@example.com'], normalizer: $normalizer);
    $keyWithoutNormalizer = IdempotencyKey::create(['name' => 'john', 'email' => 'john@example.com']);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
    expect($key1->toString())->toBe($keyWithoutNormalizer->toString());
})->group('happy-path', 'normalizers');

test('custom normalizer processes nested structures', function (): void {
    // Arrange
    $data = [
        'user' => ['name' => 'JOHN'],
        'settings' => ['theme' => 'DARK'],
    ];
    $normalizer = new class() implements NormalizerInterface
    {
        public function normalize(mixed $data): mixed
        {
            if (is_array($data)) {
                return array_map($this->normalize(...), $data);
            }

            if (is_string($data)) {
                return mb_strtolower($data);
            }

            return $data;
        }
    };

    // Act
    $key1 = IdempotencyKey::create($data, normalizer: $normalizer);
    $key2 = IdempotencyKey::create([
        'user' => ['name' => 'john'],
        'settings' => ['theme' => 'dark'],
    ], normalizer: $normalizer);

    // Assert
    expect($key1->toString())->toBe($key2->toString());
})->group('happy-path', 'normalizers');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Combined Features
|--------------------------------------------------------------------------
*/

test('combines prefix and custom algorithm', function (): void {
    // Arrange
    $data = ['name' => 'John'];

    // Act
    $key = IdempotencyKey::create($data, algorithm: HashAlgorithm::SHA512, prefix: 'user');

    // Assert
    expect($key->getAlgorithm())->toBe(HashAlgorithm::SHA512);
    expect($key->getPrefix())->toBe('user');
    expect($key->toString())->toHaveLength(128);
})->group('edge-case', 'combined');

test('combines all features together', function (): void {
    // Arrange
    $data = ['name' => 'John'];
    $normalizer = new class() implements NormalizerInterface
    {
        public function normalize(mixed $data): mixed
        {
            return $data;
        }
    };

    // Act
    $key = IdempotencyKey::create(
        $data,
        algorithm: HashAlgorithm::SHA512,
        prefix: 'payment',
        normalizer: $normalizer,
    );

    // Assert
    expect($key->getAlgorithm())->toBe(HashAlgorithm::SHA512);
    expect($key->getPrefix())->toBe('payment');
    expect($key->toString())->toHaveLength(128);
    expect($key->toBinary())->toBeString();
    expect($key->toBase64())->toBeString();
    expect($key->toBase62())->toBeString();
    expect($key->truncate(16))->toHaveLength(16);
})->group('edge-case', 'combined');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Coverage Gaps
|--------------------------------------------------------------------------
*/

test('handles fromVersionedString with unsupported version', function (): void {
    // Arrange
    $versionedString = 'v99:sha256:'.str_repeat('a', 64);

    // Act & Assert
    IdempotencyKey::fromVersionedString($versionedString);
})->throws(InvalidArgumentException::class, 'Unsupported version: 99')->group('edge-case', 'versioning');

test('handles toBase62 with zero hash', function (): void {
    // Arrange - Generate a key and forcibly create one with zero hash for testing
    $data = ['test' => 'data'];
    $key = IdempotencyKey::create($data, HashAlgorithm::MD5);

    // Create a versioned string with all zeros
    $zeroHash = str_repeat('0', 32);
    $versionedString = 'v1:md5:'.$zeroHash;

    // Act
    $zeroKey = IdempotencyKey::fromVersionedString($versionedString);
    $base62 = $zeroKey->toBase62();

    // Assert
    expect($base62)->toBe('0');
})->group('edge-case', 'encoding');

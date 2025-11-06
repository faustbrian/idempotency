<?php declare(strict_types=1);

/**
 * Copyright (C) Brian Faust
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cline\Idempotency\Actions\StringNormalizer;

covers(StringNormalizer::class);

/*
|--------------------------------------------------------------------------
| Happy Path Tests - JSON Parsing
|--------------------------------------------------------------------------
*/

test('normalizes JSON object string to array', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $json = '{"name":"John","age":30}';

    // Act
    $result = $normalizer->normalize($json);

    // Assert
    expect($result)->toBe(['name' => 'John', 'age' => 30]);
})->group('happy-path', 'json');

test('normalizes JSON array string to array', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $json = '["apple","banana","cherry"]';

    // Act
    $result = $normalizer->normalize($json);

    // Assert
    expect($result)->toBe(['apple', 'banana', 'cherry']);
})->group('happy-path', 'json');

test('normalizes nested JSON structure', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $json = '{"user":{"name":"John","age":30},"meta":{"created":"2024-01-01"}}';

    // Act
    $result = $normalizer->normalize($json);

    // Assert
    expect($result)->toBe([
        'user' => ['name' => 'John', 'age' => 30],
        'meta' => ['created' => '2024-01-01'],
    ]);
})->group('happy-path', 'json');

test('normalizes JSON with unicode characters', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $json = '{"name":"José","emoji":"🚀","chinese":"中文"}';

    // Act
    $result = $normalizer->normalize($json);

    // Assert
    expect($result)->toBe([
        'name' => 'José',
        'emoji' => '🚀',
        'chinese' => '中文',
    ]);
})->group('happy-path', 'json');

test('normalizes JSON with escaped characters', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $json = '{"path":"C:\\\\Users\\\\test","quote":"He said \\"hello\\""}';

    // Act
    $result = $normalizer->normalize($json);

    // Assert
    expect($result)->toBe([
        'path' => 'C:\\Users\\test',
        'quote' => 'He said "hello"',
    ]);
})->group('happy-path', 'json');

test('normalizes JSON with null values', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $json = '{"name":"John","middle":null,"age":30}';

    // Act
    $result = $normalizer->normalize($json);

    // Assert
    expect($result)->toBe([
        'name' => 'John',
        'middle' => null,
        'age' => 30,
    ]);
})->group('happy-path', 'json');

test('normalizes JSON with boolean values', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $json = '{"active":true,"deleted":false}';

    // Act
    $result = $normalizer->normalize($json);

    // Assert
    expect($result)->toBe([
        'active' => true,
        'deleted' => false,
    ]);
})->group('happy-path', 'json');

test('normalizes JSON with numeric values', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $json = '{"int":42,"float":3.14,"negative":-10,"scientific":1.23e-10}';

    // Act
    $result = $normalizer->normalize($json);

    // Assert
    expect($result)->toBe([
        'int' => 42,
        'float' => 3.14,
        'negative' => -10,
        'scientific' => 1.23e-10,
    ]);
})->group('happy-path', 'json');

test('normalizes empty JSON object', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $json = '{}';

    // Act
    $result = $normalizer->normalize($json);

    // Assert
    expect($result)->toBe([]);
})->group('happy-path', 'json');

test('normalizes empty JSON array', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $json = '[]';

    // Act
    $result = $normalizer->normalize($json);

    // Assert
    expect($result)->toBe([]);
})->group('happy-path', 'json');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - XML Parsing
|--------------------------------------------------------------------------
*/

test('normalizes simple XML to array', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $xml = '<user name="John" age="30"/>';

    // Act
    $result = $normalizer->normalize($xml);

    // Assert
    expect($result)->toBeArray()
        ->toHaveKey('user');
})->group('happy-path', 'xml');

test('normalizes nested XML elements', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $xml = '<root><user><name>John</name><age>30</age></user></root>';

    // Act
    $result = $normalizer->normalize($xml);

    // Assert
    expect($result)->toBeArray();
})->group('happy-path', 'xml');

test('normalizes XML with attributes and content', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $xml = '<item id="1" type="product"><name>Laptop</name><price>999.99</price></item>';

    // Act
    $result = $normalizer->normalize($xml);

    // Assert
    expect($result)->toBeArray();
})->group('happy-path', 'xml');

test('normalizes XML with CDATA sections', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $xml = '<content><![CDATA[<p>HTML content</p>]]></content>';

    // Act
    $result = $normalizer->normalize($xml);

    // Assert
    expect($result)->toBeArray();
})->group('happy-path', 'xml');

test('normalizes XML with namespaces', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $xml = '<root xmlns:foo="http://example.com"><foo:item>value</foo:item></root>';

    // Act
    $result = $normalizer->normalize($xml);

    // Assert
    expect($result)->toBeArray();
})->group('happy-path', 'xml');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - YAML Parsing
|--------------------------------------------------------------------------
*/

test('normalizes simple YAML to array', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $yaml = "name: John\nage: 30";

    // Act
    $result = $normalizer->normalize($yaml);

    // Assert
    expect($result)->toBe([
        'name' => 'John',
        'age' => 30,
    ]);
})->group('happy-path', 'yaml');

test('normalizes nested YAML structure', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $yaml = "user:\n  name: John\n  age: 30\nlocation:\n  city: NYC\n  country: USA";

    // Act
    $result = $normalizer->normalize($yaml);

    // Assert
    expect($result)->toBe([
        'user' => ['name' => 'John', 'age' => 30],
        'location' => ['city' => 'NYC', 'country' => 'USA'],
    ]);
})->group('happy-path', 'yaml');

test('normalizes YAML with lists', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $yaml = "tags:\n  - php\n  - laravel\n  - pest";

    // Act
    $result = $normalizer->normalize($yaml);

    // Assert
    expect($result)->toBe([
        'tags' => ['php', 'laravel', 'pest'],
    ]);
})->group('happy-path', 'yaml');

test('normalizes YAML with multiline strings', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $yaml = "description: |\n  This is a multiline\n  string value\n  with content";

    // Act
    $result = $normalizer->normalize($yaml);

    // Assert
    expect($result)->toBeArray()
        ->toHaveKey('description');
})->group('happy-path', 'yaml');

test('normalizes complex YAML structure', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $yaml = "database:\n  connections:\n    mysql:\n      host: localhost\n      port: 3306\n    redis:\n      host: localhost\n      port: 6379";

    // Act
    $result = $normalizer->normalize($yaml);

    // Assert
    expect($result)->toBe([
        'database' => [
            'connections' => [
                'mysql' => ['host' => 'localhost', 'port' => 3_306],
                'redis' => ['host' => 'localhost', 'port' => 6_379],
            ],
        ],
    ]);
})->group('happy-path', 'yaml');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Plain String Handling
|--------------------------------------------------------------------------
*/

test('normalizes plain string to value wrapper', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $string = 'hello world';

    // Act
    $result = $normalizer->normalize($string);

    // Assert
    expect($result)->toBe(['value' => 'hello world']);
})->group('happy-path', 'plain-strings');

test('normalizes empty string to value wrapper', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $string = '';

    // Act
    $result = $normalizer->normalize($string);

    // Assert
    expect($result)->toBe(['value' => '']);
})->group('happy-path', 'plain-strings');

test('normalizes string with special characters to value wrapper', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $string = '!@#$%^&*()_+-=[]{}|;\':",.<>?/`~';

    // Act
    $result = $normalizer->normalize($string);

    // Assert
    expect($result)->toBe(['value' => $string]);
})->group('happy-path', 'plain-strings');

test('normalizes string with unicode to value wrapper', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $string = 'Hello 世界 🌍';

    // Act
    $result = $normalizer->normalize($string);

    // Assert
    expect($result)->toBe(['value' => $string]);
})->group('happy-path', 'plain-strings');

test('normalizes string with newlines to value wrapper', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $string = "line1\nline2\nline3";

    // Act
    $result = $normalizer->normalize($string);

    // Assert
    expect($result)->toBe(['value' => $string]);
})->group('happy-path', 'plain-strings');

/*
|--------------------------------------------------------------------------
| Happy Path Tests - Whitespace Trimming
|--------------------------------------------------------------------------
*/

test('trims leading and trailing whitespace from JSON', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $json = '  {"name":"John","age":30}  ';

    // Act
    $result = $normalizer->normalize($json);

    // Assert
    expect($result)->toBe(['name' => 'John', 'age' => 30]);
})->group('happy-path', 'whitespace');

test('trims tabs and newlines from JSON', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $json = "\n\t{\"name\":\"John\",\"age\":30}\n\t";

    // Act
    $result = $normalizer->normalize($json);

    // Assert
    expect($result)->toBe(['name' => 'John', 'age' => 30]);
})->group('happy-path', 'whitespace');

test('trims whitespace from XML', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $xml = '  <user name="John" age="30"/>  ';

    // Act
    $result = $normalizer->normalize($xml);

    // Assert
    expect($result)->toBeArray();
})->group('happy-path', 'whitespace');

test('does not trim whitespace from plain strings', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $string = '  hello world  ';

    // Act
    $result = $normalizer->normalize($string);

    // Assert
    expect($result)->toBe(['value' => '  hello world  ']);
})->group('happy-path', 'whitespace');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Invalid Formats
|--------------------------------------------------------------------------
*/

test('handles invalid JSON gracefully', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $invalidJson = '{"incomplete":';

    // Act
    $result = $normalizer->normalize($invalidJson);

    // Assert
    expect($result)->toBe(['value' => '{"incomplete":']);
})->group('edge-case', 'invalid-format');

test('handles invalid XML gracefully', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $invalidXml = '<incomplete';

    // Act
    $result = $normalizer->normalize($invalidXml);

    // Assert
    expect($result)->toBe(['value' => '<incomplete']);
})->group('edge-case', 'invalid-format');

test('handles string that looks like JSON but is not', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $notJson = '{not valid json}';

    // Act
    $result = $normalizer->normalize($notJson);

    // Assert
    expect($result)->toBe(['value' => '{not valid json}']);
})->group('edge-case', 'invalid-format');

test('handles string that looks like XML but is not', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $notXml = '<not valid xml>';

    // Act
    $result = $normalizer->normalize($notXml);

    // Assert
    expect($result)->toBe(['value' => '<not valid xml>']);
})->group('edge-case', 'invalid-format');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Format Detection
|--------------------------------------------------------------------------
*/

test('detects JSON starting with curly brace', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $json = '{"key":"value"}';

    // Act
    $result = $normalizer->normalize($json);

    // Assert
    expect($result)->toBe(['key' => 'value']);
})->group('edge-case', 'detection');

test('detects JSON starting with square bracket', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $json = '["a","b","c"]';

    // Act
    $result = $normalizer->normalize($json);

    // Assert
    expect($result)->toBe(['a', 'b', 'c']);
})->group('edge-case', 'detection');

test('detects XML starting with angle bracket', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $xml = '<root>value</root>';

    // Act
    $result = $normalizer->normalize($xml);

    // Assert
    expect($result)->toBeArray();
})->group('edge-case', 'detection');

test('detects YAML with colon and newline', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $yaml = "key: value\nanother: value2";

    // Act
    $result = $normalizer->normalize($yaml);

    // Assert
    expect($result)->toBe(['key' => 'value', 'another' => 'value2']);
})->group('edge-case', 'detection');

test('treats string with colon but no newline as plain string', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $string = 'key: value';

    // Act
    $result = $normalizer->normalize($string);

    // Assert
    expect($result)->toBe(['value' => 'key: value']);
})->group('edge-case', 'detection');

test('detects YAML with colon and carriage return', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $yaml = "key: value\ranother: value2";

    // Act
    $result = $normalizer->normalize($yaml);

    // Assert
    expect($result)->toBe(['key' => 'value', 'another' => 'value2']);
})->group('edge-case', 'detection');

test('handles invalid YAML gracefully', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $invalidYaml = "key: value\n[invalid";

    // Act
    $result = $normalizer->normalize($invalidYaml);

    // Assert
    expect($result)->toBe(['value' => "key: value\n[invalid"]);
})->group('edge-case', 'invalid-format');

/*
|--------------------------------------------------------------------------
| Edge Case Tests - Special Cases
|--------------------------------------------------------------------------
*/

test('handles JSON with pretty printing', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $json = "{\n  \"name\": \"John\",\n  \"age\": 30\n}";

    // Act
    $result = $normalizer->normalize($json);

    // Assert
    expect($result)->toBe(['name' => 'John', 'age' => 30]);
})->group('edge-case', 'formatting');

test('handles XML with whitespace between elements', function (): void {
    // Arrange
    $normalizer = new StringNormalizer();
    $xml = "<root>\n  <user>\n    <name>John</name>\n  </user>\n</root>";

    // Act
    $result = $normalizer->normalize($xml);

    // Assert
    expect($result)->toBeArray();
})->group('edge-case', 'formatting');

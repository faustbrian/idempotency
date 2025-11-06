# Supported Formats

The `IdempotencyKey` class automatically detects and normalizes various data formats. All formats are converted to a canonical representation before hashing.

## Arrays

Both associative and indexed arrays are supported:

```php
use Cline\Idempotency\IdempotencyKey;

// Associative arrays
$key = IdempotencyKey::create(['name' => 'John', 'age' => 30]);

// Indexed arrays
$key = IdempotencyKey::create(['apple', 'banana', 'cherry']);

// Mixed arrays
$key = IdempotencyKey::create([
    'items' => ['apple', 'banana'],
    'count' => 2,
]);
```

## Objects

Standard PHP objects are converted to arrays:

```php
$obj = new stdClass();
$obj->name = 'John';
$obj->age = 30;

$key = IdempotencyKey::create($obj);

// Produces same key as array
$same = IdempotencyKey::create(['name' => 'John', 'age' => 30]);
$key->equals($same); // true
```

## JSON Strings

JSON is automatically detected and parsed:

```php
$json = '{"name":"John","age":30}';
$key = IdempotencyKey::create($json);

// Key order in JSON doesn't matter
$json2 = '{"age":30,"name":"John"}';
IdempotencyKey::create($json)->equals(
    IdempotencyKey::create($json2)
); // true

// Arrays
$jsonArray = '["apple","banana","cherry"]';
IdempotencyKey::create($jsonArray);

// Nested structures
$nested = '{
    "user": {"name":"John","age":30},
    "location": {"city":"NYC","country":"USA"}
}';
IdempotencyKey::create($nested);
```

## XML Strings

XML is parsed using the Saloon XML Wrangler:

```php
// Simple XML
$xml = '<user name="John" age="30"/>';
$key = IdempotencyKey::create($xml);

// Attribute order doesn't matter
$xml2 = '<user age="30" name="John"/>';
IdempotencyKey::create($xml)->equals(
    IdempotencyKey::create($xml2)
); // true

// Nested XML
$nested = '
<data>
    <user>
        <name>John</name>
        <age>30</age>
    </user>
    <location>
        <city>NYC</city>
        <country>USA</country>
    </location>
</data>
';
IdempotencyKey::create($nested);
```

## YAML Strings

YAML is parsed using Symfony YAML component:

```php
$yaml = <<<YAML
user:
  name: John
  age: 30
location:
  city: NYC
  country: USA
YAML;

$key = IdempotencyKey::create($yaml);

// Key order doesn't matter in YAML either
$yaml2 = <<<YAML
location:
  country: USA
  city: NYC
user:
  age: 30
  name: John
YAML;

IdempotencyKey::create($yaml)->equals(
    IdempotencyKey::create($yaml2)
); // true
```

## Scalars

Scalar values are automatically wrapped:

```php
// Strings
$key = IdempotencyKey::create('plain string');

// Integers
$key = IdempotencyKey::create(42);

// Floats
$key = IdempotencyKey::create(3.14);

// Booleans
$key = IdempotencyKey::create(true);
$key = IdempotencyKey::create(false);

// Null
$key = IdempotencyKey::create(null);
```

Note: Plain strings that don't parse as JSON, XML, or YAML are treated as scalar values.

## Format Detection Order

When a string is provided, detection happens in this order:

1. **JSON** - Attempts `json_decode()`
2. **XML** - Checks for `<` as first character
3. **YAML** - Attempts `Yaml::parse()`
4. **Scalar** - Falls back to treating as plain string

```php
// JSON detected
IdempotencyKey::create('{"key":"value"}');

// XML detected
IdempotencyKey::create('<root><key>value</key></root>');

// YAML detected
IdempotencyKey::create("key: value\nother: data");

// Plain string (no format detected)
IdempotencyKey::create('just a plain string');
```

## Cross-Format Consistency

The same logical data produces the same key regardless of format:

```php
$array = ['name' => 'John', 'age' => 30];
$json = '{"name":"John","age":30}';
$xml = '<data name="John" age="30"/>';
$yaml = "name: John\nage: 30";

$k1 = IdempotencyKey::create($array);
$k2 = IdempotencyKey::create($json);
$k3 = IdempotencyKey::create($xml);
$k4 = IdempotencyKey::create($yaml);

// Note: XML and YAML may differ from JSON/array due to structure differences
// But same format with same data always produces same key
```

## Type Preservation

During normalization, types are preserved:

```php
$data = [
    'string' => 'hello',
    'int' => 42,
    'float' => 3.14,
    'bool' => true,
    'null' => null,
    'array' => [1, 2, 3],
];

$key = IdempotencyKey::create($data);
```

Different types produce different keys:

```php
IdempotencyKey::create(['value' => 42])->equals(
    IdempotencyKey::create(['value' => '42'])
); // false - int vs string
```

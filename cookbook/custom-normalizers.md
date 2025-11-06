# Custom Normalizers

Custom normalizers allow you to preprocess data before the standard normalization pipeline. This is useful for domain-specific transformations, filtering sensitive data, or normalizing complex business objects.

## The NormalizerInterface

Implement the `NormalizerInterface` to create custom normalizers:

```php
namespace Cline\Idempotency\Contracts;

interface NormalizerInterface
{
    public function normalize(mixed $data): mixed;
}
```

## Basic Custom Normalizer

Create a simple normalizer:

```php
use Cline\Idempotency\Contracts\NormalizerInterface;
use Cline\Idempotency\IdempotencyKey;

class UppercaseNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data): mixed
    {
        if (is_string($data)) {
            return strtoupper($data);
        }

        return $data;
    }
}

// Usage
$normalizer = new UppercaseNormalizer();

$key1 = IdempotencyKey::create('hello', normalizer: $normalizer);
$key2 = IdempotencyKey::create('HELLO', normalizer: $normalizer);

$key1->equals($key2); // true - both normalized to "HELLO"
```

## Filtering Sensitive Fields

Remove sensitive data before generating keys:

```php
use Cline\Idempotency\Contracts\NormalizerInterface;

class SensitiveFieldFilter implements NormalizerInterface
{
    public function __construct(
        private array $excludeFields = ['password', 'api_key', 'token', 'secret']
    ) {}

    public function normalize(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        return $this->filterArray($data);
    }

    private function filterArray(array $data): array
    {
        foreach ($this->excludeFields as $field) {
            unset($data[$field]);
        }

        // Recursively filter nested arrays
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->filterArray($value);
            }
        }

        return $data;
    }
}

// Usage
$userData = [
    'username' => 'john',
    'email' => 'john@example.com',
    'password' => 'secret123',
    'api_key' => 'sk_live_abc123',
];

$normalizer = new SensitiveFieldFilter();
$key = IdempotencyKey::create($userData, normalizer: $normalizer);

// Key generated from: ['username' => 'john', 'email' => 'john@example.com']
// password and api_key excluded
```

## Timestamp Normalization

Normalize timestamps to ensure consistency:

```php
use Cline\Idempotency\Contracts\NormalizerInterface;
use DateTimeInterface;

class TimestampNormalizer implements NormalizerInterface
{
    public function __construct(
        private string $precision = 'minute'
    ) {}

    public function normalize(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        return $this->normalizeArray($data);
    }

    private function normalizeArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $data[$key] = $this->roundTimestamp($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->normalizeArray($value);
            }
        }

        return $data;
    }

    private function roundTimestamp(DateTimeInterface $dt): string
    {
        return match ($this->precision) {
            'second' => $dt->format('Y-m-d H:i:s'),
            'minute' => $dt->format('Y-m-d H:i:00'),
            'hour' => $dt->format('Y-m-d H:00:00'),
            'day' => $dt->format('Y-m-d 00:00:00'),
            default => $dt->format('Y-m-d H:i:s'),
        };
    }
}

// Usage
$normalizer = new TimestampNormalizer('minute');

$data1 = ['created_at' => new DateTime('2025-01-15 10:30:15')];
$data2 = ['created_at' => new DateTime('2025-01-15 10:30:45')];

$key1 = IdempotencyKey::create($data1, normalizer: $normalizer);
$key2 = IdempotencyKey::create($data2, normalizer: $normalizer);

// Both rounded to '2025-01-15 10:30:00'
$key1->equals($key2); // true
```

## Domain Object Normalizer

Transform domain objects into canonical format:

```php
use Cline\Idempotency\Contracts\NormalizerInterface;

class PaymentNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data): mixed
    {
        if ($data instanceof Payment) {
            return [
                'amount' => $data->getAmount()->getAmount(),
                'currency' => $data->getAmount()->getCurrency()->getCode(),
                'customer_id' => $data->getCustomer()->getId(),
                'items' => array_map(
                    fn($item) => [
                        'sku' => $item->getSku(),
                        'quantity' => $item->getQuantity(),
                    ],
                    $data->getItems()
                ),
            ];
        }

        return $data;
    }
}

// Usage
$payment = new Payment(
    amount: new Money(10000, Currency::USD()),
    customer: $customer,
    items: $items
);

$normalizer = new PaymentNormalizer();
$key = IdempotencyKey::create($payment, normalizer: $normalizer);
```

## Composite Normalizer

Chain multiple normalizers:

```php
use Cline\Idempotency\Contracts\NormalizerInterface;

class CompositeNormalizer implements NormalizerInterface
{
    public function __construct(
        private array $normalizers = []
    ) {}

    public function add(NormalizerInterface $normalizer): self
    {
        $this->normalizers[] = $normalizer;
        return $this;
    }

    public function normalize(mixed $data): mixed
    {
        foreach ($this->normalizers as $normalizer) {
            $data = $normalizer->normalize($data);
        }

        return $data;
    }
}

// Usage
$normalizer = new CompositeNormalizer();
$normalizer
    ->add(new SensitiveFieldFilter())
    ->add(new TimestampNormalizer('minute'))
    ->add(new PaymentNormalizer());

$key = IdempotencyKey::create($paymentData, normalizer: $normalizer);
```

## Sorting Normalizer

Ensure array elements are in consistent order:

```php
use Cline\Idempotency\Contracts\NormalizerInterface;

class SortNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        // Sort indexed arrays by value
        if (array_is_list($data)) {
            sort($data);
            return array_map(fn($v) => $this->normalize($v), $data);
        }

        // Sort associative arrays by key
        ksort($data);
        return array_map(fn($v) => $this->normalize($v), $data);
    }
}

// Usage
$data1 = ['items' => ['banana', 'apple', 'cherry']];
$data2 = ['items' => ['cherry', 'banana', 'apple']];

$normalizer = new SortNormalizer();

$key1 = IdempotencyKey::create($data1, normalizer: $normalizer);
$key2 = IdempotencyKey::create($data2, normalizer: $normalizer);

$key1->equals($key2); // true - both sorted to ['apple', 'banana', 'cherry']
```

**Note**: The built-in normalization already sorts associative array keys. This normalizer is useful if you also need to sort array values.

## Whitespace Normalizer

Normalize whitespace in strings:

```php
use Cline\Idempotency\Contracts\NormalizerInterface;

class WhitespaceNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data): mixed
    {
        if (is_string($data)) {
            // Trim and normalize multiple spaces to single space
            return trim(preg_replace('/\s+/', ' ', $data));
        }

        if (is_array($data)) {
            return array_map(fn($v) => $this->normalize($v), $data);
        }

        return $data;
    }
}

// Usage
$normalizer = new WhitespaceNormalizer();

$text1 = "Hello    World\n\n";
$text2 = "Hello World";

$key1 = IdempotencyKey::create($text1, normalizer: $normalizer);
$key2 = IdempotencyKey::create($text2, normalizer: $normalizer);

$key1->equals($key2); // true - both normalized to "Hello World"
```

## Case-Insensitive Normalizer

Make string comparisons case-insensitive:

```php
use Cline\Idempotency\Contracts\NormalizerInterface;

class CaseInsensitiveNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data): mixed
    {
        if (is_string($data)) {
            return mb_strtolower($data);
        }

        if (is_array($data)) {
            return array_map(fn($v) => $this->normalize($v), $data);
        }

        return $data;
    }
}

// Usage
$normalizer = new CaseInsensitiveNormalizer();

$key1 = IdempotencyKey::create('HELLO', normalizer: $normalizer);
$key2 = IdempotencyKey::create('hello', normalizer: $normalizer);

$key1->equals($key2); // true
```

## Best Practices

1. **Keep normalizers focused** - Each normalizer should handle one concern
2. **Make them composable** - Use CompositeNormalizer to chain multiple normalizers
3. **Document transformations** - Clearly document what each normalizer does
4. **Test thoroughly** - Ensure normalizers produce consistent output
5. **Avoid lossy transformations** - Be careful when removing or transforming data

```php
// Good: Focused, single-purpose normalizer
class EmailNormalizer implements NormalizerInterface
{
    public function normalize(mixed $data): mixed
    {
        if (is_string($data) && filter_var($data, FILTER_VALIDATE_EMAIL)) {
            return strtolower($data);
        }
        return $data;
    }
}

// Good: Compose multiple normalizers
$normalizer = new CompositeNormalizer();
$normalizer
    ->add(new EmailNormalizer())
    ->add(new WhitespaceNormalizer())
    ->add(new SensitiveFieldFilter());
```

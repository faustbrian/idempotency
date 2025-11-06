# Advanced Examples

Real-world usage patterns and advanced techniques for the idempotency library.

## API Request Deduplication

Prevent duplicate API requests by generating idempotency keys from request data:

```php
use Cline\Idempotency\IdempotencyKey;

class PaymentService
{
    private array $processedKeys = [];

    public function processPayment(array $paymentData): PaymentResult
    {
        // Generate key from payment data
        $key = IdempotencyKey::create($paymentData);
        $keyString = $key->toString();

        // Check if already processed
        if (isset($this->processedKeys[$keyString])) {
            return $this->processedKeys[$keyString];
        }

        // Process payment
        $result = $this->executePayment($paymentData);

        // Cache result
        $this->processedKeys[$keyString] = $result;

        return $result;
    }
}

// Usage
$payment = [
    'amount' => 100.00,
    'currency' => 'USD',
    'customer_id' => 'cust_123',
    'timestamp' => '2025-01-15T10:30:00Z',
];

// These produce the same key, preventing duplicate charge
$service->processPayment($payment);
$service->processPayment($payment); // Returns cached result
```

## HTTP Idempotency Headers

Use with HTTP Idempotency-Key headers:

```php
use Cline\Idempotency\IdempotencyKey;

class ApiController
{
    public function handleRequest(Request $request): Response
    {
        // Extract relevant data (exclude timestamps, request IDs, etc.)
        $requestData = [
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'body' => $request->json(),
        ];

        // Generate idempotency key
        $key = IdempotencyKey::create($requestData);

        // Use truncated version for header (shorter, still unique enough)
        $response = new Response();
        $response->header('Idempotency-Key', $key->truncate(32));

        return $response;
    }
}
```

## Database Record Deduplication

Ensure unique records based on content:

```php
use Cline\Idempotency\IdempotencyKey;
use Illuminate\Support\Facades\DB;

class UserImporter
{
    public function importUser(array $userData): void
    {
        // Generate content-based hash
        $key = IdempotencyKey::create([
            'email' => $userData['email'],
            'name' => $userData['name'],
            'company' => $userData['company'],
        ]);

        // Use as unique constraint
        DB::table('users')->insertOrIgnore([
            'idempotency_key' => $key->toString(),
            'email' => $userData['email'],
            'name' => $userData['name'],
            'company' => $userData['company'],
            'created_at' => now(),
        ]);
    }
}

// Migration
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('idempotency_key', 64)->unique();
    $table->string('email');
    $table->string('name');
    $table->string('company');
    $table->timestamps();
});
```

## Webhook Event Deduplication

Prevent processing duplicate webhook events:

```php
use Cline\Idempotency\IdempotencyKey;
use Illuminate\Support\Facades\Cache;

class WebhookHandler
{
    public function handle(array $webhookPayload): void
    {
        // Generate key from payload (excluding metadata)
        $key = IdempotencyKey::create([
            'event_type' => $webhookPayload['type'],
            'data' => $webhookPayload['data'],
            'resource_id' => $webhookPayload['resource_id'],
        ]);

        $keyString = $key->toString();

        // Check if already processed (with 24h TTL)
        if (Cache::has("webhook:{$keyString}")) {
            return; // Already processed, skip
        }

        // Process webhook
        $this->processWebhook($webhookPayload);

        // Mark as processed
        Cache::put("webhook:{$keyString}", true, now()->addDay());
    }
}
```

## Content-Addressable Storage

Use idempotency keys as content addresses:

```php
use Cline\Idempotency\IdempotencyKey;
use Illuminate\Support\Facades\Storage;

class ContentStore
{
    public function store(string $content): string
    {
        // Generate key from content
        $key = IdempotencyKey::create($content);
        $address = $key->toString();

        // Store only if not exists (automatic deduplication)
        if (!Storage::exists("content/{$address}")) {
            Storage::put("content/{$address}", $content);
        }

        return $address;
    }

    public function retrieve(string $address): ?string
    {
        return Storage::get("content/{$address}");
    }

    public function verify(string $address, string $content): bool
    {
        $key = IdempotencyKey::create($content);
        return $key->equals($address);
    }
}

// Usage
$store = new ContentStore();

$content = 'Lorem ipsum dolor sit amet...';
$address = $store->store($content); // "a1b2c3d4..."

// Same content stores once
$address2 = $store->store($content); // Same address
assert($address === $address2);

// Verify integrity
$retrieved = $store->retrieve($address);
$store->verify($address, $retrieved); // true
```

## Caching with Content-Based Keys

Generate cache keys based on query parameters:

```php
use Cline\Idempotency\IdempotencyKey;
use Illuminate\Support\Facades\Cache;

class ReportGenerator
{
    public function generateReport(array $filters): Report
    {
        // Generate cache key from filters
        $key = IdempotencyKey::create($filters);
        $cacheKey = "report:{$key->truncate(32)}";

        return Cache::remember($cacheKey, 3600, function () use ($filters) {
            return $this->buildReport($filters);
        });
    }
}

// Usage
$filters = [
    'start_date' => '2025-01-01',
    'end_date' => '2025-01-31',
    'department' => 'sales',
    'region' => 'north',
];

// These generate same cache key regardless of order
$report1 = $generator->generateReport($filters);
$report2 = $generator->generateReport([
    'region' => 'north',
    'department' => 'sales',
    'end_date' => '2025-01-31',
    'start_date' => '2025-01-01',
]); // Returns cached result
```

## ETags for HTTP Caching

Generate ETags from response data:

```php
use Cline\Idempotency\IdempotencyKey;

class ApiController
{
    public function getResource(Request $request): Response
    {
        $resource = $this->fetchResource($request->route('id'));

        // Generate ETag from resource data
        $key = IdempotencyKey::create($resource->toArray());
        $etag = $key->truncate(16);

        // Check If-None-Match header
        if ($request->header('If-None-Match') === $etag) {
            return response()->noContent(304);
        }

        return response()->json($resource)->header('ETag', $etag);
    }
}
```

## Distributed Lock Keys

Create deterministic lock keys for distributed systems:

```php
use Cline\Idempotency\IdempotencyKey;
use Illuminate\Support\Facades\Cache;

class DistributedLock
{
    public function executeOnce(array $taskData, callable $callback): mixed
    {
        // Generate deterministic lock key
        $key = IdempotencyKey::create($taskData);
        $lockKey = "lock:{$key->toString()}";

        $lock = Cache::lock($lockKey, 60);

        if ($lock->get()) {
            try {
                return $callback();
            } finally {
                $lock->release();
            }
        }

        throw new LockException('Could not acquire lock');
    }
}

// Usage - prevents concurrent execution of same task
$lock = new DistributedLock();

$taskData = [
    'user_id' => 123,
    'action' => 'send_email',
    'template' => 'welcome',
];

$lock->executeOnce($taskData, function () use ($taskData) {
    // Only one instance across all servers will execute this
    $this->sendEmail($taskData);
});
```

## Versioned Keys with Prefixes

Namespace keys by version and context:

```php
use Cline\Idempotency\IdempotencyKey;

class ApiVersioning
{
    public function generateRequestKey(string $version, array $requestData): string
    {
        // Use prefix to namespace by API version
        $key = IdempotencyKey::create(
            data: $requestData,
            prefix: "api.{$version}"
        );

        return $key->toVersionedString();
    }
}

// Usage
$api = new ApiVersioning();

$requestData = ['user_id' => 123, 'action' => 'update'];

$v1Key = $api->generateRequestKey('v1', $requestData);
// "v1:sha256:abc123..."

$v2Key = $api->generateRequestKey('v2', $requestData);
// "v1:sha256:def456..." (different hash due to different prefix)

// Different versions produce different keys
assert($v1Key !== $v2Key);
```

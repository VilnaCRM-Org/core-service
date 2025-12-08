# Metrics Patterns

Comprehensive guide to implementing metrics collection for observability.

## The Three Key Metrics

### 1. Latency (Duration)

**What**: How long operations take

**Why**: Detect performance degradation

**How**: Record duration in milliseconds

```php
$startTime = microtime(true);
// ... operation
$duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

$this->metrics->record('operation.duration', $duration, [
    'status' => 'success',
]);
```

### 2. Errors (Failures)

**What**: Count of failed operations

**Why**: Detect reliability issues

**How**: Increment counter on errors

```php
try {
    // ... operation
} catch (\Throwable $e) {
    $this->metrics->increment('operation.errors', [
        'error_type' => get_class($e),
    ]);
    throw $e;
}
```

### 3. Throughput (RPS - Requests Per Second)

**What**: Number of operations per time unit

**Why**: Understand load and capacity

**How**: Increment counter for each operation

```php
$this->metrics->increment('operation.total');
```

---

## Metric Naming Convention

**Format**: `{component}.{operation}.{metric_type}`

### Components

- **customer** - Customer bounded context
- **order** - Order bounded context
- **mongodb** - Database operations
- **http** - HTTP operations
- **cache** - Cache operations

### Operations

- **create** - Create operations
- **update** - Update operations
- **delete** - Delete operations
- **find** - Query operations
- **save** - Persistence operations

### Metric Types

- **duration** or **duration_ms** - Timing in milliseconds
- **total** - Count of operations
- **errors** - Count of failures
- **size** - Size in bytes

### Examples

```
# Command handlers
customer.create.duration_ms
customer.create.total
customer.create.errors
customer.update.duration_ms
order.place.duration_ms

# Repository operations
mongodb.save.duration_ms
mongodb.find.duration_ms
mongodb.query.errors

# HTTP operations
http.email.send.duration_ms
http.email.send.errors
http.api.call.total

# Cache operations
cache.hit.total
cache.miss.total
cache.read.duration_ms
```

---

## Metrics Interface

### Basic Metrics Collector

```php
<?php

declare(strict_types=1);

namespace App\Shared\Application\Service;

interface MetricsCollector
{
    /**
     * Record a metric value (gauge or timing)
     *
     * @param string $name Metric name
     * @param float $value Metric value
     * @param array<string, string> $tags Optional tags
     */
    public function record(string $name, float $value, array $tags = []): void;

    /**
     * Increment a counter
     *
     * @param string $name Counter name
     * @param array<string, string> $tags Optional tags
     * @param int $value Increment by (default 1)
     */
    public function increment(string $name, array $tags = [], int $value = 1): void;

    /**
     * Decrement a counter
     *
     * @param string $name Counter name
     * @param array<string, string> $tags Optional tags
     * @param int $value Decrement by (default 1)
     */
    public function decrement(string $name, array $tags = [], int $value = 1): void;
}
```

### Simple Implementation (Development)

```php
<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Service;

use App\Shared\Application\Service\MetricsCollector;
use Psr\Log\LoggerInterface;

final readonly class LoggingMetricsCollector implements MetricsCollector
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function record(string $name, float $value, array $tags = []): void
    {
        $this->logger->info('METRIC', [
            'metric_name' => $name,
            'metric_value' => $value,
            'metric_tags' => $tags,
            'metric_type' => 'gauge',
        ]);
    }

    public function increment(string $name, array $tags = [], int $value = 1): void
    {
        $this->logger->info('METRIC', [
            'metric_name' => $name,
            'metric_value' => $value,
            'metric_tags' => $tags,
            'metric_type' => 'counter',
        ]);
    }

    public function decrement(string $name, array $tags = [], int $value = 1): void
    {
        $this->logger->info('METRIC', [
            'metric_name' => $name,
            'metric_value' => -$value,
            'metric_tags' => $tags,
            'metric_type' => 'counter',
        ]);
    }
}
```

---

## Complete Handler Example

```php
final readonly class CreateCustomerCommandHandler
{
    public function __construct(
        private CustomerRepository $repository,
        private MetricsCollector $metrics,
        private LoggerInterface $logger
    ) {}

    public function __invoke(CreateCustomerCommand $command): void
    {
        $startTime = microtime(true);

        try {
            // 1. Execute operation
            $customer = Customer::create(/* ... */);
            $this->repository->save($customer);

            // 2. Record success metrics
            $duration = (microtime(true) - $startTime) * 1000;

            // Duration with success tag
            $this->metrics->record('customer.create.duration', $duration, [
                'status' => 'success',
            ]);

            // Throughput counter
            $this->metrics->increment('customer.create.total');

            // Business metric
            $this->metrics->increment('customer.total');

        } catch (\Throwable $e) {
            // 3. Record error metrics
            $duration = (microtime(true) - $startTime) * 1000;

            // Duration with error tag
            $this->metrics->record('customer.create.duration', $duration, [
                'status' => 'error',
            ]);

            // Error counter with error type
            $this->metrics->increment('customer.create.errors', [
                'error_type' => get_class($e),
            ]);

            throw $e;
        }
    }
}
```

---

## Tags (Labels)

Tags provide dimensions for filtering metrics.

### Common Tags

| Tag | Purpose | Example Values |
|-----|---------|----------------|
| **status** | Operation result | `success`, `error` |
| **error_type** | Exception class | `ConnectionException`, `ValidationException` |
| **operation** | Specific operation | `create`, `update`, `delete` |
| **entity_type** | Entity being operated on | `customer`, `order` |
| **source** | Origin of request | `api`, `cli`, `queue` |

### Example with Tags

```php
// Success with tags
$this->metrics->record('handler.duration', $duration, [
    'handler' => 'CreateCustomerCommandHandler',
    'status' => 'success',
    'source' => 'api',
]);

// Error with tags
$this->metrics->increment('handler.errors', [
    'handler' => 'CreateCustomerCommandHandler',
    'error_type' => 'ValidationException',
    'source' => 'api',
]);
```

---

## Metrics by Layer

### Application Layer (Command Handlers)

```php
final readonly class CreateCustomerCommandHandler
{
    public function __invoke(CreateCustomerCommand $command): void
    {
        $startTime = microtime(true);

        try {
            // Execute
            $this->execute($command);

            // Metrics
            $duration = (microtime(true) - $startTime) * 1000;
            $this->metrics->record('customer.create.duration', $duration, ['status' => 'success']);
            $this->metrics->increment('customer.create.total');

        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->metrics->record('customer.create.duration', $duration, ['status' => 'error']);
            $this->metrics->increment('customer.create.errors', ['error_type' => get_class($e)]);
            throw $e;
        }
    }
}
```

### Infrastructure Layer (Repositories)

```php
final class MongoCustomerRepository
{
    public function save(Customer $customer): void
    {
        $startTime = microtime(true);

        try {
            $this->documentManager->persist($customer);
            $this->documentManager->flush();

            $duration = (microtime(true) - $startTime) * 1000;
            $this->metrics->record('mongodb.customer.save.duration', $duration);

        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            $this->metrics->record('mongodb.customer.save.duration', $duration, ['status' => 'error']);
            $this->metrics->increment('mongodb.customer.save.errors', ['error_type' => get_class($e)]);
            throw $e;
        }
    }

    public function findById(CustomerId $id): ?Customer
    {
        $startTime = microtime(true);

        try {
            $customer = $this->documentManager->find(Customer::class, $id->value());

            $duration = (microtime(true) - $startTime) * 1000;
            $this->metrics->record('mongodb.customer.find.duration', $duration);

            // Track cache hits/misses
            if ($customer === null) {
                $this->metrics->increment('mongodb.customer.find.misses');
            } else {
                $this->metrics->increment('mongodb.customer.find.hits');
            }

            return $customer;

        } catch (\Throwable $e) {
            $this->metrics->increment('mongodb.customer.find.errors', ['error_type' => get_class($e)]);
            throw $e;
        }
    }
}
```

### Infrastructure Layer (HTTP Clients)

```php
final class EmailServiceHttpClient
{
    public function sendEmail(string $to, string $subject): void
    {
        $startTime = microtime(true);

        try {
            $response = $this->httpClient->post('/api/email', [/* ... */]);

            $duration = (microtime(true) - $startTime) * 1000;

            $this->metrics->record('http.email.send.duration', $duration, [
                'status_code' => (string) $response->getStatusCode(),
            ]);

            $this->metrics->increment('http.email.sent.total');

        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            $this->metrics->record('http.email.send.duration', $duration, ['status' => 'error']);
            $this->metrics->increment('http.email.send.errors', [
                'error_type' => get_class($e),
            ]);

            throw $e;
        }
    }
}
```

---

## Business Metrics

Track domain-specific events:

```php
// After creating customer
$this->metrics->increment('business.customer.registered.total');

// After placing order
$this->metrics->increment('business.order.placed.total');
$this->metrics->record('business.order.value', $order->total());

// After payment
$this->metrics->increment('business.payment.processed.total', [
    'payment_method' => $paymentMethod,
]);
```

---

## Percentiles and Histograms

For advanced metrics backends (Prometheus, Datadog):

```php
// Record timing for histogram/percentile calculation
$this->metrics->record('customer.create.duration', $duration);

// Backend calculates:
// - p50 (median): 45ms
// - p95: 120ms
// - p99: 250ms
```

---

## Rate Calculation

Calculate error rate:

```
error_rate = errors / total
```

Example:
- `customer.create.total`: 1000
- `customer.create.errors`: 10
- Error rate: 10 / 1000 = 1%

---

## Alerting Thresholds

Define SLOs (Service Level Objectives):

| Metric | Threshold | Alert |
|--------|-----------|-------|
| p95 latency | > 500ms | Warning |
| p99 latency | > 1000ms | Critical |
| Error rate | > 1% | Warning |
| Error rate | > 5% | Critical |

---

## Testing Metrics

### Unit Test

```php
final class MetricsCollectorSpy implements MetricsCollector
{
    private array $recorded = [];
    private array $incremented = [];

    public function record(string $name, float $value, array $tags = []): void
    {
        $this->recorded[] = compact('name', 'value', 'tags');
    }

    public function increment(string $name, array $tags = [], int $value = 1): void
    {
        $this->incremented[] = compact('name', 'tags', 'value');
    }

    public function assertRecorded(string $name, ?float $value = null): void
    {
        foreach ($this->recorded as $record) {
            if ($record['name'] === $name) {
                if ($value === null || abs($record['value'] - $value) < 0.01) {
                    return;
                }
            }
        }
        throw new \AssertionError("Metric '$name' not recorded");
    }

    public function assertIncremented(string $name): void
    {
        foreach ($this->incremented as $record) {
            if ($record['name'] === $name) {
                return;
            }
        }
        throw new \AssertionError("Counter '$name' not incremented");
    }
}

// Usage in test
final class CreateCustomerHandlerTest extends TestCase
{
    public function testRecordsMetrics(): void
    {
        $metrics = new MetricsCollectorSpy();
        $handler = new CreateCustomerCommandHandler(/* ..., */ $metrics);

        $handler(new CreateCustomerCommand(/* ... */));

        $metrics->assertRecorded('customer.create.duration');
        $metrics->assertIncremented('customer.create.total');
    }
}
```

---

## Common Patterns

### Pattern 1: Success/Error Tagging

```php
$tags = ['status' => $success ? 'success' : 'error'];
$this->metrics->record('operation.duration', $duration, $tags);
```

### Pattern 2: Try-Catch Metrics

```php
try {
    $result = $this->operation();
    $this->metrics->increment('operation.success');
    return $result;
} catch (\Throwable $e) {
    $this->metrics->increment('operation.errors', ['error_type' => get_class($e)]);
    throw $e;
}
```

### Pattern 3: Timing Helper

```php
private function timeOperation(callable $operation, string $metricName): mixed
{
    $startTime = microtime(true);

    try {
        $result = $operation();
        $duration = (microtime(true) - $startTime) * 1000;
        $this->metrics->record($metricName, $duration, ['status' => 'success']);
        return $result;

    } catch (\Throwable $e) {
        $duration = (microtime(true) - $startTime) * 1000;
        $this->metrics->record($metricName, $duration, ['status' => 'error']);
        $this->metrics->increment("$metricName.errors", ['error_type' => get_class($e)]);
        throw $e;
    }
}

// Usage
$customer = $this->timeOperation(
    fn() => $this->repository->save($customer),
    'mongodb.customer.save.duration'
);
```

---

## Success Criteria

- ✅ Duration metrics recorded for all operations
- ✅ Error counters increment on failures
- ✅ Throughput counters track operation volume
- ✅ Tags provide meaningful dimensions
- ✅ Metric names follow naming convention
- ✅ Business metrics track domain events

---

**Next Steps**:
- [Tracing Patterns](tracing-patterns.md) - Add distributed tracing
- [Structured Logging](structured-logging.md) - Combine with logs
- [PR Evidence Guide](pr-evidence-guide.md) - Show metrics in PRs

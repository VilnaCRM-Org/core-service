# Business Metrics with AWS EMF

Guide to implementing business metrics using AWS CloudWatch Embedded Metric Format (EMF). This codebase uses AWS AppRunner which provides infrastructure metrics (latency, errors, RPS) automatically - this guide focuses on **business metrics only**.

## What Are Business Metrics?

Business metrics track domain events and business value - things that matter to the business, not infrastructure.

| Business Metrics (Track These) | Infrastructure Metrics (AWS Provides) |
| ------------------------------ | ------------------------------------- |
| CustomersCreated               | Request latency (p50, p95, p99)       |
| OrdersPlaced                   | Error rates                           |
| PaymentsProcessed              | Requests per second                   |
| OrderValue (amount)            | CPU/Memory usage                      |
| LoginAttempts                  | Connection counts                     |

---

## AWS EMF Format

AWS Embedded Metric Format allows logs to be automatically extracted as CloudWatch metrics.

### EMF Log Structure

```json
{
  "_aws": {
    "Timestamp": 1702425600000,
    "CloudWatchMetrics": [
      {
        "Namespace": "CCore/BusinessMetrics",
        "Dimensions": [["Endpoint", "Operation"]],
        "Metrics": [{ "Name": "CustomersCreated", "Unit": "Count" }]
      }
    ]
  },
  "Endpoint": "Customer",
  "Operation": "_api_/customers_post",
  "CustomersCreated": 1
}
```

When written to stdout, CloudWatch automatically:

1. Extracts `CustomersCreated` as a metric
2. Associates it with the `CCore/BusinessMetrics` namespace
3. Applies dimensions `Endpoint` and `Operation`

---

## Using BusinessMetricsEmitterInterface

### Inject the Interface

```php
use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;

final readonly class CreateCustomerCommandHandler
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private BusinessMetricsEmitterInterface $metrics
    ) {}
}
```

### Emit Single Metric

```php
public function __invoke(CreateCustomerCommand $command): void
{
    // ... create customer logic

    $this->metrics->emit('CustomersCreated', 1, [
        'Endpoint' => 'Customer',
        'Operation' => 'create',
    ]);
}
```

### Emit Multiple Metrics

```php
public function __invoke(PlaceOrderCommand $command): void
{
    // ... place order logic

    $this->metrics->emitMultiple([
        'OrdersPlaced' => ['value' => 1, 'unit' => 'Count'],
        'OrderValue' => ['value' => $order->totalAmount(), 'unit' => 'None'],
    ], [
        'Endpoint' => 'Order',
        'PaymentMethod' => $order->paymentMethod(),
    ]);
}
```

---

## Metric Naming Convention

### Format

```
{Entity}{Action}   # PascalCase, plural noun, past tense
```

### Examples

| Good                | Bad                    |
| ------------------- | ---------------------- |
| `CustomersCreated`  | `customer_created`     |
| `OrdersPlaced`      | `orders.placed.count`  |
| `PaymentsProcessed` | `payment-processed`    |
| `LoginAttempts`     | `login_attempts_total` |

### Guidelines

- Use **PascalCase** for metric names
- Use **plural nouns** for counters (CustomersCreated, not CustomerCreated)
- Use **past tense** for completed actions
- Keep names **concise** but descriptive

---

## Dimensions Best Practices

### Recommended Dimensions

| Dimension       | Description       | Cardinality |
| --------------- | ----------------- | ----------- |
| `Endpoint`      | API resource name | Low         |
| `Operation`     | CRUD action       | Very Low    |
| `PaymentMethod` | Payment type      | Low         |
| `CustomerType`  | Customer segment  | Low         |

### Avoid High-Cardinality Dimensions

**Don't use:**

- Customer IDs
- Order IDs
- Session IDs
- Timestamps
- Email addresses

High-cardinality dimensions create too many unique metric streams and increase CloudWatch costs significantly.

---

## Business Metrics by Domain

### Customer Domain

```php
// Customer registration
$this->metrics->emit('CustomersCreated', 1, [
    'Endpoint' => 'Customer',
    'Operation' => 'create',
]);

// Customer update
$this->metrics->emit('CustomersUpdated', 1, [
    'Endpoint' => 'Customer',
    'Operation' => 'update',
]);

// Customer deletion
$this->metrics->emit('CustomersDeleted', 1, [
    'Endpoint' => 'Customer',
    'Operation' => 'delete',
]);
```

### Order Domain

```php
// Order placed
$this->metrics->emitMultiple([
    'OrdersPlaced' => ['value' => 1, 'unit' => 'Count'],
    'OrderValue' => ['value' => $order->totalAmount(), 'unit' => 'None'],
    'OrderItemCount' => ['value' => $order->itemCount(), 'unit' => 'Count'],
], [
    'Endpoint' => 'Order',
    'PaymentMethod' => $order->paymentMethod(),
]);

// Order cancelled
$this->metrics->emit('OrdersCancelled', 1, [
    'Endpoint' => 'Order',
    'CancellationReason' => $reason,
]);
```

### Payment Domain

```php
// Payment processed
$this->metrics->emitMultiple([
    'PaymentsProcessed' => ['value' => 1, 'unit' => 'Count'],
    'PaymentAmount' => ['value' => $payment->amount(), 'unit' => 'None'],
], [
    'Endpoint' => 'Payment',
    'PaymentProvider' => $payment->provider(),
    'PaymentMethod' => $payment->method(),
]);

// Payment failed
$this->metrics->emit('PaymentsFailed', 1, [
    'Endpoint' => 'Payment',
    'FailureReason' => $failureCategory, // Low cardinality: 'insufficient_funds', 'expired_card', etc.
]);
```

### Authentication Domain

```php
// Login attempt
$this->metrics->emit('LoginAttempts', 1, [
    'Endpoint' => 'Auth',
    'Result' => $success ? 'success' : 'failure',
]);

// Password reset requested
$this->metrics->emit('PasswordResetsRequested', 1, [
    'Endpoint' => 'Auth',
]);
```

---

## Units Reference

| Unit      | Use For                                   |
| --------- | ----------------------------------------- |
| `Count`   | Counters (items created, events occurred) |
| `None`    | Monetary values, quantities without unit  |
| `Seconds` | Time durations                            |
| `Bytes`   | Data sizes                                |
| `Percent` | Percentages                               |

---

## CloudWatch Queries

After deploying, query your business metrics:

```sql
-- Total endpoint invocations by resource
SELECT SUM(EndpointInvocations)
FROM "CCore/BusinessMetrics"
GROUP BY Endpoint

-- Customers created over time
SELECT SUM(CustomersCreated)
FROM "CCore/BusinessMetrics"
WHERE Endpoint = 'Customer'

-- Order value by payment method
SELECT AVG(OrderValue)
FROM "CCore/BusinessMetrics"
WHERE Endpoint = 'Order'
GROUP BY PaymentMethod
```

---

## What NOT to Track

AWS AppRunner already provides infrastructure metrics. Do NOT implement:

- Request latency (p50, p95, p99)
- Error rates and counts
- Requests per second (RPS)
- Response times
- HTTP status codes
- Memory/CPU usage
- Connection metrics

Focus ONLY on business events and values.

---

## Testing Business Metrics

Use the spy in unit tests:

```php
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;

final class CreateCustomerCommandHandlerTest extends TestCase
{
    public function testEmitsCustomerCreatedMetric(): void
    {
        $metricsSpy = new BusinessMetricsEmitterSpy();
        $handler = new CreateCustomerCommandHandler($repository, $metricsSpy);

        $handler(new CreateCustomerCommand(/* ... */));

        $emitted = $metricsSpy->emitted();
        self::assertCount(1, $emitted);
        self::assertSame('CustomersCreated', $emitted[0]['name']);
        self::assertSame(1, $emitted[0]['value']);
        self::assertSame('Customer', $emitted[0]['dimensions']['Endpoint']);
    }

    public function testEmitsMetricWithCorrectDimensions(): void
    {
        $metricsSpy = new BusinessMetricsEmitterSpy();
        $handler = new CreateCustomerCommandHandler($repository, $metricsSpy);

        $handler(new CreateCustomerCommand(/* ... */));

        $metricsSpy->assertEmittedWithDimensions('CustomersCreated', [
            'Endpoint' => 'Customer',
            'Operation' => 'create',
        ]);
    }
}
```

---

## Success Criteria

- ✅ Business metrics track domain events (not infrastructure)
- ✅ Metrics use PascalCase naming convention
- ✅ Dimensions have low cardinality (no IDs, timestamps)
- ✅ Unit tests verify metric emission
- ✅ EMF format outputs to stdout for CloudWatch extraction
- ✅ Namespace is `CCore/BusinessMetrics`

---

**Next Steps**:

- [Quick Start Guide](quick-start.md) - Add business metrics to your code
- [Structured Logging](structured-logging.md) - Add correlation IDs for debugging
- [Complete Example](../examples/instrumented-command-handler.md) - Full working example

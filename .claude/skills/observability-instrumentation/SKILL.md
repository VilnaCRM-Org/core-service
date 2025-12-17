---
name: observability-instrumentation
description: Add business metrics using AWS EMF (Embedded Metric Format) to API endpoints. Focus on domain-specific metrics only - AWS AppRunner provides default SLO/SLA metrics. Use when implementing new endpoints, adding command handlers, or instrumenting business events.
---

# Business Metrics with AWS EMF

Instrument API endpoints with **business metrics** using AWS CloudWatch Embedded Metric Format (EMF). This skill focuses exclusively on domain-specific metrics - AWS AppRunner already provides infrastructure SLO/SLA metrics automatically.

## What This Skill Covers

- **Business metrics** - Domain events (customers created, orders placed, payments processed)
- **AWS EMF format** - Logs that automatically become CloudWatch metrics
- **Endpoint instrumentation** - Per-endpoint business metric emission

## What This Skill Does NOT Cover

- **Infrastructure metrics** - Latency, error rates, RPS (AWS AppRunner provides these)
- **SLO/SLA metrics** - Availability, response times (AWS AppRunner provides these)
- **Distributed tracing** - Use AWS X-Ray integration instead

## When to Use This Skill

Use this skill when:

- Implementing new API endpoints that have business significance
- Adding command handlers that create/modify business entities
- Tracking domain events for analytics and business intelligence
- Building dashboards for business KPIs

---

## Current Implementation

The codebase already has AWS EMF business metrics implemented:

### Interface (Application Layer)

```php
// src/Shared/Application/Observability/BusinessMetricsEmitterInterface.php
interface BusinessMetricsEmitterInterface
{
    public function emit(
        string $metricName,
        float|int $value,
        array $dimensions = [],
        string $unit = 'Count'
    ): void;

    public function emitMultiple(array $metrics, array $dimensions = []): void;
}
```

### Implementation (Infrastructure Layer)

```php
// src/Shared/Infrastructure/Observability/AwsEmfBusinessMetricsEmitter.php
// Outputs AWS EMF JSON format to stdout - CloudWatch extracts metrics automatically
```

### Automatic Endpoint Metrics

```php
// src/Shared/Infrastructure/Observability/ApiEndpointBusinessMetricsSubscriber.php
// Automatically emits 'EndpointInvocations' metric for every /api request
```

---

## AWS EMF Format

AWS Embedded Metric Format allows you to embed custom metrics in structured log events. CloudWatch automatically extracts metrics from EMF-formatted logs.

### EMF Log Structure

```json
{
  "_aws": {
    "Timestamp": 1702425600000,
    "CloudWatchMetrics": [
      {
        "Namespace": "CCore/BusinessMetrics",
        "Dimensions": [["Endpoint", "Operation"]],
        "Metrics": [{ "Name": "EndpointInvocations", "Unit": "Count" }]
      }
    ]
  },
  "Endpoint": "Customer",
  "Operation": "_api_/customers_post",
  "EndpointInvocations": 1
}
```

When this log is written to stdout, CloudWatch automatically:

1. Extracts `EndpointInvocations` as a metric
2. Associates it with the `CCore/BusinessMetrics` namespace
3. Applies dimensions `Endpoint` and `Operation`

---

## Using Business Metrics

### Inject the Interface

```php
use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;

final readonly class YourCommandHandler
{
    public function __construct(
        private YourRepository $repository,
        private BusinessMetricsEmitterInterface $metrics
    ) {}
}
```

### Emit Single Metric

```php
// When a customer is created
$this->metrics->emit('CustomersCreated', 1, [
    'Endpoint' => '/api/customers',
    'Operation' => 'create',
]);
```

### Emit Multiple Metrics

```php
// When an order is placed - track count and value
$this->metrics->emitMultiple([
    'OrdersPlaced' => ['value' => 1, 'unit' => 'Count'],
    'OrderValue' => ['value' => $order->totalAmount(), 'unit' => 'None'],
], [
    'Endpoint' => '/api/orders',
    'PaymentMethod' => $order->paymentMethod(),
]);
```

---

## Business Metrics by Domain

### Customer Domain

```php
// Registration
$this->metrics->emit('CustomersCreated', 1, [
    'Endpoint' => 'Customer',
    'Operation' => 'create',
]);

// Profile update
$this->metrics->emit('CustomersUpdated', 1, [
    'Endpoint' => 'Customer',
    'Operation' => 'update',
]);
```

### Order Domain

```php
// Order placed
$this->metrics->emitMultiple([
    'OrdersPlaced' => ['value' => 1, 'unit' => 'Count'],
    'OrderValue' => ['value' => $order->totalAmount(), 'unit' => 'None'],
], [
    'Endpoint' => 'Order',
    'PaymentMethod' => $order->paymentMethod(),
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
]);
```

---

## Dimension Best Practices

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

These create too many unique metric streams and increase CloudWatch costs.

---

## Metric Naming Conventions

### Format

```
{Entity}{Action}   # PascalCase
```

### Examples

| Good                | Bad                   |
| ------------------- | --------------------- |
| `CustomersCreated`  | `customer_created`    |
| `OrdersPlaced`      | `orders.placed.count` |
| `PaymentsProcessed` | `payment-processed`   |

### Guidelines

- Use PascalCase for metric names
- Use plural nouns for counters (CustomersCreated not CustomerCreated)
- Use past tense for completed actions

---

## Testing Business Metrics

### Use the Spy in Tests

```php
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;

final class YourCommandHandlerTest extends TestCase
{
    public function testEmitsBusinessMetric(): void
    {
        $metricsSpy = new BusinessMetricsEmitterSpy();
        $handler = new YourCommandHandler($repository, $metricsSpy);

        $handler(new YourCommand(/* ... */));

        $emitted = $metricsSpy->emitted();
        self::assertCount(1, $emitted);
        self::assertSame('CustomersCreated', $emitted[0]['name']);
        self::assertSame(1, $emitted[0]['value']);
    }
}
```

### Test Service Configuration

In `config/services_test.yaml`, the spy is already configured:

```yaml
App\Shared\Application\Observability\BusinessMetricsEmitterInterface: '@App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy'

App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy:
  public: true
```

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
```

---

## What NOT to Track

Remember: AWS AppRunner already provides infrastructure metrics.

**Don't track:**

- Request latency
- Error rates
- Response times
- HTTP status codes
- Memory usage
- CPU usage

**Do track:**

- Business events (orders placed, customers created)
- Business values (order amounts, payment totals)
- Domain-specific actions (logins, uploads, exports)

---

## Success Criteria

After implementing business metrics:

- Each API endpoint emits relevant business metrics
- Metrics follow EMF format for CloudWatch extraction
- Dimensions provide meaningful segmentation
- Unit tests verify metric emission
- No infrastructure metrics (AppRunner handles those)
- Metrics focus on business value, not technical performance

---

## Files Reference

### Implementation Files

- `src/Shared/Application/Observability/BusinessMetricsEmitterInterface.php` - Interface
- `src/Shared/Infrastructure/Observability/AwsEmfBusinessMetricsEmitter.php` - EMF implementation
- `src/Shared/Infrastructure/Observability/ApiEndpointBusinessMetricsSubscriber.php` - Auto metrics
- `src/Shared/Infrastructure/Observability/ApiEndpointMetricDimensionsResolver.php` - Dimension resolver

### Test Files

- `tests/Unit/Shared/Infrastructure/Observability/AwsEmfBusinessMetricsEmitterTest.php`
- `tests/Unit/Shared/Infrastructure/Observability/ApiEndpointBusinessMetricsSubscriberTest.php`
- `tests/Unit/Shared/Infrastructure/Observability/ApiEndpointMetricDimensionsResolverTest.php`
- `tests/Unit/Shared/Infrastructure/Observability/BusinessMetricsEmitterSpy.php`
- `tests/Integration/ObservabilityBusinessMetricsTest.php`

### Configuration

- `config/services.yaml` - Production wiring
- `config/services_test.yaml` - Test spy wiring

---

## AWS Documentation

- [CloudWatch Embedded Metric Format](https://docs.aws.amazon.com/AmazonCloudWatch/latest/monitoring/CloudWatch_Embedded_Metric_Format_Specification.html)
- [AWS App Runner Metrics](https://docs.aws.amazon.com/apprunner/latest/dg/monitor-cw.html)

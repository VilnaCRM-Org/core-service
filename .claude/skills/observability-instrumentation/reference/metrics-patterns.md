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

## Architecture: Metric Classes + Event Subscribers

Business metrics use **typed classes** (not arrays) and are emitted via **event subscribers** (not hardcoded in handlers).

### Metric Class Pattern

```php
use App\Shared\Application\Observability\Metric\EndpointOperationBusinessMetric;
use App\Shared\Application\Observability\Metric\MetricDimensionsFactoryInterface;
use App\Shared\Application\Observability\Metric\MetricUnit;

final readonly class CustomersCreatedMetric extends EndpointOperationBusinessMetric
{
    public function __construct(
        MetricDimensionsFactoryInterface $dimensionsFactory,
        float|int $value = 1
    ) {
        parent::__construct($dimensionsFactory, $value, MetricUnit::COUNT);
    }

    public function name(): string
    {
        return 'CustomersCreated';
    }

    protected function endpoint(): string
    {
        return 'Customer';
    }

    protected function operation(): string
    {
        return 'create';
    }
}
```

### Event Subscriber Pattern

```php
use App\Core\Customer\Application\Factory\CustomersCreatedMetricFactoryInterface;
use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use Psr\Log\LoggerInterface;

final readonly class CustomerCreatedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private CustomersCreatedMetricFactoryInterface $metricFactory,
        private LoggerInterface $logger
    ) {}

    public function __invoke(CustomerCreatedEvent $event): void
    {
        try {
            $this->metricsEmitter->emit($this->metricFactory->create());

            $this->logger->debug('Business metric emitted', [
                'metric' => 'CustomersCreated',
                'event_id' => $event->eventId(),
            ]);
        } catch (\Throwable $e) {
            // Metrics are best-effort: don't fail business operations
            $this->logger->warning('Failed to emit business metric', [
                'metric' => 'CustomersCreated',
                'event_id' => $event->eventId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [CustomerCreatedEvent::class];
    }
}
```

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
  "Operation": "create",
  "CustomersCreated": 1
}
```

When written to stdout via Monolog EMF channel, CloudWatch automatically:

1. Extracts `CustomersCreated` as a metric
2. Associates it with the `CCore/BusinessMetrics` namespace
3. Applies dimensions `Endpoint` and `Operation`

---

## Using BusinessMetricsEmitterInterface

### Emit Single Metric

```php
// In an event subscriber (metric factory injected via constructor)
$this->metricsEmitter->emit($this->customersCreatedMetricFactory->create());
```

### Emit Multiple Metrics

```php
use App\Shared\Application\Observability\Metric\MetricCollection;

$this->metricsEmitter->emitCollection(new MetricCollection(
    $this->ordersPlacedMetricFactory->create($paymentMethod),
    $this->orderValueMetricFactory->create($totalAmount)
));
```

---

## Metric Naming Convention

### Format

```text
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
// Customer registration - via event subscriber
final readonly class CustomerCreatedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __invoke(CustomerCreatedEvent $event): void
    {
        $this->metricsEmitter->emit($this->customersCreatedMetricFactory->create());
    }
}

// Customer update - via event subscriber
final readonly class CustomerUpdatedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __invoke(CustomerUpdatedEvent $event): void
    {
        $this->metricsEmitter->emit($this->customersUpdatedMetricFactory->create());
    }
}

// Customer deletion - via event subscriber
final readonly class CustomerDeletedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __invoke(CustomerDeletedEvent $event): void
    {
        $this->metricsEmitter->emit($this->customersDeletedMetricFactory->create());
    }
}
```

### Order Domain

```php
// Order placed - multiple metrics
final readonly class OrderPlacedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __invoke(OrderPlacedEvent $event): void
    {
        $this->metricsEmitter->emitCollection(new MetricCollection(
            new OrdersPlacedMetric($event->paymentMethod()),
            new OrderValueMetric($event->totalAmount()),
            new OrderItemCountMetric($event->itemCount())
        ));
    }
}
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
use App\Shared\Application\Observability\Metric\MetricDimension;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;

final class CustomerCreatedMetricsSubscriberTest extends TestCase
{
    public function testEmitsCustomerCreatedMetric(): void
    {
        $metricsSpy = new BusinessMetricsEmitterSpy();
        $dimensionsFactory = new MetricDimensionsFactory();
        $logger = $this->createMock(LoggerInterface::class);

        $subscriber = new CustomerCreatedMetricsSubscriber(
            $metricsSpy,
            $dimensionsFactory,
            $logger
        );

        $event = new CustomerCreatedEvent($customerId, $email);
        ($subscriber)($event);

        self::assertSame(1, $metricsSpy->count());

        foreach ($metricsSpy->emitted() as $metric) {
            self::assertSame('CustomersCreated', $metric->name());
            self::assertSame(1, $metric->value());
            self::assertSame('Customer', $metric->dimensions()->values()->get('Endpoint'));
        }
    }

    public function testEmitsMetricWithCorrectDimensions(): void
    {
        $metricsSpy = new BusinessMetricsEmitterSpy();
        $dimensionsFactory = new MetricDimensionsFactory();
        $logger = $this->createMock(LoggerInterface::class);

        $subscriber = new CustomerCreatedMetricsSubscriber(
            $metricsSpy,
            $dimensionsFactory,
            $logger
        );

        $event = new CustomerCreatedEvent($customerId, $email);
        ($subscriber)($event);

        $metricsSpy->assertEmittedWithDimensions(
            'CustomersCreated',
            new MetricDimension('Endpoint', 'Customer'),
            new MetricDimension('Operation', 'create')
        );
    }
}
```

---

## Success Criteria

- Metrics use typed classes extending `BusinessMetric`
- Metrics are emitted via domain event subscribers (not in handlers)
- Metric names use PascalCase naming convention
- Dimensions have low cardinality (no IDs, timestamps)
- Unit tests verify metric emission
- EMF format outputs to stdout via Monolog for CloudWatch extraction
- Namespace is `CCore/BusinessMetrics`

---

**Next Steps**:

- [Quick Start Guide](quick-start.md) - Add business metrics to your code
- [Structured Logging](structured-logging.md) - Add correlation IDs for debugging
- [Complete Example](../examples/instrumented-command-handler.md) - Full working example

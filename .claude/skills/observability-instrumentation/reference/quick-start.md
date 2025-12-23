# Quick Start: Business Metrics with AWS EMF

Add business metrics to your code in 5 minutes using AWS CloudWatch Embedded Metric Format.

## What You'll Add

- **Business metrics** - Track domain events (CustomersCreated, OrdersPlaced)
- **EMF format** - Logs automatically become CloudWatch metrics
- **Low overhead** - Emit metrics in dedicated domain event subscribers

## The 3-Step Pattern

### Step 1: Create a domain event subscriber (30 seconds)

```php
use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

final readonly class YourMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private BusinessMetricsEmitterInterface $metricsEmitter
    ) {}
}
```

### Step 2: Emit the metric from the subscriber (1 minute)

```php
public function __invoke(YourEntityCreatedEvent $event): void
{
    // Metrics are best-effort: keep business flow resilient
    $this->metricsEmitter->emit(new EntitiesCreatedMetric());
}
```

### Step 3: Add Test (2 minutes)

```php
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;

public function testEmitsBusinessMetric(): void
{
    $metricsSpy = new BusinessMetricsEmitterSpy();
    $subscriber = new YourMetricsSubscriber($metricsSpy);

    ($subscriber)(new YourEntityCreatedEvent(/* ... */));

    $metricsSpy->assertEmittedWithDimensions('EntitiesCreated', [
        'Endpoint' => 'YourEntity',
        'Operation' => 'create',
    ]);
}
```

**Done! Your business metric will appear in CloudWatch.**

---

## Copy-Paste Template

```php
<?php

declare(strict_types=1);

namespace App\YourContext\Application\EventSubscriber;

use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Metric\EndpointOperationBusinessMetric;
use App\Shared\Application\Observability\Metric\MetricUnit;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

final readonly class EntitiesCreatedMetric extends EndpointOperationBusinessMetric
{
    public function __construct(float|int $value = 1)
    {
        parent::__construct($value, MetricUnit::COUNT);
    }

    public function name(): string
    {
        return 'EntitiesCreated';
    }

    protected function endpoint(): string
    {
        return 'YourEntity';
    }

    protected function operation(): string
    {
        return 'create';
    }
}

final readonly class YourEntityCreatedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(private BusinessMetricsEmitterInterface $metricsEmitter) {}

    public function __invoke(YourEntityCreatedEvent $event): void
    {
        $this->metricsEmitter->emit(new EntitiesCreatedMetric());
    }

    public function subscribedTo(): array
    {
        return [YourEntityCreatedEvent::class];
    }
}
```

---

## Common Business Metrics

### For Create Operations

```php
$this->metricsEmitter->emit(new CustomersCreatedMetric());
```

### For Update Operations

```php
$this->metricsEmitter->emit(new CustomersUpdatedMetric());
```

### For Delete Operations

```php
$this->metricsEmitter->emit(new CustomersDeletedMetric());
```

### For Business Values

```php
use App\Shared\Application\Observability\Metric\MetricCollection;

$this->metricsEmitter->emitCollection(new MetricCollection(
    new OrdersPlacedMetric($order->paymentMethod()),
    new OrderValueMetric($order->totalAmount())
));
```

---

## Automatic Endpoint Metrics

The codebase already automatically emits `EndpointInvocations` for every `/api` request via `ApiEndpointBusinessMetricsSubscriber`. You don't need to add anything for basic endpoint tracking.

Your job is to add **domain-specific business metrics** that track business events.

---

## Quick Reference

### Metric Naming

| Pattern             | Example                                       |
| ------------------- | --------------------------------------------- |
| `{Entity}{Action}`  | `CustomersCreated`, `OrdersPlaced`            |
| PascalCase          | `PaymentsProcessed`, not `payments_processed` |
| Plural + Past tense | `LoginAttempts`, not `LoginAttempt`           |

### Dimensions (Low Cardinality Only)

| Good Dimensions | Bad Dimensions (Avoid!) |
| --------------- | ----------------------- |
| `Endpoint`      | `CustomerId`            |
| `Operation`     | `OrderId`               |
| `PaymentMethod` | `SessionId`             |
| `CustomerType`  | `Timestamp`             |

---

## Test Template

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\YourContext\Application\CommandHandler;

use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use App\YourContext\Application\CommandHandler\YourCommandHandler;
use App\YourContext\Application\Command\YourCommand;

final class YourCommandHandlerTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsSpy;
    private YourCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->metricsSpy = new BusinessMetricsEmitterSpy();
        $this->handler = new YourCommandHandler(
            $this->createMock(YourRepositoryInterface::class),
            $this->metricsSpy
        );
    }

    public function testEmitsBusinessMetricOnSuccess(): void
    {
        ($this->handler)(new YourCommand(/* ... */));

        $this->metricsSpy->assertEmittedWithDimensions('EntitiesCreated', [
            'Endpoint' => 'YourEntity',
            'Operation' => 'create',
        ]);
    }
}
```

---

## What NOT to Track

AWS AppRunner provides infrastructure metrics automatically. Don't add:

- ❌ Request latency
- ❌ Error rates
- ❌ Response times
- ❌ RPS (requests per second)
- ❌ HTTP status codes

Focus ONLY on business events.

---

## Verification Checklist

After implementing:

- [ ] Handler injects `BusinessMetricsEmitterInterface`
- [ ] Metric uses PascalCase name (e.g., `CustomersCreated`)
- [ ] Dimensions are low cardinality (no IDs)
- [ ] Unit test verifies metric emission
- [ ] Run `make test` to confirm tests pass

---

## Full Guides

- [Metrics Patterns](metrics-patterns.md) - Complete business metrics guide
- [Structured Logging](structured-logging.md) - Add correlation IDs for debugging
- [PR Evidence Guide](pr-evidence-guide.md) - How to document metrics in PRs
- [Complete Example](../examples/instrumented-command-handler.md) - Full working example

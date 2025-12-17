# Quick Start: Business Metrics with AWS EMF

Add business metrics to your code in 5 minutes using AWS CloudWatch Embedded Metric Format.

## What You'll Add

- **Business metrics** - Track domain events (CustomersCreated, OrdersPlaced)
- **EMF format** - Logs automatically become CloudWatch metrics
- **Low overhead** - Just inject the interface and emit

## The 3-Step Pattern

### Step 1: Inject the Interface (30 seconds)

```php
use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;

final readonly class YourCommandHandler
{
    public function __construct(
        private YourRepository $repository,
        private BusinessMetricsEmitterInterface $metrics  // Add this
    ) {}
}
```

### Step 2: Emit Business Metric (1 minute)

```php
public function __invoke(YourCommand $command): void
{
    // Your existing business logic
    $entity = $this->createEntity($command);
    $this->repository->save($entity);

    // Emit business metric
    $this->metrics->emit('EntitiesCreated', 1, [
        'Endpoint' => 'YourEntity',
        'Operation' => 'create',
    ]);
}
```

### Step 3: Add Test (2 minutes)

```php
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;

public function testEmitsBusinessMetric(): void
{
    $metricsSpy = new BusinessMetricsEmitterSpy();
    $handler = new YourCommandHandler($repository, $metricsSpy);

    $handler(new YourCommand(/* ... */));

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

namespace App\YourContext\Application\CommandHandler;

use App\YourContext\Application\Command\YourCommand;
use App\YourContext\Domain\Repository\YourRepositoryInterface;
use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;

final readonly class YourCommandHandler
{
    public function __construct(
        private YourRepositoryInterface $repository,
        private BusinessMetricsEmitterInterface $metrics
    ) {}

    public function __invoke(YourCommand $command): void
    {
        // YOUR BUSINESS LOGIC HERE
        $entity = YourEntity::create(/* ... */);
        $this->repository->save($entity);

        // EMIT BUSINESS METRIC
        $this->metrics->emit('EntitiesCreated', 1, [
            'Endpoint' => 'YourEntity',
            'Operation' => 'create',
        ]);
    }
}
```

---

## Common Business Metrics

### For Create Operations

```php
$this->metrics->emit('CustomersCreated', 1, [
    'Endpoint' => 'Customer',
    'Operation' => 'create',
]);
```

### For Update Operations

```php
$this->metrics->emit('CustomersUpdated', 1, [
    'Endpoint' => 'Customer',
    'Operation' => 'update',
]);
```

### For Delete Operations

```php
$this->metrics->emit('CustomersDeleted', 1, [
    'Endpoint' => 'Customer',
    'Operation' => 'delete',
]);
```

### For Business Values

```php
$this->metrics->emitMultiple([
    'OrdersPlaced' => ['value' => 1, 'unit' => 'Count'],
    'OrderValue' => ['value' => $order->totalAmount(), 'unit' => 'None'],
], [
    'Endpoint' => 'Order',
    'PaymentMethod' => $order->paymentMethod(),
]);
```

---

## Automatic Endpoint Metrics

The codebase already automatically emits `EndpointInvocations` for every `/api` request via `ApiEndpointBusinessMetricsSubscriber`. You don't need to add anything for basic endpoint tracking.

Your job is to add **domain-specific business metrics** that track business events.

---

## Quick Reference

### Metric Naming

| Pattern | Example |
|---------|---------|
| `{Entity}{Action}` | `CustomersCreated`, `OrdersPlaced` |
| PascalCase | `PaymentsProcessed`, not `payments_processed` |
| Plural + Past tense | `LoginAttempts`, not `LoginAttempt` |

### Dimensions (Low Cardinality Only)

| Good Dimensions | Bad Dimensions (Avoid!) |
|-----------------|------------------------|
| `Endpoint` | `CustomerId` |
| `Operation` | `OrderId` |
| `PaymentMethod` | `SessionId` |
| `CustomerType` | `Timestamp` |

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

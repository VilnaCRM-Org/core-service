# Complete Example: Command Handler with Business Metrics

This example demonstrates a fully instrumented command handler with AWS EMF business metrics.

## Scenario

Creating a new customer with:

- Business metric emission via AWS EMF
- Proper dimension usage
- Unit test coverage

---

## Full Implementation

```php
<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerEmail;
use App\Core\Customer\Domain\ValueObject\CustomerName;
use App\Shared\Domain\Bus\Event\DomainEventPublisherInterface;

final readonly class CreateCustomerCommandHandler
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private DomainEventPublisherInterface $publisher
    ) {}

    public function __invoke(CreateCustomerCommand $command): void
    {
        // 1. Create domain entity
        $customer = Customer::create(
            id: $command->id,
            name: CustomerName::fromString($command->name),
            email: CustomerEmail::fromString($command->email)
        );

        // 2. Persist to repository
        $this->repository->save($customer);

        // 3. Publish domain events
        $events = $customer->pullDomainEvents();
        $this->publisher->publish(...$events);

        // 4. Metrics are emitted in domain event subscribers (best practice)
    }
}
```

---

## EMF Output

When this handler executes, the following EMF log is written to stdout:

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

CloudWatch automatically extracts this as a metric in the `CCore/BusinessMetrics` namespace.

---

## Unit Test for Event Subscriber

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\EventSubscriber;

use App\Core\Customer\Application\EventSubscriber\CustomerCreatedMetricsSubscriber;
use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Shared\Application\Observability\Metric\MetricDimension;
use App\Shared\Infrastructure\Observability\Factory\MetricDimensionsFactory;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class CustomerCreatedMetricsSubscriberTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsSpy;
    private LoggerInterface&MockObject $logger;
    private CustomerCreatedMetricsSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metricsSpy = new BusinessMetricsEmitterSpy();
        $this->logger = $this->createMock(LoggerInterface::class);

        $dimensionsFactory = new MetricDimensionsFactory();
        $metricFactory = new \App\Core\Customer\Application\Factory\CustomersCreatedMetricFactory($dimensionsFactory);

        $this->subscriber = new CustomerCreatedMetricsSubscriber(
            $this->metricsSpy,
            $metricFactory,
            $this->logger
        );
    }

    public function testEmitsCustomerCreatedMetric(): void
    {
        $event = new CustomerCreatedEvent(
            customerId: '01JCXYZ1234567890ABCDEFGH',
            customerEmail: 'john.doe@example.com'
        );

        ($this->subscriber)($event);

        self::assertSame(1, $this->metricsSpy->count());

        foreach ($this->metricsSpy->emitted() as $metric) {
            self::assertSame('CustomersCreated', $metric->name());
            self::assertSame(1, $metric->value());
            self::assertSame('Customer', $metric->dimensions()->values()->get('Endpoint'));
            self::assertSame('create', $metric->dimensions()->values()->get('Operation'));
        }
    }

    public function testMetricHasCorrectDimensions(): void
    {
        $event = new CustomerCreatedEvent(
            customerId: '01JCXYZ1234567890ABCDEFGH',
            customerEmail: 'john.doe@example.com'
        );

        ($this->subscriber)($event);

        $this->metricsSpy->assertEmittedWithDimensions(
            'CustomersCreated',
            new MetricDimension('Endpoint', 'Customer'),
            new MetricDimension('Operation', 'create')
        );
    }

    public function testSubscribesToCorrectEvent(): void
    {
        $subscribedEvents = $this->subscriber->subscribedTo();

        self::assertCount(1, $subscribedEvents);
        self::assertContains(CustomerCreatedEvent::class, $subscribedEvents);
    }
}
```

---

## Example: Multiple Metrics via Event Subscriber

For operations that track multiple business values, use `MetricCollection` in an event subscriber:

```php
<?php

declare(strict_types=1);

namespace App\Core\Order\Application\EventSubscriber;

use App\Core\Order\Application\Factory\OrderItemCountMetricFactoryInterface;
use App\Core\Order\Application\Factory\OrdersPlacedMetricFactoryInterface;
use App\Core\Order\Application\Factory\OrderValueMetricFactoryInterface;
use App\Core\Order\Domain\Event\OrderPlacedEvent;
use App\Shared\Application\Observability\Emitter\BusinessMetricsEmitterInterface;
use App\Shared\Application\Observability\Metric\MetricCollection;
use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use Psr\Log\LoggerInterface;

final readonly class OrderPlacedMetricsSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private BusinessMetricsEmitterInterface $metricsEmitter,
        private OrdersPlacedMetricFactoryInterface $ordersPlacedMetricFactory,
        private OrderValueMetricFactoryInterface $orderValueMetricFactory,
        private OrderItemCountMetricFactoryInterface $orderItemCountMetricFactory,
        private LoggerInterface $logger
    ) {}

    public function __invoke(OrderPlacedEvent $event): void
    {
        // Error handling is automatic via DomainEventMessageHandler.
        // Subscribers are executed in async workers - failures are logged + emit metrics.
        // This ensures observability never breaks the main request (AP from CAP).
        $this->metricsEmitter->emitCollection(new MetricCollection(
            $this->ordersPlacedMetricFactory->create($event->paymentMethod()),
            $this->orderValueMetricFactory->create($event->totalAmount()),
            $this->orderItemCountMetricFactory->create($event->itemCount())
        ));
    }

    /**
     * @return array<class-string>
     */
    public function subscribedTo(): array
    {
        return [OrderPlacedEvent::class];
    }
}
```

### EMF Output for Multiple Metrics

```json
{
  "_aws": {
    "Timestamp": 1702425600000,
    "CloudWatchMetrics": [
      {
        "Namespace": "CCore/BusinessMetrics",
        "Dimensions": [["Endpoint", "Operation", "PaymentMethod"]],
        "Metrics": [
          { "Name": "OrdersPlaced", "Unit": "Count" },
          { "Name": "OrderValue", "Unit": "None" },
          { "Name": "OrderItemCount", "Unit": "Count" }
        ]
      }
    ]
  },
  "Endpoint": "Order",
  "Operation": "create",
  "PaymentMethod": "credit_card",
  "OrdersPlaced": 1,
  "OrderValue": 299.99,
  "OrderItemCount": 3
}
```

---

## Example: Conditional Metrics

For operations with different outcomes:

```php
<?php

declare(strict_types=1);

namespace App\Core\Auth\Application\CommandHandler;

use App\Core\Auth\Application\Command\LoginCommand;
final readonly class LoginCommandHandler
{
    public function __construct(
        private AuthServiceInterface $authService
    ) {}

    public function __invoke(LoginCommand $command): void
    {
        $result = $this->authService->authenticate(
            $command->username,
            $command->password
        );

        // Publish a domain event and emit metrics in a dedicated subscriber (best practice)

        if (!$result->isSuccess()) {
            throw new AuthenticationFailedException();
        }
    }
}
```

---

## CloudWatch Queries

After deploying, query your business metrics:

```sql
-- Total customers created
SELECT SUM(CustomersCreated)
FROM "CCore/BusinessMetrics"
WHERE Endpoint = 'Customer'

-- Orders by payment method
SELECT SUM(OrdersPlaced), AVG(OrderValue)
FROM "CCore/BusinessMetrics"
WHERE Endpoint = 'Order'
GROUP BY PaymentMethod

-- Login success rate
SELECT SUM(LoginAttempts)
FROM "CCore/BusinessMetrics"
WHERE Endpoint = 'Auth'
GROUP BY Result
```

---

## Key Takeaways

1. **Inject `BusinessMetricsEmitterInterface`** in constructor
2. **Emit after successful operation** - metric represents completed business event
3. **Use PascalCase** for metric names
4. **Keep dimensions low cardinality** - no IDs, timestamps, or email addresses
5. **Test with `BusinessMetricsEmitterSpy`** to verify emission
6. **Focus on business value** - not infrastructure metrics

---

## What NOT to Include

AWS AppRunner already provides infrastructure metrics. Don't add:

- ❌ Operation duration/latency
- ❌ Error counters
- ❌ Request counts (use automatic `EndpointInvocations`)
- ❌ HTTP status codes
- ❌ Database query timing

These are infrastructure concerns handled by AWS AppRunner automatically.

---

## Files Reference

- Interface: `src/Shared/Application/Observability/Emitter/BusinessMetricsEmitterInterface.php`
- Implementation: `src/Shared/Infrastructure/Observability/AwsEmfBusinessMetricsEmitter.php`
- Test spy: `tests/Unit/Shared/Infrastructure/Observability/BusinessMetricsEmitterSpy.php`
- Auto metrics: `src/Shared/Infrastructure/Observability/ApiEndpointBusinessMetricsSubscriber.php`

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
use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;
use App\Shared\Domain\Bus\Event\DomainEventPublisherInterface;

final readonly class CreateCustomerCommandHandler
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private DomainEventPublisherInterface $publisher,
        private BusinessMetricsEmitterInterface $metrics
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

        // 4. Emit business metric
        $this->metrics->emit('CustomersCreated', 1, [
            'Endpoint' => 'Customer',
            'Operation' => 'create',
        ]);
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
        "Metrics": [
          { "Name": "CustomersCreated", "Unit": "Count" }
        ]
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

## Unit Test

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Application\CommandHandler\CreateCustomerCommandHandler;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Event\DomainEventPublisherInterface;
use App\Tests\Unit\Shared\Infrastructure\Observability\BusinessMetricsEmitterSpy;
use App\Tests\Unit\UnitTestCase;

final class CreateCustomerCommandHandlerTest extends UnitTestCase
{
    private BusinessMetricsEmitterSpy $metricsSpy;
    private CreateCustomerCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metricsSpy = new BusinessMetricsEmitterSpy();

        $this->handler = new CreateCustomerCommandHandler(
            $this->createMock(CustomerRepositoryInterface::class),
            $this->createMock(DomainEventPublisherInterface::class),
            $this->metricsSpy
        );
    }

    public function testEmitsCustomerCreatedMetric(): void
    {
        $command = new CreateCustomerCommand(
            id: '01JCXYZ1234567890ABCDEFGH',
            name: 'John Doe',
            email: 'john.doe@example.com'
        );

        ($this->handler)($command);

        $emitted = $this->metricsSpy->emitted();

        self::assertCount(1, $emitted);
        self::assertSame('CustomersCreated', $emitted[0]['name']);
        self::assertSame(1, $emitted[0]['value']);
        self::assertSame('Count', $emitted[0]['unit']);
    }

    public function testMetricHasCorrectDimensions(): void
    {
        $command = new CreateCustomerCommand(
            id: '01JCXYZ1234567890ABCDEFGH',
            name: 'John Doe',
            email: 'john.doe@example.com'
        );

        ($this->handler)($command);

        $this->metricsSpy->assertEmittedWithDimensions('CustomersCreated', [
            'Endpoint' => 'Customer',
            'Operation' => 'create',
        ]);
    }
}
```

---

## Example: Multiple Metrics

For operations that track multiple business values:

```php
<?php

declare(strict_types=1);

namespace App\Core\Order\Application\CommandHandler;

use App\Core\Order\Application\Command\PlaceOrderCommand;
use App\Core\Order\Domain\Entity\Order;
use App\Core\Order\Domain\Repository\OrderRepositoryInterface;
use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;

final readonly class PlaceOrderCommandHandler
{
    public function __construct(
        private OrderRepositoryInterface $repository,
        private BusinessMetricsEmitterInterface $metrics
    ) {}

    public function __invoke(PlaceOrderCommand $command): void
    {
        // Create and save order
        $order = Order::place(/* ... */);
        $this->repository->save($order);

        // Emit multiple business metrics
        $this->metrics->emitMultiple([
            'OrdersPlaced' => ['value' => 1, 'unit' => 'Count'],
            'OrderValue' => ['value' => $order->totalAmount(), 'unit' => 'None'],
            'OrderItemCount' => ['value' => $order->itemCount(), 'unit' => 'Count'],
        ], [
            'Endpoint' => 'Order',
            'PaymentMethod' => $order->paymentMethod(),
        ]);
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
        "Dimensions": [["Endpoint", "PaymentMethod"]],
        "Metrics": [
          { "Name": "OrdersPlaced", "Unit": "Count" },
          { "Name": "OrderValue", "Unit": "None" },
          { "Name": "OrderItemCount", "Unit": "Count" }
        ]
      }
    ]
  },
  "Endpoint": "Order",
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
use App\Shared\Application\Observability\BusinessMetricsEmitterInterface;

final readonly class LoginCommandHandler
{
    public function __construct(
        private AuthServiceInterface $authService,
        private BusinessMetricsEmitterInterface $metrics
    ) {}

    public function __invoke(LoginCommand $command): void
    {
        $result = $this->authService->authenticate(
            $command->username,
            $command->password
        );

        // Emit metric based on outcome
        $this->metrics->emit('LoginAttempts', 1, [
            'Endpoint' => 'Auth',
            'Result' => $result->isSuccess() ? 'success' : 'failure',
        ]);

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

- Interface: `src/Shared/Application/Observability/BusinessMetricsEmitterInterface.php`
- Implementation: `src/Shared/Infrastructure/Observability/AwsEmfBusinessMetricsEmitter.php`
- Test spy: `tests/Unit/Shared/Infrastructure/Observability/BusinessMetricsEmitterSpy.php`
- Auto metrics: `src/Shared/Infrastructure/Observability/ApiEndpointBusinessMetricsSubscriber.php`

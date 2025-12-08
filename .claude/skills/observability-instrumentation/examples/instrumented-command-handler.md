# Complete Example: Instrumented Command Handler

This example demonstrates a fully instrumented command handler with all three pillars of observability: **Logs, Metrics, and Traces**.

## Scenario

Creating a new customer with:
- Structured logging with correlation ID
- Latency and error metrics
- Database operation tracing
- Email service HTTP call tracing

---

## Full Implementation

```php
<?php

declare(strict_types=1);

namespace App\Customer\Application\CommandHandler;

use App\Customer\Application\Command\CreateCustomerCommand;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Customer\Domain\ValueObject\CustomerEmail;
use App\Customer\Domain\ValueObject\CustomerName;
use App\Shared\Application\Service\MetricsCollector;
use App\Shared\Domain\Bus\Event\DomainEventPublisherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class CreateCustomerCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private DomainEventPublisherInterface $publisher,
        private LoggerInterface $logger,
        private MetricsCollector $metrics,
        private EmailService $emailService
    ) {}

    public function __invoke(CreateCustomerCommand $command): void
    {
        $correlationId = $this->generateCorrelationId();
        $startTime = microtime(true);

        // 1. LOG: Start of operation
        $this->logger->info('Processing CreateCustomerCommand', [
            'correlation_id' => $correlationId,
            'command' => CreateCustomerCommand::class,
            'customer_id' => $command->id,
            'customer_email' => $command->email,
            'timestamp' => time(),
        ]);

        try {
            // 2. Domain logic
            $customer = $this->createCustomerWithTrace($command, $correlationId);

            // 3. TRACE: Database operation
            $this->saveCustomerWithTrace($customer, $correlationId);

            // 4. TRACE: External service call
            $this->sendWelcomeEmailWithTrace($customer, $correlationId);

            // 5. Publish domain events
            $events = $customer->pullDomainEvents();
            $this->publishEventsWithTrace($events, $correlationId);

            // 6. METRICS: Success metrics
            $duration = (microtime(true) - $startTime) * 1000;
            $this->metrics->record('customer.create.duration', $duration, [
                'status' => 'success',
            ]);
            $this->metrics->increment('customer.create.total');

            // 7. LOG: Success
            $this->logger->info('Customer created successfully', [
                'correlation_id' => $correlationId,
                'customer_id' => $customer->id()->value(),
                'duration_ms' => round($duration, 2),
            ]);

        } catch (\Throwable $e) {
            // 8. Handle errors with full observability
            $this->handleErrorWithTrace($e, $command, $correlationId, $startTime);

            throw $e;
        }
    }

    private function createCustomerWithTrace(
        CreateCustomerCommand $command,
        string $correlationId
    ): Customer {
        $this->logger->debug('Creating customer domain entity', [
            'correlation_id' => $correlationId,
            'customer_id' => $command->id,
        ]);

        $customer = Customer::create(
            id: $command->id,
            name: CustomerName::fromString($command->name),
            email: CustomerEmail::fromString($command->email)
        );

        $this->logger->debug('Customer domain entity created', [
            'correlation_id' => $correlationId,
            'customer_id' => $customer->id()->value(),
        ]);

        return $customer;
    }

    private function saveCustomerWithTrace(Customer $customer, string $correlationId): void
    {
        $startTime = microtime(true);

        $this->logger->debug('Saving customer to MongoDB', [
            'correlation_id' => $correlationId,
            'customer_id' => $customer->id()->value(),
            'operation' => 'mongodb.save',
        ]);

        try {
            $this->repository->save($customer);

            $duration = (microtime(true) - $startTime) * 1000;

            // METRICS: Database operation timing
            $this->metrics->record('mongodb.customer.save.duration', $duration);

            $this->logger->info('Customer saved to MongoDB', [
                'correlation_id' => $correlationId,
                'customer_id' => $customer->id()->value(),
                'operation' => 'mongodb.save',
                'duration_ms' => round($duration, 2),
            ]);

        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            $this->logger->error('Failed to save customer to MongoDB', [
                'correlation_id' => $correlationId,
                'customer_id' => $customer->id()->value(),
                'operation' => 'mongodb.save',
                'duration_ms' => round($duration, 2),
                'error' => $e->getMessage(),
            ]);

            // METRICS: Database error
            $this->metrics->increment('mongodb.customer.save.errors', [
                'error_type' => get_class($e),
            ]);

            throw $e;
        }
    }

    private function sendWelcomeEmailWithTrace(Customer $customer, string $correlationId): void
    {
        $startTime = microtime(true);

        $this->logger->info('Sending welcome email', [
            'correlation_id' => $correlationId,
            'customer_id' => $customer->id()->value(),
            'customer_email' => $customer->email()->value(),
            'operation' => 'http.email.send',
        ]);

        try {
            $this->emailService->sendWelcomeEmail(
                email: $customer->email()->value(),
                name: $customer->name()->value()
            );

            $duration = (microtime(true) - $startTime) * 1000;

            // METRICS: Email service timing
            $this->metrics->record('email.send.duration', $duration, [
                'email_type' => 'welcome',
            ]);
            $this->metrics->increment('email.sent.total', [
                'email_type' => 'welcome',
            ]);

            $this->logger->info('Welcome email sent successfully', [
                'correlation_id' => $correlationId,
                'customer_id' => $customer->id()->value(),
                'operation' => 'http.email.send',
                'duration_ms' => round($duration, 2),
            ]);

        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            $this->logger->warning('Failed to send welcome email (non-critical)', [
                'correlation_id' => $correlationId,
                'customer_id' => $customer->id()->value(),
                'operation' => 'http.email.send',
                'duration_ms' => round($duration, 2),
                'error' => $e->getMessage(),
            ]);

            // METRICS: Email error (non-critical, don't rethrow)
            $this->metrics->increment('email.send.errors', [
                'email_type' => 'welcome',
                'error_type' => get_class($e),
            ]);

            // Don't throw - email failure shouldn't block customer creation
        }
    }

    private function publishEventsWithTrace(array $events, string $correlationId): void
    {
        $eventCount = count($events);

        $this->logger->debug('Publishing domain events', [
            'correlation_id' => $correlationId,
            'event_count' => $eventCount,
        ]);

        $this->publisher->publish(...$events);

        $this->logger->debug('Domain events published', [
            'correlation_id' => $correlationId,
            'event_count' => $eventCount,
        ]);

        // METRICS: Event publishing
        $this->metrics->increment('domain.events.published', [
            'event_type' => 'customer',
        ], $eventCount);
    }

    private function handleErrorWithTrace(
        \Throwable $e,
        CreateCustomerCommand $command,
        string $correlationId,
        float $startTime
    ): void {
        $duration = (microtime(true) - $startTime) * 1000;

        // LOG: Comprehensive error logging
        $this->logger->error('Failed to create customer', [
            'correlation_id' => $correlationId,
            'command' => CreateCustomerCommand::class,
            'customer_id' => $command->id,
            'customer_email' => $command->email,
            'duration_ms' => round($duration, 2),
            'error_type' => get_class($e),
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        // METRICS: Error tracking
        $this->metrics->record('customer.create.duration', $duration, [
            'status' => 'error',
        ]);
        $this->metrics->increment('customer.create.errors', [
            'error_type' => get_class($e),
        ]);
    }

    private function generateCorrelationId(): string
    {
        // In production, extract from request headers if available
        // For now, generate a new UUID v4
        return Uuid::v4()->toString();
    }
}
```

---

## Sample Log Output

```json
{
  "level": "info",
  "message": "Processing CreateCustomerCommand",
  "context": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "command": "App\\Customer\\Application\\Command\\CreateCustomerCommand",
    "customer_id": "01JCXYZ1234567890ABCDEFGH",
    "customer_email": "john.doe@example.com",
    "timestamp": 1702425600
  }
}

{
  "level": "debug",
  "message": "Saving customer to MongoDB",
  "context": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "customer_id": "01JCXYZ1234567890ABCDEFGH",
    "operation": "mongodb.save"
  }
}

{
  "level": "info",
  "message": "Customer saved to MongoDB",
  "context": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "customer_id": "01JCXYZ1234567890ABCDEFGH",
    "operation": "mongodb.save",
    "duration_ms": 12.45
  }
}

{
  "level": "info",
  "message": "Sending welcome email",
  "context": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "customer_id": "01JCXYZ1234567890ABCDEFGH",
    "customer_email": "john.doe@example.com",
    "operation": "http.email.send"
  }
}

{
  "level": "info",
  "message": "Welcome email sent successfully",
  "context": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "customer_id": "01JCXYZ1234567890ABCDEFGH",
    "operation": "http.email.send",
    "duration_ms": 234.56
  }
}

{
  "level": "info",
  "message": "Customer created successfully",
  "context": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "customer_id": "01JCXYZ1234567890ABCDEFGH",
    "duration_ms": 278.34
  }
}
```

---

## Metrics Recorded

```
customer.create.duration: 278.34ms (status=success)
customer.create.total: 1
mongodb.customer.save.duration: 12.45ms
email.send.duration: 234.56ms (email_type=welcome)
email.sent.total: 1 (email_type=welcome)
domain.events.published: 2 (event_type=customer)
```

---

## Trace Summary

| Operation | Duration | Status |
|-----------|----------|--------|
| Total handler execution | 278.34ms | ✅ Success |
| Customer domain creation | ~1ms | ✅ Success |
| MongoDB save | 12.45ms | ✅ Success |
| Email service call | 234.56ms | ✅ Success |
| Event publishing | ~2ms | ✅ Success |

---

## Error Scenario Example

If the MongoDB save fails:

```json
{
  "level": "error",
  "message": "Failed to save customer to MongoDB",
  "context": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "customer_id": "01JCXYZ1234567890ABCDEFGH",
    "operation": "mongodb.save",
    "duration_ms": 5.23,
    "error": "Connection timeout to MongoDB"
  }
}

{
  "level": "error",
  "message": "Failed to create customer",
  "context": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "command": "App\\Customer\\Application\\Command\\CreateCustomerCommand",
    "customer_id": "01JCXYZ1234567890ABCDEFGH",
    "customer_email": "john.doe@example.com",
    "duration_ms": 15.67,
    "error_type": "MongoDB\\Driver\\Exception\\ConnectionTimeoutException",
    "error_message": "Connection timeout to MongoDB",
    "error_file": "/app/src/Customer/Infrastructure/Repository/MongoCustomerRepository.php",
    "error_line": 45,
    "trace": "..."
  }
}
```

**Metrics**:
```
customer.create.duration: 15.67ms (status=error)
customer.create.errors: 1 (error_type=ConnectionTimeoutException)
mongodb.customer.save.errors: 1 (error_type=ConnectionTimeoutException)
```

---

## Key Takeaways

1. **Correlation ID** flows through every operation
2. **Structured logs** provide searchable context
3. **Metrics** quantify performance and errors
4. **Tracing** shows operation timing breakdown
5. **Error handling** includes full context for debugging
6. **Non-critical failures** (email) don't block the operation

---

## Using This Pattern

Copy this pattern for any command handler:

1. Generate correlation ID at start
2. Log operation start with context
3. Wrap DB operations with tracing
4. Wrap HTTP calls with tracing
5. Record metrics for duration and errors
6. Log success/failure with correlation ID
7. Include full error context in logs

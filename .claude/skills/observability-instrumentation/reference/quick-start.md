# Quick Start Guide

Fast-track guide to adding observability to your code in 10 minutes.

## The 5-Minute Pattern

### Step 1: Inject Dependencies (30 seconds)

```php
use Psr\Log\LoggerInterface;

final readonly class YourCommandHandler
{
    public function __construct(
        private YourRepository $repository,
        private LoggerInterface $logger  // Add this
    ) {}
}
```

### Step 2: Add Correlation ID (1 minute)

```php
public function __invoke(YourCommand $command): void
{
    $correlationId = Uuid::v4()->toString();

    $this->logger->info('Processing command', [
        'correlation_id' => $correlationId,
        'command' => get_class($command),
    ]);

    // ... rest of your code
}
```

### Step 3: Wrap with Try-Catch (2 minutes)

```php
public function __invoke(YourCommand $command): void
{
    $correlationId = Uuid::v4()->toString();
    $startTime = microtime(true);

    $this->logger->info('Processing command', [
        'correlation_id' => $correlationId,
        'command' => get_class($command),
    ]);

    try {
        // Your existing code here
        $result = $this->execute($command);

        $duration = (microtime(true) - $startTime) * 1000;

        $this->logger->info('Command processed', [
            'correlation_id' => $correlationId,
            'duration_ms' => $duration,
        ]);

    } catch (\Throwable $e) {
        $duration = (microtime(true) - $startTime) * 1000;

        $this->logger->error('Command failed', [
            'correlation_id' => $correlationId,
            'duration_ms' => $duration,
            'error' => $e->getMessage(),
        ]);

        throw $e;
    }
}
```

### Step 4: Add DB Tracing (1 minute)

```php
private function saveWithTrace(Entity $entity, string $correlationId): void
{
    $this->logger->debug('Saving to database', [
        'correlation_id' => $correlationId,
        'operation' => 'mongodb.save',
    ]);

    $this->repository->save($entity);

    $this->logger->info('Saved to database', [
        'correlation_id' => $correlationId,
        'operation' => 'mongodb.save',
    ]);
}
```

### Step 5: Test It (30 seconds)

```bash
make sh
tail -f var/log/dev.log | grep correlation_id
```

Run your operation and verify logs appear.

**Done! You now have observable code.**

---

## Copy-Paste Template

```php
<?php

declare(strict_types=1);

namespace App\YourContext\Application\CommandHandler;

use App\YourContext\Application\Command\YourCommand;
use App\YourContext\Domain\Repository\YourRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

final readonly class YourCommandHandler
{
    public function __construct(
        private YourRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}

    public function __invoke(YourCommand $command): void
    {
        $correlationId = Uuid::v4()->toString();
        $startTime = microtime(true);

        $this->logger->info('Processing command', [
            'correlation_id' => $correlationId,
            'command' => get_class($command),
            'entity_id' => $command->id,  // Adjust based on your command
        ]);

        try {
            // YOUR CODE HERE
            $entity = $this->createEntity($command);
            $this->saveWithTrace($entity, $correlationId);

            // Success logging
            $duration = (microtime(true) - $startTime) * 1000;
            $this->logger->info('Command processed successfully', [
                'correlation_id' => $correlationId,
                'entity_id' => $entity->id(),
                'duration_ms' => round($duration, 2),
            ]);

        } catch (\Throwable $e) {
            // Error logging
            $duration = (microtime(true) - $startTime) * 1000;
            $this->logger->error('Command processing failed', [
                'correlation_id' => $correlationId,
                'entity_id' => $command->id ?? 'unknown',
                'duration_ms' => round($duration, 2),
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function saveWithTrace($entity, string $correlationId): void
    {
        $startTime = microtime(true);

        $this->logger->debug('Saving to database', [
            'correlation_id' => $correlationId,
            'entity_id' => $entity->id(),
            'operation' => 'mongodb.save',
        ]);

        try {
            $this->repository->save($entity);

            $duration = (microtime(true) - $startTime) * 1000;
            $this->logger->info('Saved to database', [
                'correlation_id' => $correlationId,
                'entity_id' => $entity->id(),
                'operation' => 'mongodb.save',
                'duration_ms' => round($duration, 2),
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Database save failed', [
                'correlation_id' => $correlationId,
                'entity_id' => $entity->id(),
                'operation' => 'mongodb.save',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

---

## Verification Checklist

After implementing, verify:

- [ ] Run your code: `make sh` then execute operation
- [ ] Check logs: `grep correlation_id var/log/dev.log`
- [ ] Verify correlation ID appears in all log entries
- [ ] Confirm duration is tracked
- [ ] Test error case and verify error logging

---

## Adding Metrics (Optional - 2 more minutes)

If you have MetricsCollector configured:

```php
public function __construct(
    private YourRepositoryInterface $repository,
    private LoggerInterface $logger,
    private MetricsCollector $metrics  // Add this
) {}

public function __invoke(YourCommand $command): void
{
    $startTime = microtime(true);

    try {
        // Your code...

        // Add success metrics
        $duration = (microtime(true) - $startTime) * 1000;
        $this->metrics->record('your.operation.duration', $duration, ['status' => 'success']);
        $this->metrics->increment('your.operation.total');

    } catch (\Throwable $e) {
        // Add error metrics
        $duration = (microtime(true) - $startTime) * 1000;
        $this->metrics->record('your.operation.duration', $duration, ['status' => 'error']);
        $this->metrics->increment('your.operation.errors', ['error_type' => get_class($e)]);

        throw $e;
    }
}
```

---

## Common Mistakes to Avoid

### ❌ Don't: String Concatenation

```php
$this->logger->info("Processing customer " . $customerId);
```

### ✅ Do: Structured Arrays

```php
$this->logger->info('Processing customer', [
    'correlation_id' => $correlationId,
    'customer_id' => $customerId,
]);
```

---

### ❌ Don't: Missing Correlation ID

```php
$this->logger->info('Operation started');
```

### ✅ Do: Include Correlation ID

```php
$this->logger->info('Operation started', [
    'correlation_id' => $correlationId,
]);
```

---

### ❌ Don't: Swallow Exceptions

```php
try {
    $this->operation();
} catch (\Throwable $e) {
    $this->logger->error('Failed');
    // Exception lost!
}
```

### ✅ Do: Log and Rethrow

```php
try {
    $this->operation();
} catch (\Throwable $e) {
    $this->logger->error('Failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    throw $e;  // Important!
}
```

---

## Next Steps

Once you have basic observability:

1. **Add metrics** - Track duration and errors
2. **Instrument repository** - Add DB operation tracing
3. **Instrument HTTP calls** - Add external service tracing
4. **Collect evidence** - Capture logs for PR
5. **Review** - Use [PR Evidence Guide](pr-evidence-guide.md)

---

## Full Guides

- [Structured Logging](structured-logging.md) - Complete logging patterns
- [Metrics Patterns](metrics-patterns.md) - Comprehensive metrics guide
- [Tracing Patterns](tracing-patterns.md) - DB/HTTP tracing strategies
- [Complete Example](../examples/instrumented-command-handler.md) - Full working example

---

**Time to implement**: 5-10 minutes
**Impact**: Production-ready observability
**Benefit**: Debuggable, traceable, measurable code

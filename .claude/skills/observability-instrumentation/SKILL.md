---
name: observability-instrumentation
description: Add comprehensive observability to new code with structured logs (correlation ID), metrics (latency, errors, RPS), and traces around DB/HTTP calls. Use when implementing new features, adding command handlers, creating API endpoints, or instrumenting existing code for production monitoring. Automatically attaches evidence to PRs.
---

# Observability Instrumentation Skill

Instrument new code with production-grade observability following the Three Pillars: **Logs, Metrics, and Traces**. This skill ensures all new code is observable, debuggable, and production-ready.

## When to Use This Skill

Use this skill when:

- Implementing new features or command handlers
- Creating new API endpoints (REST/GraphQL)
- Adding database operations or external HTTP calls
- Refactoring existing code that lacks observability
- Preparing code for production deployment
- Debugging performance or reliability issues
- Before creating pull requests (attach observability evidence)

## ⚡ Quick Start

**New to observability?** Follow the three-step pattern:

1. **Logs**: Add structured logging with correlation ID
2. **Metrics**: Instrument latency, errors, and throughput
3. **Traces**: Wrap DB/HTTP calls with timing and context

## The Three Pillars of Observability

### 1. Structured Logging (Context + Correlation)

**Purpose**: Understand what happened and trace requests across services

**Requirements**:

- Use PSR-3 LoggerInterface (Symfony/Monolog)
- Include correlation ID in all log entries
- Log structured data (arrays, not strings)
- Log at appropriate levels (debug, info, warning, error)

### 2. Metrics (Measure Performance)

**Purpose**: Quantify system behavior and detect anomalies

**Key Metrics**:

- **Latency**: Response/operation duration
- **Errors**: Failure counts and error rates
- **Throughput**: Requests per second (RPS)

### 3. Traces (Track Flow)

**Purpose**: Track request flow through the system

**What to Trace**:

- Database operations (MongoDB queries)
- HTTP calls to external services
- Command/Query handler execution
- Critical business logic paths

---

## Core Workflow

### Step 1: Add Structured Logging

**Inject LoggerInterface**:

```php
use Psr\Log\LoggerInterface;

final readonly class CreateCustomerCommandHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private CustomerRepository $repository
    ) {}
}
```

**Log with correlation ID and context**:

```php
public function __invoke(CreateCustomerCommand $command): void
{
    $correlationId = $this->generateCorrelationId();

    $this->logger->info('Creating customer', [
        'correlation_id' => $correlationId,
        'customer_id' => $command->id,
        'customer_email' => $command->email,
        'timestamp' => time(),
    ]);

    try {
        $customer = Customer::create(/* ... */);

        $this->logger->info('Customer created successfully', [
            'correlation_id' => $correlationId,
            'customer_id' => $customer->id(),
        ]);
    } catch (\Throwable $e) {
        $this->logger->error('Failed to create customer', [
            'correlation_id' => $correlationId,
            'customer_id' => $command->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        throw $e;
    }
}
```

**See**: [structured-logging.md](reference/structured-logging.md) for complete patterns

### Step 2: Add Metrics

**Track operation latency**:

```php
public function __invoke(CreateCustomerCommand $command): void
{
    $startTime = microtime(true);

    try {
        // Execute operation
        $customer = Customer::create(/* ... */);
        $this->repository->save($customer);

        // Record success metric
        $duration = (microtime(true) - $startTime) * 1000; // ms
        $this->recordMetric('customer.create.duration', $duration, [
            'status' => 'success',
        ]);

    } catch (\Throwable $e) {
        // Record error metric
        $duration = (microtime(true) - $startTime) * 1000;
        $this->recordMetric('customer.create.duration', $duration, [
            'status' => 'error',
            'error_type' => get_class($e),
        ]);

        $this->incrementCounter('customer.create.errors');

        throw $e;
    }
}
```

**Key metrics to track**:

| Metric Type     | Example                         | When to Use         |
| --------------- | ------------------------------- | ------------------- |
| Duration        | `handler.execution.duration_ms` | Every operation     |
| Counter         | `handler.execution.total`       | Throughput tracking |
| Error Rate      | `handler.execution.errors`      | Failure detection   |
| Business Metric | `customer.created.total`        | Domain events       |

**See**: [metrics-patterns.md](reference/metrics-patterns.md) for complete guide

### Step 3: Add Tracing for DB/HTTP Calls

**Wrap database operations**:

```php
private function saveWithTrace(Customer $customer, string $correlationId): void
{
    $startTime = microtime(true);

    $this->logger->debug('Saving customer to database', [
        'correlation_id' => $correlationId,
        'customer_id' => $customer->id(),
        'operation' => 'mongodb.save',
    ]);

    try {
        $this->repository->save($customer);

        $duration = (microtime(true) - $startTime) * 1000;

        $this->logger->info('Customer saved to database', [
            'correlation_id' => $correlationId,
            'customer_id' => $customer->id(),
            'duration_ms' => $duration,
            'operation' => 'mongodb.save',
        ]);

        $this->recordMetric('mongodb.save.duration', $duration);

    } catch (\Throwable $e) {
        $this->logger->error('Database save failed', [
            'correlation_id' => $correlationId,
            'customer_id' => $customer->id(),
            'error' => $e->getMessage(),
            'operation' => 'mongodb.save',
        ]);

        throw $e;
    }
}
```

**Wrap HTTP calls**:

```php
private function callExternalApiWithTrace(string $url, string $correlationId): array
{
    $startTime = microtime(true);

    $this->logger->info('Calling external API', [
        'correlation_id' => $correlationId,
        'url' => $url,
        'method' => 'POST',
    ]);

    try {
        $response = $this->httpClient->post($url, [/* ... */]);

        $duration = (microtime(true) - $startTime) * 1000;

        $this->logger->info('External API call completed', [
            'correlation_id' => $correlationId,
            'url' => $url,
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
        ]);

        $this->recordMetric('http.call.duration', $duration, [
            'endpoint' => $url,
            'status' => $response->getStatusCode(),
        ]);

        return $response->toArray();

    } catch (\Throwable $e) {
        $duration = (microtime(true) - $startTime) * 1000;

        $this->logger->error('External API call failed', [
            'correlation_id' => $correlationId,
            'url' => $url,
            'duration_ms' => $duration,
            'error' => $e->getMessage(),
        ]);

        $this->incrementCounter('http.call.errors', [
            'endpoint' => $url,
        ]);

        throw $e;
    }
}
```

**See complete example**: [instrumented-command-handler.md](examples/instrumented-command-handler.md) shows full DB and HTTP tracing

### Step 4: Attach Evidence to Pull Requests

**After implementing observability, collect evidence**:

1. **Run the code** and capture log output:

```bash
# Tail logs while testing
make sh
tail -f var/log/dev.log | grep correlation_id
```

2. **Extract observability evidence**:

- Correlation ID tracking across operations
- Structured log entries with context
- Metric recordings (duration, errors)
- Trace information for DB/HTTP calls

3. **Add to PR description**:

````markdown
## Observability Evidence

### Structured Logs

```json
{
  "level": "info",
  "message": "Creating customer",
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
  "customer_id": "01JCXYZ...",
  "timestamp": 1702425600
}
```
````

### Metrics Recorded

- `customer.create.duration`: 45ms (success)
- `mongodb.save.duration`: 12ms
- `customer.create.errors`: 0

### Traces

- DB operation: 12ms (mongodb.save)
- Total handler execution: 45ms

````

**See**: [pr-evidence-guide.md](reference/pr-evidence-guide.md) for templates

---

## Correlation ID Management

**Generate correlation ID**:

```php
private function generateCorrelationId(): string
{
    // Generate new UUID v4
    return Uuid::v4()->toString();

    // Alternative: Use Symfony ULID
    // return (string) new Ulid();
}
````

**Extract from request headers** (if available via API Gateway/HTTP layer):

```php
// In a controller or HTTP middleware where Request is available
private function getOrGenerateCorrelationId(Request $request): string
{
    return $request->headers->get('X-Correlation-ID')
        ?? Uuid::v4()->toString();
}
```

**Store in request context** (Application layer):

```php
final class RequestCorrelationIdMiddleware
{
    public function __construct(
        private RequestStack $requestStack
    ) {}

    public function setCorrelationId(string $correlationId): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $request?->attributes->set('correlation_id', $correlationId);
    }

    public function getCorrelationId(): ?string
    {
        return $this->requestStack
            ->getCurrentRequest()
            ?->attributes
            ->get('correlation_id');
    }
}
```

**Note**: The middleware example above shows advanced usage. For most command handlers, simply generating a new UUID at the start of execution is sufficient.

---

## Logging Best Practices

### ✅ DO

- **Use structured arrays**, not concatenated strings
- **Include correlation ID** in every log entry
- **Log before and after** critical operations
- **Log errors with full context** (stack trace, input data)
- **Use appropriate log levels** (debug, info, warning, error)
- **Log business events** (customer created, order placed)
- **Include timestamps** for time-sensitive operations
- **Sanitize sensitive data** (passwords, tokens, PII)

### ❌ DON'T

- Don't log sensitive data (passwords, credit cards, tokens)
- Don't use string concatenation in logs
- Don't log inside tight loops (performance impact)
- Don't swallow exceptions without logging
- Don't log at wrong levels (error for info, debug for errors)
- Don't create unstructured log messages

---

## Metric Naming Conventions

**Format**: `{component}.{operation}.{metric_type}`

**Examples**:

```
# Command handlers
customer.create.duration_ms
customer.create.errors
customer.update.total

# Repository operations
mongodb.save.duration_ms
mongodb.find.duration_ms
mongodb.query.errors

# HTTP operations
http.call.duration_ms
http.call.errors
http.call.total

# Business metrics
order.placed.total
payment.processed.total
email.sent.total
```

**See**: [metrics-patterns.md](reference/metrics-patterns.md)

---

## Architecture Integration

### Layer-Specific Guidance

**Domain Layer**:

- ❌ NO direct logging (pure domain logic)
- ✅ Emit Domain Events for observability
- ✅ Use exceptions to signal errors

**Application Layer** (Command Handlers):

- ✅ Inject LoggerInterface
- ✅ Log command execution start/end
- ✅ Track handler duration metrics
- ✅ Manage correlation ID
- ✅ Log domain events being published

**Infrastructure Layer** (Repositories, HTTP clients):

- ✅ Inject LoggerInterface
- ✅ Log database operations (query, save, delete)
- ✅ Log external HTTP calls
- ✅ Track operation-specific metrics

**See complete examples** in [instrumented-command-handler.md](examples/instrumented-command-handler.md) and [structured-logging.md](reference/structured-logging.md)

---

## Example: Fully Instrumented Command Handler

See complete example: [instrumented-command-handler.md](examples/instrumented-command-handler.md)

**Quick preview**:

```php
final readonly class CreateCustomerCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CustomerRepository $repository,
        private DomainEventPublisher $publisher,
        private LoggerInterface $logger,
        private MetricsCollector $metrics
    ) {}

    public function __invoke(CreateCustomerCommand $command): void
    {
        $correlationId = $this->generateCorrelationId();
        $startTime = microtime(true);

        $this->logger->info('Processing CreateCustomerCommand', [
            'correlation_id' => $correlationId,
            'command' => get_class($command),
            'customer_id' => $command->id,
        ]);

        try {
            // Domain logic
            $customer = Customer::create($command->id, $command->name, $command->email);

            // Persist with tracing
            $this->saveWithTrace($customer, $correlationId);

            // Publish events
            $events = $customer->pullDomainEvents();
            $this->publisher->publish(...$events);

            // Success metrics
            $duration = (microtime(true) - $startTime) * 1000;
            $this->metrics->record('customer.create.duration', $duration, ['status' => 'success']);
            $this->metrics->increment('customer.create.total');

            $this->logger->info('Customer created successfully', [
                'correlation_id' => $correlationId,
                'customer_id' => $customer->id(),
                'duration_ms' => $duration,
            ]);

        } catch (\Throwable $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            $this->logger->error('Failed to create customer', [
                'correlation_id' => $correlationId,
                'customer_id' => $command->id,
                'duration_ms' => $duration,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->metrics->record('customer.create.duration', $duration, ['status' => 'error']);
            $this->metrics->increment('customer.create.errors', ['error_type' => get_class($e)]);

            throw $e;
        }
    }
}
```

---

## Integration with CI Workflow

Before creating a PR, ensure:

1. **Run the code** and verify logs are generated
2. **Check log format** (structured, includes correlation ID)
3. **Capture evidence** (logs, metrics, traces)
4. **Add to PR description** using evidence template
5. **Run CI checks**: `make ci`

**See**: [pr-evidence-guide.md](reference/pr-evidence-guide.md)

---

## Success Criteria

After instrumenting code, verify:

- ✅ All operations log with correlation ID
- ✅ Structured logging (arrays, not strings)
- ✅ Metrics tracked (duration, errors, throughput)
- ✅ DB operations traced with timing
- ✅ HTTP calls traced with timing
- ✅ Error cases logged with full context
- ✅ No sensitive data in logs
- ✅ Evidence attached to PR
- ✅ CI checks pass: `make ci`

---

## Additional Resources

### Quick References

- **[Quick Start Guide](reference/quick-start.md)** - Fast-track instrumentation workflow (10 minutes)
- **[Structured Logging](reference/structured-logging.md)** - Complete logging patterns with examples
- **[Metrics Patterns](reference/metrics-patterns.md)** - Metric types, naming conventions, and collection
- **[PR Evidence Guide](reference/pr-evidence-guide.md)** - Evidence collection templates for PRs

### Complete Examples

- **[Instrumented Command Handler](examples/instrumented-command-handler.md)** - Full working example with logs, metrics, and traces

### Integration with Other Skills

- **CI Workflow** skill: Run `make ci` to validate instrumented code before committing
- **Testing Workflow** skill: Test observability in unit/integration tests
- **Code Review** skill: Review observability evidence in PRs
- **Implementing DDD Architecture** skill: Layer-specific instrumentation guidance
- **API Platform CRUD** skill: Add observability to API endpoints
- **Database Migrations** skill: Instrument repository operations

---

## Common Patterns Summary

| Pattern             | When                    | Example                                           |
| ------------------- | ----------------------- | ------------------------------------------------- |
| **Structured Log**  | Every operation         | `$logger->info('msg', ['correlation_id' => $id])` |
| **Duration Metric** | Every handler/operation | `$metrics->record('op.duration', $ms)`            |
| **Error Counter**   | Catch blocks            | `$metrics->increment('op.errors')`                |
| **DB Trace**        | Repository methods      | Log before/after with timing                      |
| **HTTP Trace**      | External calls          | Log request/response with timing                  |
| **Correlation ID**  | Start of request        | Generate/extract from headers                     |

---

**For detailed implementation patterns, troubleshooting, and complete examples → See supporting files in `reference/` and `examples/` directories.**

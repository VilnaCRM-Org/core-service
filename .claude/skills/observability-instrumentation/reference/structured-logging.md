# Structured Logging Patterns

Comprehensive guide to implementing structured logging in the VilnaCRM Core Service.

## Why Structured Logging?

**Traditional logging** (string concatenation):
```php
// ❌ Bad - Unstructured, hard to parse
$logger->info("Creating customer " . $customerId . " with email " . $email);
```

**Structured logging** (arrays):
```php
// ✅ Good - Structured, searchable, parseable
$logger->info('Creating customer', [
    'customer_id' => $customerId,
    'email' => $email,
]);
```

**Benefits**:
- **Searchable**: Query by specific fields
- **Parseable**: JSON format for log aggregators
- **Contextual**: Rich metadata for debugging
- **Traceable**: Correlation ID connects related logs

---

## Log Levels

Use appropriate log levels following PSR-3:

| Level | When to Use | Example |
|-------|-------------|---------|
| **debug** | Detailed diagnostic info | Variable values, method entry/exit |
| **info** | Important business events | Customer created, order placed |
| **warning** | Non-critical issues | Email failed (retryable), cache miss |
| **error** | Critical failures | Database down, API call failed |

### Examples

```php
// DEBUG: Detailed execution flow
$this->logger->debug('Entering method', [
    'correlation_id' => $correlationId,
    'method' => __METHOD__,
    'arguments' => compact('customerId', 'email'),
]);

// INFO: Business event
$this->logger->info('Customer created', [
    'correlation_id' => $correlationId,
    'customer_id' => $customer->id(),
    'event' => 'customer.created',
]);

// WARNING: Recoverable issue
$this->logger->warning('Cache miss, fetching from database', [
    'correlation_id' => $correlationId,
    'cache_key' => $key,
]);

// ERROR: Critical failure
$this->logger->error('Database connection failed', [
    'correlation_id' => $correlationId,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

---

## Essential Context Fields

Every log entry should include:

### 1. Correlation ID (Required)

```php
$this->logger->info('Operation started', [
    'correlation_id' => $correlationId,  // ✅ Required
    // ... other fields
]);
```

### 2. Entity Identifiers

```php
$this->logger->info('Processing entity', [
    'correlation_id' => $correlationId,
    'customer_id' => $customerId,      // Primary entity
    'order_id' => $orderId,            // Related entities
]);
```

### 3. Operation Context

```php
$this->logger->info('Database operation', [
    'correlation_id' => $correlationId,
    'operation' => 'mongodb.save',     // Operation type
    'collection' => 'customers',       // Target resource
]);
```

### 4. Timing Information

```php
$this->logger->info('Operation completed', [
    'correlation_id' => $correlationId,
    'duration_ms' => $duration,        // Execution time
    'timestamp' => time(),             // Unix timestamp
]);
```

### 5. Error Context (for errors)

```php
$this->logger->error('Operation failed', [
    'correlation_id' => $correlationId,
    'error_type' => get_class($e),
    'error_message' => $e->getMessage(),
    'error_file' => $e->getFile(),
    'error_line' => $e->getLine(),
    'trace' => $e->getTraceAsString(),
]);
```

---

## Complete Context Template

```php
$context = [
    // Required: Correlation
    'correlation_id' => $correlationId,

    // Entity identifiers
    'customer_id' => $customerId,
    'order_id' => $orderId,

    // Operation details
    'operation' => 'mongodb.save',
    'command' => CreateCustomerCommand::class,
    'handler' => get_class($this),

    // Timing
    'timestamp' => time(),
    'duration_ms' => round($duration, 2),

    // Status
    'status' => 'success', // or 'error'

    // Business context
    'customer_email' => $email,
    'order_total' => $total,

    // Technical context
    'memory_usage_mb' => memory_get_usage(true) / 1024 / 1024,
];

$this->logger->info('Operation completed', $context);
```

---

## Logging Patterns by Layer

### Application Layer (Command Handlers)

```php
final readonly class CreateCustomerCommandHandler
{
    public function __construct(
        private LoggerInterface $logger,
        // ... other dependencies
    ) {}

    public function __invoke(CreateCustomerCommand $command): void
    {
        $correlationId = $this->generateCorrelationId();

        // Log command start
        $this->logger->info('Processing command', [
            'correlation_id' => $correlationId,
            'command' => get_class($command),
            'command_data' => [
                'customer_id' => $command->id,
                'email' => $command->email,
            ],
        ]);

        try {
            // Execute operation
            $result = $this->execute($command, $correlationId);

            // Log success
            $this->logger->info('Command processed successfully', [
                'correlation_id' => $correlationId,
                'command' => get_class($command),
                'result' => $result,
            ]);

        } catch (\Throwable $e) {
            // Log error
            $this->logger->error('Command processing failed', [
                'correlation_id' => $correlationId,
                'command' => get_class($command),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
```

### Infrastructure Layer (Repositories)

```php
final class MongoCustomerRepository implements CustomerRepositoryInterface
{
    public function __construct(
        private DocumentManager $documentManager,
        private LoggerInterface $logger
    ) {}

    public function save(Customer $customer): void
    {
        $this->logger->debug('Saving customer to MongoDB', [
            'customer_id' => $customer->id()->value(),
            'operation' => 'mongodb.save',
            'collection' => 'customers',
        ]);

        try {
            $this->documentManager->persist($customer);
            $this->documentManager->flush();

            $this->logger->info('Customer saved successfully', [
                'customer_id' => $customer->id()->value(),
                'operation' => 'mongodb.save',
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to save customer', [
                'customer_id' => $customer->id()->value(),
                'operation' => 'mongodb.save',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

### Infrastructure Layer (HTTP Clients)

```php
final class EmailServiceHttpClient
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    public function sendEmail(string $to, string $subject): void
    {
        $this->logger->info('Sending email via HTTP API', [
            'operation' => 'http.email.send',
            'recipient' => $to,
            'subject' => $subject,
        ]);

        try {
            $response = $this->httpClient->request('POST', '/api/email/send', [
                'json' => compact('to', 'subject'),
            ]);

            $this->logger->info('Email sent successfully', [
                'operation' => 'http.email.send',
                'status_code' => $response->getStatusCode(),
            ]);

        } catch (\Throwable $e) {
            $this->logger->error('Failed to send email', [
                'operation' => 'http.email.send',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

---

## Data Sanitization

**CRITICAL**: Never log sensitive data.

### Sanitize Before Logging

```php
// ❌ NEVER log sensitive data
$this->logger->info('User authenticated', [
    'username' => $username,
    'password' => $password,              // ❌ Security violation!
    'credit_card' => $creditCard,         // ❌ PCI compliance violation!
    'ssn' => $ssn,                        // ❌ PII violation!
]);

// ✅ Log with sanitization
$this->logger->info('User authenticated', [
    'username' => $username,
    'password_length' => strlen($password), // ✅ Metadata only
    'has_mfa' => $hasMfa,                   // ✅ Boolean safe
]);

// ✅ Mask sensitive fields
$this->logger->info('Payment processed', [
    'card_last_four' => substr($cardNumber, -4), // ✅ Partial data
    'amount' => $amount,                         // ✅ Non-sensitive
]);
```

### Sensitive Data Types to Avoid

| Type | Examples | Safe Alternative |
|------|----------|-----------------|
| Passwords | Plain text passwords | Password length, hash type |
| Tokens | API keys, JWT tokens | Token prefix, expiry time |
| Credit Cards | Full card numbers | Last 4 digits |
| SSN/Tax IDs | Full identifiers | Last 4 digits |
| API Keys | Secret keys | Key ID, key type |
| Personal Email | Full addresses | Email domain |

---

## Symfony/Monolog Integration

### Inject Logger

```php
use Psr\Log\LoggerInterface;

final readonly class MyService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}
}
```

### Log Channel Configuration (optional)

```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['app', 'customer', 'order']

services:
    App\Customer\Application\:
        tags:
            - { name: monolog.logger, channel: customer }
```

### JSON Formatter for Production

```yaml
# config/packages/prod/monolog.yaml
monolog:
    handlers:
        main:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: info
            formatter: 'monolog.formatter.json'
```

---

## Log Aggregation Integration

### Elasticsearch/OpenSearch

Structured logs are automatically indexed by field:

```json
{
  "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
  "customer_id": "01JCXYZ...",
  "operation": "mongodb.save",
  "duration_ms": 12.45
}
```

**Query example**:
```
correlation_id:"550e8400-e29b-41d4-a716-446655440000"
operation:"mongodb.save" AND duration_ms:>100
```

### Datadog/New Relic

Add structured attributes for filtering:

```php
$this->logger->info('Operation completed', [
    'correlation_id' => $correlationId,
    'service' => 'core-service',
    'environment' => 'production',
    'version' => '1.2.3',
]);
```

---

## Common Pitfalls

### ❌ Don't: String Concatenation

```php
$this->logger->info("Customer $customerId created with email $email");
```

### ✅ Do: Structured Context

```php
$this->logger->info('Customer created', [
    'customer_id' => $customerId,
    'email' => $email,
]);
```

---

### ❌ Don't: Log Inside Loops

```php
foreach ($customers as $customer) {
    $this->logger->debug('Processing customer', ['id' => $customer->id()]);
    // ... process
}
```

### ✅ Do: Log Once with Summary

```php
$this->logger->info('Processing customers', ['count' => count($customers)]);
foreach ($customers as $customer) {
    // ... process (no logging)
}
$this->logger->info('Customers processed', ['count' => count($customers)]);
```

---

### ❌ Don't: Duplicate Information

```php
$this->logger->info('Creating customer', ['customer_id' => $customerId]);
$this->logger->info('Customer being created', ['customer_id' => $customerId]);
$this->logger->info('About to create customer', ['customer_id' => $customerId]);
```

### ✅ Do: Log State Changes

```php
$this->logger->info('Creating customer', ['customer_id' => $customerId]);
// ... create customer
$this->logger->info('Customer created', ['customer_id' => $customerId]);
```

---

## Testing Logged Output

### Unit Test Example

```php
use Psr\Log\Test\TestLogger;

final class MyServiceTest extends TestCase
{
    public function testLogsCorrectly(): void
    {
        $logger = new TestLogger();
        $service = new MyService($logger);

        $service->doSomething('customer-123');

        $this->assertTrue($logger->hasInfoThatContains('Operation completed'));
        $this->assertTrue($logger->hasInfoThatPasses(function ($record) {
            return $record['context']['customer_id'] === 'customer-123';
        }));
    }
}
```

---

## Success Checklist

- ✅ All logs use structured arrays (not strings)
- ✅ Every log includes correlation_id
- ✅ Appropriate log levels used (debug, info, warning, error)
- ✅ No sensitive data logged
- ✅ Errors include full context and stack trace
- ✅ Timing information included for operations
- ✅ Operation type clearly identified
- ✅ Entity identifiers included

---

**Next Steps**:
- [Metrics Patterns](metrics-patterns.md) - Add quantitative observability
- [Tracing Patterns](tracing-patterns.md) - Track operation flow
- [Correlation ID Patterns](correlation-id-patterns.md) - Manage request tracing

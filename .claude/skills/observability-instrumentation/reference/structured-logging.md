# Structured Logging Patterns

Guide to implementing structured logging for debugging and correlation. This complements business metrics by providing detailed context for troubleshooting.

## Why Structured Logging?

**Traditional logging** (string concatenation):

```php
// Bad - Unstructured, hard to parse
$logger->info("Creating customer " . $customerId . " with email " . $email);
```

**Structured logging** (arrays):

```php
// Good - Structured, searchable, parseable
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

| Level       | When to Use               | Example                              |
| ----------- | ------------------------- | ------------------------------------ |
| **debug**   | Detailed diagnostic info  | Variable values, method entry/exit   |
| **info**    | Important business events | Customer created, order placed       |
| **warning** | Non-critical issues       | Email failed (retryable), cache miss |
| **error**   | Critical failures         | Database down, API call failed       |

### Examples

```php
// DEBUG: Detailed execution flow
$this->logger->debug('Entering method', [
    'method' => __METHOD__,
    'arguments' => compact('customerId', 'email'),
]);

// INFO: Business event
$this->logger->info('Customer created', [
    'customer_id' => $customer->id(),
    'event' => 'customer.created',
]);

// WARNING: Recoverable issue
$this->logger->warning('Cache miss, fetching from database', [
    'cache_key' => $key,
]);

// ERROR: Critical failure
$this->logger->error('Database connection failed', [
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString(),
]);
```

---

## Essential Context Fields

### Entity Identifiers

```php
$this->logger->info('Processing entity', [
    'customer_id' => $customerId,      // Primary entity
    'order_id' => $orderId,            // Related entities
]);
```

### Operation Context

```php
$this->logger->info('Database operation', [
    'operation' => 'mongodb.save',     // Operation type
    'collection' => 'customers',       // Target resource
]);
```

### Error Context

```php
$this->logger->error('Operation failed', [
    'error_type' => get_class($e),
    'error_message' => $e->getMessage(),
    'error_file' => $e->getFile(),
    'error_line' => $e->getLine(),
    'trace' => $e->getTraceAsString(),
]);
```

---

## Logging in Command Handlers

```php
final readonly class CreateCustomerCommandHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private BusinessMetricsEmitterInterface $metrics,
        // ... other dependencies
    ) {}

    public function __invoke(CreateCustomerCommand $command): void
    {
        // Log command start
        $this->logger->info('Processing command', [
            'command' => get_class($command),
            'customer_id' => $command->id,
        ]);

        try {
            // Execute operation
            $customer = $this->execute($command);

            // Log success
            $this->logger->info('Command processed successfully', [
                'command' => get_class($command),
                'customer_id' => $customer->id(),
            ]);

            // Emit business metric
            $this->metrics->emit('CustomersCreated', 1, [
                'Endpoint' => 'Customer',
                'Operation' => 'create',
            ]);

        } catch (\Throwable $e) {
            // Log error
            $this->logger->error('Command processing failed', [
                'command' => get_class($command),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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
// NEVER log sensitive data
$this->logger->info('User authenticated', [
    'username' => $username,
    'password' => $password,              // Security violation!
    'credit_card' => $creditCard,         // PCI compliance violation!
]);

// Log with sanitization
$this->logger->info('User authenticated', [
    'username' => $username,
    'password_length' => strlen($password), // Metadata only
    'has_mfa' => $hasMfa,                   // Boolean safe
]);

// Mask sensitive fields
$this->logger->info('Payment processed', [
    'card_last_four' => substr($cardNumber, -4), // Partial data
    'amount' => $amount,                         // Non-sensitive
]);
```

### Sensitive Data Types to Avoid

| Type           | Examples             | Safe Alternative           |
| -------------- | -------------------- | -------------------------- |
| Passwords      | Plain text passwords | Password length, hash type |
| Tokens         | API keys, JWT tokens | Token prefix, expiry time  |
| Credit Cards   | Full card numbers    | Last 4 digits              |
| SSN/Tax IDs    | Full identifiers     | Last 4 digits              |
| API Keys       | Secret keys          | Key ID, key type           |
| Personal Email | Full addresses       | Email domain               |

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

### JSON Formatter for Production

```yaml
# config/packages/prod/monolog.yaml
monolog:
  handlers:
    main:
      type: stream
      path: '%kernel.logs_dir%/%kernel.environment%.log'
      level: info
      formatter: 'monolog.formatter.json'
```

---

## Common Pitfalls

### Don't: String Concatenation

```php
$this->logger->info("Customer $customerId created with email $email");
```

### Do: Structured Context

```php
$this->logger->info('Customer created', [
    'customer_id' => $customerId,
    'email' => $email,
]);
```

---

### Don't: Log Inside Loops

```php
foreach ($customers as $customer) {
    $this->logger->debug('Processing customer', ['id' => $customer->id()]);
    // ... process
}
```

### Do: Log Once with Summary

```php
$this->logger->info('Processing customers', ['count' => count($customers)]);
foreach ($customers as $customer) {
    // ... process (no logging)
}
$this->logger->info('Customers processed', ['count' => count($customers)]);
```

---

## Logging vs Business Metrics

| Use Logging For   | Use Business Metrics For        |
| ----------------- | ------------------------------- |
| Debugging context | Business KPIs                   |
| Error details     | Domain event counts             |
| Operation flow    | Business values (order amounts) |
| Troubleshooting   | CloudWatch dashboards           |

Both complement each other:

- **Logs**: Detailed context for debugging specific issues
- **Metrics**: Aggregated counts for business intelligence

---

## Success Checklist

- ✅ All logs use structured arrays (not strings)
- ✅ Appropriate log levels used (debug, info, warning, error)
- ✅ No sensitive data logged
- ✅ Errors include full context and stack trace
- ✅ Entity identifiers included
- ✅ Operation type clearly identified

---

**Next Steps**:

- [Metrics Patterns](metrics-patterns.md) - Add business metrics with AWS EMF
- [Complete Example](../examples/instrumented-command-handler.md) - See full implementation

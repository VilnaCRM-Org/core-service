# PR Evidence Guide

How to collect and present observability evidence in pull requests to demonstrate your business metrics implementation is production-ready.

## Why Attach Evidence?

Evidence in PRs:

- **Proves metrics work** - Shows EMF output is correct
- **Validates dimensions** - Confirms low cardinality
- **Demonstrates testing** - Shows unit tests pass
- **Provides baseline** - Documents expected behavior

---

## Evidence Collection Workflow

### Step 1: Run Tests

```bash
# Run observability tests
make test -- --filter=Observability

# Or run all tests
make test
```

### Step 2: Check EMF Output

For integration testing, you can verify EMF output format:

```bash
# View EMF-formatted output
docker-compose logs app | grep "_aws"
```

### Step 3: Capture Unit Test Results

```bash
# Run specific test with verbose output
make test -- --filter=BusinessMetricsEmitter -v
```

---

## PR Description Template

Copy this template into your PR description:

````markdown
## Summary

Added business metrics for [feature description].

## Business Metrics Added

| Metric Name        | Dimensions              | Purpose                      |
| ------------------ | ----------------------- | ---------------------------- |
| `CustomersCreated` | Endpoint, Operation     | Track customer registrations |
| `OrdersPlaced`     | Endpoint, PaymentMethod | Track order volume           |
| `OrderValue`       | Endpoint, PaymentMethod | Track order amounts          |

## EMF Output Example

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
````

## Tests

- [x] Unit tests verify metric emission
- [x] Unit tests verify correct dimensions
- [x] Integration test confirms EMF format
- [x] All observability tests pass

## Checklist

- [x] Metric names use PascalCase
- [x] Dimensions are low cardinality (no IDs, timestamps)
- [x] BusinessMetricsEmitterSpy used in tests
- [x] No infrastructure metrics added (AWS AppRunner provides those)

````

---

## Minimal Evidence Template

For smaller PRs:

```markdown
## Business Metrics Evidence

### Metric Added
- `CustomersCreated` with dimensions `Endpoint`, `Operation`

### Test Coverage
```bash
make test -- --filter=CreateCustomerCommandHandlerTest
OK (2 tests, 4 assertions)
````

### EMF Format Verified

- Namespace: `CCore/BusinessMetrics`
- Unit: `Count`
- Dimensions: Low cardinality confirmed

````

---

## What Reviewers Look For

### Correct Metric Naming

```php
use App\Shared\Application\Observability\Metric\EndpointOperationBusinessMetric;

// Good: PascalCase, plural, past tense
final readonly class CustomersCreatedMetric extends EndpointOperationBusinessMetric
{
    public function name(): string { return 'CustomersCreated'; }
}

// Bad: snake_case
final readonly class CustomersCreatedMetric extends EndpointOperationBusinessMetric
{
    public function name(): string { return 'customers_created'; }
}
````

### Low Cardinality Dimensions

```php
// Good: Low cardinality
['Endpoint' => 'Customer', 'Operation' => 'create']

// Bad: High cardinality (IDs)
['CustomerId' => $customerId, 'Timestamp' => time()]
```

### Test Coverage

```php
// Tests verify metric emission
public function testEmitsBusinessMetric(): void
{
    ($this->handler)($command);

    $this->metricsSpy->assertEmittedWithDimensions('CustomersCreated', [
        'Endpoint' => 'Customer',
        'Operation' => 'create',
    ]);
}
```

### No Infrastructure Metrics

Reviewers should verify the PR does NOT add:

- Latency tracking
- Error counters
- Request timing
- HTTP status metrics

These are provided by AWS AppRunner automatically.

---

## Code Review Checklist

Add this to your PR checklist:

```markdown
## Business Metrics Checklist

- [ ] Metric names use PascalCase (CustomersCreated, not customers_created)
- [ ] Dimensions are low cardinality (no IDs, timestamps)
- [ ] Unit tests use BusinessMetricsEmitterSpy
- [ ] Unit tests verify metric name and dimensions
- [ ] No infrastructure metrics added (latency, errors, RPS)
- [ ] EMF format documented in PR description
```

---

## Example PR Comments

### Reviewer Request

> Can you add a test that verifies the metric dimensions are correct?

### Author Response

> Added! The test now uses `assertEmittedWithDimensions`:
>
> ```php
> $this->metricsSpy->assertEmittedWithDimensions('CustomersCreated', [
>     'Endpoint' => 'Customer',
>     'Operation' => 'create',
> ]);
> ```

---

### Reviewer Request

> This dimension `CustomerId` has high cardinality. Can you remove it?

### Author Response

> Good catch! Removed the high-cardinality dimension. Now using only `Endpoint` and `Operation` which are low cardinality.

---

## Common Evidence Issues

### Issue: Missing Test for Dimensions

**Problem**: Test only checks metric name, not dimensions

**Fix**: Use `assertEmittedWithDimensions`:

```php
// Before: Only checks name
self::assertSame('CustomersCreated', $emitted[0]['name']);

// After: Checks name AND dimensions
$this->metricsSpy->assertEmittedWithDimensions('CustomersCreated', [
    'Endpoint' => 'Customer',
    'Operation' => 'create',
]);
```

### Issue: High-Cardinality Dimension

**Problem**: Using IDs or timestamps as dimensions

**Fix**: Remove high-cardinality dimensions:

```php
use App\Shared\Application\Observability\Metric\MetricDimensionsInterface;

// Before: High cardinality (don't do this)
final readonly class CustomersCreatedMetricDimensions implements MetricDimensionsInterface
{
    public function __construct(private string $customerId) {}

    public function toArray(): array
    {
        return [
            'Endpoint' => 'Customer',
            'Operation' => 'create',
            'CustomerId' => $this->customerId, // Remove this
        ];
    }
}

// After: Low cardinality only
final readonly class CustomersCreatedMetricDimensions implements MetricDimensionsInterface
{
    public function toArray(): array
    {
        return [
            'Endpoint' => 'Customer',
            'Operation' => 'create',
        ];
    }
}
```

### Issue: Infrastructure Metric Added

**Problem**: Adding latency or error tracking

**Fix**: Remove infrastructure metrics - AWS AppRunner provides them:

```php
// Before: Infrastructure metric (remove this)
// - customer create latency, error rate, response time, HTTP statuses, etc.

// After: Only business metrics
// - customers created, orders placed, payments processed, etc.
```

---

## Success Criteria

PR evidence is complete when:

- ✅ Metric names documented with PascalCase format
- ✅ Dimensions listed with cardinality noted
- ✅ EMF output example provided
- ✅ Unit tests pass and are documented
- ✅ No infrastructure metrics included
- ✅ Reviewers can verify business metrics are correct

---

**Next Steps**:

- Review your PR description
- Run tests and capture output
- Copy evidence template
- Fill in actual metric data
- Submit PR with evidence

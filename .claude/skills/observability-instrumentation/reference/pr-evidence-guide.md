# PR Evidence Guide

How to collect and present observability evidence in pull requests to demonstrate your code is production-ready.

## Why Attach Evidence?

Observability evidence in PRs:

- **Proves code is instrumented** correctly
- **Shows correlation tracking** works
- **Demonstrates metrics collection** is functioning
- **Provides debugging baseline** for future issues
- **Validates tracing** of DB/HTTP operations

---

## Evidence Collection Workflow

### Step 1: Run Your Code Locally

```bash
# Start containers
make start

# Access container shell
make sh

# Run your operation (API call, command, etc.)
bin/console app:create-customer --id=01JCXYZ... --email=test@example.com
```

### Step 2: Capture Logs

**Option A: Tail logs in real-time**

```bash
# In another terminal
make sh
tail -f var/log/dev.log | grep correlation_id
```

**Option B: Filter logs after execution**

```bash
grep "correlation_id" var/log/dev.log | tail -20
```

**Option C: Extract specific correlation ID**

```bash
grep "550e8400-e29b-41d4-a716-446655440000" var/log/dev.log
```

### Step 3: Extract Metrics

If using metrics collection:

```bash
# Review metrics output
grep "METRIC" var/log/dev.log | tail -10
```

Or use your metrics backend (Prometheus, Datadog, etc.)

### Step 4: Calculate Trace Timings

From logs, extract duration_ms values:

- Total operation duration
- Database operation duration
- HTTP call duration

---

## PR Description Template

Copy this template into your PR description:

````markdown
## Description

[Brief description of changes]

## Observability Instrumentation

### ✅ Structured Logging

- [x] Correlation ID added to all log entries
- [x] Structured context (arrays, not strings)
- [x] Appropriate log levels (debug, info, warning, error)
- [x] No sensitive data logged

### ✅ Metrics

- [x] Operation duration tracked
- [x] Error counters implemented
- [x] Throughput metrics added

### ✅ Traces

- [x] Database operations traced
- [x] HTTP calls traced (if applicable)
- [x] Operation timing recorded

---

## Evidence

### Sample Correlation Flow

**Correlation ID**: `550e8400-e29b-41d4-a716-446655440000`

```json
{
  "level": "info",
  "message": "Processing CreateCustomerCommand",
  "context": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "command": "App\\Customer\\Application\\Command\\CreateCustomerCommand",
    "customer_id": "01JCXYZ1234567890ABCDEFGH",
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
  "message": "Customer created successfully",
  "context": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "customer_id": "01JCXYZ1234567890ABCDEFGH",
    "duration_ms": 45.67
  }
}
```
````

### Metrics Recorded

| Metric                           | Value   | Tags             |
| -------------------------------- | ------- | ---------------- |
| `customer.create.duration`       | 45.67ms | `status=success` |
| `customer.create.total`          | 1       | -                |
| `mongodb.customer.save.duration` | 12.45ms | -                |

### Trace Summary

| Operation               | Duration | Status     |
| ----------------------- | -------- | ---------- |
| Total handler execution | 45.67ms  | ✅ Success |
| MongoDB save            | 12.45ms  | ✅ Success |
| Event publishing        | ~2ms     | ✅ Success |

### Error Scenario (if applicable)

```json
{
  "level": "error",
  "message": "Failed to create customer",
  "context": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "customer_id": "01JCXYZ1234567890ABCDEFGH",
    "error_type": "MongoDB\\Driver\\Exception\\ConnectionTimeoutException",
    "error_message": "Connection timeout",
    "trace": "..."
  }
}
```

**Error metrics**:

- `customer.create.errors`: 1 (error_type=ConnectionTimeoutException)

---

## Testing

- [x] Manually tested correlation ID propagation
- [x] Verified structured log format
- [x] Confirmed metrics are recorded
- [x] Validated trace timing accuracy
- [x] Tested error scenarios with full logging

````

---

## Minimal Evidence Template

For smaller PRs, use this condensed version:

```markdown
## Observability Evidence

**Correlation ID**: `550e8400-e29b-41d4-a716-446655440000`

### Logs
```json
{
  "level": "info",
  "message": "Customer created successfully",
  "context": {
    "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
    "customer_id": "01JCXYZ...",
    "duration_ms": 45.67
  }
}
````

### Metrics

- `customer.create.duration`: 45.67ms (success)
- `mongodb.save.duration`: 12.45ms

### Traces

- Total: 45.67ms
- DB operation: 12.45ms

````

---

## Advanced: Screenshot Evidence

For visual learners, include screenshots from log aggregation tools:

### Kibana/OpenSearch

1. Search by correlation ID
2. Capture screenshot showing all related logs
3. Add to PR description

```markdown
### Log Flow in Kibana

![Correlation flow](./evidence/kibana-correlation-flow.png)

All logs successfully tracked with correlation ID `550e8400...`.
````

### Datadog/Grafana

1. Query metrics dashboard
2. Capture graph showing metric recordings
3. Add to PR

```markdown
### Metrics Dashboard

![Metrics recorded](./evidence/datadog-metrics.png)

Latency metrics successfully recorded for customer creation.
```

---

## Automated Evidence Collection

### Script: Extract Observability Evidence

Save as `scripts/extract-observability-evidence.sh`:

```bash
#!/bin/bash

CORRELATION_ID=$1
LOG_FILE=${2:-var/log/dev.log}

if [ -z "$CORRELATION_ID" ]; then
    echo "Usage: $0 <correlation-id> [log-file]"
    exit 1
fi

echo "## Observability Evidence"
echo ""
echo "**Correlation ID**: \`$CORRELATION_ID\`"
echo ""
echo "### Logs"
echo "\`\`\`json"
grep "$CORRELATION_ID" "$LOG_FILE" | head -10
echo "\`\`\`"
echo ""
echo "### Timing"
echo ""
echo "| Operation | Duration |"
echo "|-----------|----------|"
grep "$CORRELATION_ID" "$LOG_FILE" | grep "duration_ms" | \
  sed -E 's/.*"message":"([^"]+)".*"duration_ms":([0-9.]+).*/| \1 | \2ms |/'
```

**Usage**:

```bash
./scripts/extract-observability-evidence.sh 550e8400-e29b-41d4-a716-446655440000
```

---

## What Reviewers Look For

Reviewers should verify:

### ✅ Correlation ID Present

```json
"correlation_id": "550e8400-e29b-41d4-a716-446655440000"
```

Every log entry includes it.

### ✅ Structured Format

```json
{
  "level": "info",
  "message": "...",
  "context": { ... }
}
```

Not string concatenation.

### ✅ Metrics Recorded

```
customer.create.duration: 45.67ms
customer.create.errors: 0
```

Key operations have metrics.

### ✅ Traces Show Timing

```
Total: 45.67ms
  ├─ DB: 12.45ms
  └─ HTTP: 30.12ms
```

Breakdown of operation timing.

### ✅ Error Handling Visible

```json
{
  "level": "error",
  "message": "...",
  "context": {
    "error": "...",
    "trace": "..."
  }
}
```

Errors logged with context.

---

## Code Review Checklist

Add this to your PR checklist:

```markdown
## Observability Checklist

- [ ] All operations log with correlation ID
- [ ] Structured logging used (arrays, not strings)
- [ ] Metrics recorded for duration and errors
- [ ] Database operations traced
- [ ] HTTP calls traced (if applicable)
- [ ] Error scenarios logged with full context
- [ ] No sensitive data in logs
- [ ] Evidence attached to PR description
```

---

## Example PR Comments

### Reviewer Request

> Can you provide observability evidence showing the correlation ID flows through the entire operation? I'd like to see logs from start to finish with the same correlation ID.

### Author Response

> Absolutely! Here's the complete log flow for correlation ID `550e8400-e29b-41d4-a716-446655440000`:
>
> [paste evidence]
>
> As you can see, the correlation ID appears in:
>
> - Command handler start
> - MongoDB save operation
> - Domain event publishing
> - Command handler completion
>
> Total operation: 45.67ms with database operation taking 12.45ms.

---

## Common Evidence Issues

### Issue: No Correlation ID Tracking

**Problem**: Logs don't show correlation ID flow

**Fix**: Ensure correlation ID is:

1. Generated at operation start
2. Passed to all methods
3. Included in every log entry

### Issue: Incomplete Timing Data

**Problem**: Duration metrics missing

**Fix**: Add timing measurements:

```php
$startTime = microtime(true);
// operation
$duration = (microtime(true) - $startTime) * 1000;
$this->logger->info('...', ['duration_ms' => $duration]);
```

### Issue: No Error Scenario Evidence

**Problem**: Only success case shown

**Fix**: Test and capture error scenarios:

- Simulate database failure
- Capture error logs
- Show error metrics

---

## Success Criteria

PR evidence is complete when:

- ✅ Correlation ID visible in all log entries
- ✅ Structured log format demonstrated
- ✅ Metrics recorded and shown
- ✅ Timing breakdown provided
- ✅ Error scenario documented (if applicable)
- ✅ No sensitive data exposed
- ✅ Reviewers can verify observability is production-ready

---

**Next Steps**:

- Review your PR description
- Run your code and collect evidence
- Copy evidence template
- Fill in actual log/metric data
- Submit PR with evidence

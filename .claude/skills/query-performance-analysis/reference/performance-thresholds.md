# Performance Thresholds

Acceptable performance limits for database operations.

## Response Time Thresholds

### API Endpoints

| Operation Type | Target | Max Acceptable | Critical |
|----------------|--------|----------------|----------|
| GET single resource | <50ms | 100ms | >200ms |
| GET collection (10 items) | <100ms | 200ms | >500ms |
| GET collection (100 items) | <200ms | 500ms | >1000ms |
| POST (create) | <100ms | 300ms | >500ms |
| PATCH/PUT (update) | <100ms | 300ms | >500ms |
| DELETE | <50ms | 200ms | >400ms |
| Search/Filter | <150ms | 300ms | >600ms |

### Database Queries

| Query Type | Target | Max Acceptable | Critical |
|------------|--------|----------------|----------|
| Find by ID | <10ms | 50ms | >100ms |
| Find by indexed field | <20ms | 100ms | >200ms |
| Find by non-indexed field | N/A | N/A | Add index! |
| Collection scan (<1000 docs) | <50ms | 100ms | >200ms |
| Collection scan (>1000 docs) | N/A | N/A | Add index! |
| Aggregation (simple) | <100ms | 200ms | >500ms |
| Aggregation (complex) | <300ms | 500ms | >1000ms |
| Text search | <50ms | 150ms | >300ms |

## Query Count Thresholds

| Operation | Target | Max Acceptable | Critical |
|-----------|--------|----------------|----------|
| GET single resource | 1-2 | 3 | >5 |
| GET collection | 1-3 | 5 | >10 |
| POST (create) | 1-2 | 4 | >8 |
| Complex operation | 3-5 | 10 | >15 |

**Note**: More than 10 queries usually indicates N+1 problem!

## Documents Examined Ratio

**Formula**: `totalDocsExamined / nReturned`

| Ratio | Performance | Action |
|-------|-------------|--------|
| 1:1 | ✅ Excellent | Using perfect index |
| 2:1 | ✅ Good | Acceptable |
| 10:1 | ⚠️ Poor | Review index selectivity |
| 100:1 | 🚨 Critical | Add better index or refine query |
| 1000:1+ | 🚨 Unacceptable | Immediate action required |

**Example**:
```
nReturned: 10
totalDocsExamined: 100
Ratio: 100/10 = 10:1 (Poor - review index)
```

## Index Usage Metrics

### Index Hit Rate

**Target**: >95% of queries should use indexes

```javascript
// Check index usage
db.customers.aggregate([
    {
        $indexStats: {}
    }
])
```

### Ideal Index Characteristics

| Metric | Target | Notes |
|--------|--------|-------|
| Index size | <10% of collection size | Compact indexes are efficient |
| Index selectivity | >10% unique values | High cardinality = better selectivity |
| Index usage count | High | Unused indexes should be dropped |

## Collection Size Guidelines

| Collection Size | Max Query Time | Strategy |
|-----------------|----------------|----------|
| <1,000 docs | 50ms | Indexes optional |
| 1,000-10,000 docs | 100ms | Index frequently queried fields |
| 10,000-100,000 docs | 200ms | Compound indexes, careful query design |
| 100,000-1M docs | 300ms | Aggressive indexing, aggregation pipelines |
| >1M docs | 500ms | Sharding consideration, caching layer |

## MongoDB-Specific Thresholds

### Profiler Slow Query Threshold

```javascript
// Set profiler threshold based on environment
db.setProfilingLevel(2, {
    slowms: 100  // Development: 100ms
    slowms: 50   // Staging: 50ms
    slowms: 200  // Production: 200ms (less noise)
})
```

### Connection Pool

| Metric | Target | Max |
|--------|--------|-----|
| Active connections | <50 | 100 |
| Connection wait time | <10ms | 50ms |

### Memory Usage

| Metric | Warning | Critical |
|--------|---------|----------|
| Index size | >50% RAM | >80% RAM |
| Working set | >60% RAM | >90% RAM |

## Performance Degradation Triggers

Investigate when:

- Query time increases >50% from baseline
- Query count doubles for same operation
- Document examination ratio exceeds 10:1
- Any query takes >1 second
- Collection scan on collection >1000 documents

## Load Testing Thresholds

### Concurrent Users

| Users | Target p95 | Max Acceptable |
|-------|------------|----------------|
| 10 | <100ms | 200ms |
| 50 | <200ms | 400ms |
| 100 | <300ms | 600ms |
| 500 | <500ms | 1000ms |
| 1000 | <800ms | 1500ms |

### Throughput

| Operation | Target RPS | Acceptable |
|-----------|------------|------------|
| Read single | 1000+ | 500+ |
| Read collection | 500+ | 200+ |
| Write single | 500+ | 200+ |
| Complex operation | 100+ | 50+ |

## Monitoring and Alerting

### Alert Thresholds

**Warning** (investigate):
- Query time >200ms
- Query count >10 per request
- Document examination ratio >10:1
- Collection scans on collections >10,000 docs

**Critical** (immediate action):
- Query time >1000ms
- Query count >20 per request
- Document examination ratio >100:1
- Any collection scan on collections >100,000 docs

## Environment-Specific Thresholds

### Development

- More lenient thresholds
- Enable aggressive profiling (slowms: 50)
- Log all queries for analysis

### Staging

- Production-like thresholds
- Moderate profiling (slowms: 100)
- Performance testing focus

### Production

- Strict thresholds
- Conservative profiling (slowms: 200)
- Alerting on degradation

## How to Use These Thresholds

### In Tests

```php
public function testCustomerEndpointPerformance(): void
{
    $start = microtime(true);
    $response = $this->client->request('GET', '/api/customers');
    $duration = (microtime(true) - $start) * 1000;

    // Use threshold from table: GET collection (100 items) = 200ms max
    $this->assertLessThan(200, $duration);
}
```

### In Monitoring

```yaml
# Prometheus alert rules
- alert: SlowAPIEndpoint
  expr: api_request_duration_ms > 200
  for: 5m
  annotations:
    summary: "API endpoint exceeds 200ms threshold"
```

### In Code Reviews

```php
// ❌ REJECT: 45 queries for single endpoint (threshold: <10)
// ❌ REJECT: 1.2s response time (threshold: <500ms)
// ✅ APPROVE: 3 queries, 85ms response time
```

## Exceptions

Some operations may exceed thresholds justifiably:

- **Reporting endpoints**: May take >1s for complex aggregations
- **Export operations**: Can take minutes for large datasets
- **Batch operations**: Expected to be slower
- **First-time cache population**: One-time performance hit

**Always document exceptions** in code comments!

```php
// NOTE: This report aggregates 1M+ documents.
// Expected execution time: 5-10 seconds.
// Runs asynchronously via job queue.
public function generateAnnualReport(): void
{
    // ...
}
```

## References

- MongoDB Performance Best Practices: https://docs.mongodb.com/manual/administration/analyzing-mongodb-performance/
- Web Performance Budgets: https://web.dev/performance-budgets-101/

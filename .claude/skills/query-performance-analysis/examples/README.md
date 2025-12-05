# Query Performance Analysis - Examples

Practical examples for detecting and fixing query performance issues.

## Available Examples

### 1. [N+1 Query Detection](n-plus-one-detection.md)

**When to use**: Endpoint makes many database queries

**Covers**:
- Detecting N+1 patterns in code
- Using MongoDB profiler to find repeated queries
- Fixing with eager loading
- Fixing with batch loading
- Fixing with aggregation

**Scenario**: GET /api/customers returns customers with their types and statuses, causing 201 queries (1 + 100 + 100)

---

### 2. [Slow Query Analysis](slow-query-analysis.md)

**When to use**: Query execution time is high

**Covers**:
- Using MongoDB explain() command
- Interpreting EXPLAIN results
- Identifying COLLSCAN vs IXSCAN
- Understanding executionStats
- Finding inefficient queries

**Scenario**: Search endpoint takes 3 seconds due to collection scan

---

### 3. [Missing Index Detection](missing-index-detection.md)

**When to use**: Need to identify which indexes are needed

**Covers**:
- Analyzing query patterns
- Checking for missing indexes
- Determining compound vs single indexes
- Index field order for compound indexes
- Verifying index usage

**Scenario**: Filtering by status and type is slow

---

### 4. [Safe Index Migration](safe-index-migration.md)

**When to use**: Adding indexes to production database

**Covers**:
- Online index creation (non-blocking)
- Verification steps
- Performance measurement before/after
- Production deployment strategies
- Rollback procedures

**Scenario**: Adding email index to customers collection with 1M documents

---

## How to Use These Examples

### 1. Choose the Right Example

Match your problem to an example:

- **Too many queries** → N+1 Query Detection
- **Slow query execution** → Slow Query Analysis
- **Don't know which indexes needed** → Missing Index Detection
- **Adding index to production** → Safe Index Migration

### 2. Follow the Step-by-Step Guide

Each example provides:

1. **Problem description**: What's wrong
2. **Detection steps**: How to find the issue
3. **Analysis**: Understanding the problem
4. **Solution code**: How to fix it
5. **Verification**: Confirming the fix works
6. **Common questions**: FAQ

### 3. Adapt to Your Needs

Examples use customer/type/status entities - adapt for:
- Your entity names
- Your query patterns
- Your performance thresholds
- Your collection sizes

### 4. Validate Performance

After applying a fix:

```bash
# Re-run profiler
db.setProfilingLevel(2, { slowms: 100 })

# Test endpoint
curl http://localhost/api/your-endpoint

# Check query count and timing
db.system.profile.find().sort({ ts: -1 }).limit(10)
```

## Quick Reference

| Problem | Example | Key Tool |
|---------|---------|----------|
| Many queries (N+1) | N+1 Detection | Profiler query count |
| Slow query | Slow Query Analysis | EXPLAIN command |
| Missing index | Index Detection | EXPLAIN + query patterns |
| Production migration | Safe Migration | Background index creation |

## Combining Examples

Some scenarios require multiple examples:

### Optimizing Slow Endpoint

1. **N+1 Detection** → Find and fix repeated queries
2. **Slow Query Analysis** → Find remaining slow queries
3. **Missing Index Detection** → Add needed indexes
4. **Safe Index Migration** → Deploy to production

### New Feature Performance

1. **Missing Index Detection** → Analyze query patterns
2. **Safe Index Migration** → Add indexes safely
3. **N+1 Detection** → Verify no N+1 queries
4. **Load Testing** (see load-testing skill) → Confirm performance

## Tips for Success

### 1. Start with N+1 Detection

Most performance issues are N+1 queries. Fix these first.

### 2. Use Profiler Liberally

Enable profiler in development to catch issues early:

```javascript
db.setProfilingLevel(2, { slowms: 50 })
```

### 3. EXPLAIN Every Query

Before adding an index, run EXPLAIN to see if it's actually needed.

### 4. Measure Before and After

Always measure performance before and after changes:

```php
$start = microtime(true);
$result = $repository->findSomething();
$duration = (microtime(true) - $start) * 1000;
echo "Duration: {$duration}ms\n";
```

### 5. Add Performance Tests

Prevent regressions with tests:

```php
public function testCustomerEndpointPerformance(): void
{
    $this->assertQueryCount(1, 'Should use eager loading');
    $this->assertResponseTime(200, 'Should respond quickly');
}
```

## Performance Thresholds

Use these as guidelines:

| Operation | Target | Max Acceptable |
|-----------|--------|----------------|
| Read single | <50ms | 100ms |
| Read collection (10 items) | <100ms | 200ms |
| Read collection (100 items) | <200ms | 500ms |
| Write single | <100ms | 300ms |
| Write batch (10 items) | <500ms | 1000ms |
| Query count per endpoint | <5 | 10 |

## Common Patterns Across Examples

All examples demonstrate:

- **Detection**: How to find the problem
- **Analysis**: Understanding why it's slow
- **Solution**: Code to fix it
- **Verification**: Proving it's fixed
- **Prevention**: Tests to avoid regression

## When Examples Don't Cover Your Case

If you don't find an exact match:

1. **Find the closest example** (similar query pattern)
2. **Review reference documentation**:
   - [MongoDB Profiler Guide](../reference/mongodb-profiler-guide.md)
   - [Index Strategies](../reference/index-strategies.md)
   - [Performance Thresholds](../reference/performance-thresholds.md)
3. **Follow general workflow** from main [SKILL.md](../SKILL.md)
4. **Use EXPLAIN** to understand query execution

## Need More Help?

- **Main skill documentation**: [SKILL.md](../SKILL.md)
- **Reference documentation**: [reference/](../reference/)
- **Related skills**:
  - [database-migrations](../../database-migrations/SKILL.md) - Index creation syntax
  - [load-testing](../../load-testing/SKILL.md) - Performance under load
  - [testing-workflow](../../testing-workflow/SKILL.md) - Performance tests

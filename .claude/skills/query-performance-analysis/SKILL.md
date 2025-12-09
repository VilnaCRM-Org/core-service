---
name: query-performance-analysis
description: Detect N+1 queries, analyze slow queries with EXPLAIN, identify missing indexes, and ensure safe online index migrations. Use when optimizing query performance, preventing performance regressions, or debugging slow endpoints. Complements database-migrations skill which covers index creation syntax.
---

# Query Performance Analysis & Index Management

## Context (Input)

Use this skill when:

- New or modified endpoints are slow
- Profiler shows many database queries for single operation
- Need to detect N+1 query problems
- Query execution time is high
- Missing index warnings in MongoDB logs
- Performance regression after code changes
- Planning safe index migrations for production
- Need to verify index effectiveness

## Task (Function)

Analyze query performance, detect N+1 issues, identify missing indexes, and create safe online index migrations with verification steps.

**Success Criteria**:

- N+1 queries detected and fixed
- Slow queries identified with EXPLAIN analysis
- Missing indexes detected and added
- Query performance meets acceptable thresholds (<100ms for reads, <500ms for writes)
- Index migrations are safe for production (no downtime)
- Performance regression tests added

---

## TL;DR - Quick Performance Checklist

**Before Merging Code:**

- [ ] Run endpoint with profiler - check query count
- [ ] No N+1 queries (queries in loops)
- [ ] Slow queries (<100ms) analyzed with EXPLAIN
- [ ] Missing indexes identified and added
- [ ] Eager loading used where appropriate
- [ ] Query count reasonable for operation (<10 queries ideal)
- [ ] Performance test added to prevent regression

**When Adding Indexes:**

- [ ] Index covers actual query patterns
- [ ] Compound index field order correct
- [ ] Index creation is safe for production
- [ ] Verification steps included
- [ ] Index usage confirmed with explain()

---

## Quick Start: 5-Step Performance Analysis

### Step 1: Enable MongoDB Profiler

```bash
# Connect to MongoDB container
docker compose exec database mongosh -u root -p secret --authenticationDatabase admin
```

```javascript
// List all databases to find yours
show dbs

// Switch to your application database (app for this project)
use app

// Enable profiler (level 2 = all operations)
db.setProfilingLevel(2, { slowms: 100 })

// Verify profiling is enabled
db.getProfilingStatus()
```

**Note**: Use `show dbs` to see all databases. The application database is typically the one with data (not 'admin', 'config', or 'local'). This project uses `app` as the database name.

### Step 2: Run Your Endpoint

```bash
# Test endpoint
curl http://localhost/api/customers
```

### Step 3: Analyze Query Patterns

```javascript
// View recent queries
db.system.profile.find().sort({ ts: -1 }).limit(10).pretty();

// Find repeated queries (N+1 pattern)
db.system.profile.aggregate([
  { $group: { _id: '$command.filter', count: { $sum: 1 } } },
  { $match: { count: { $gt: 10 } } },
  { $sort: { count: -1 } },
]);
```

### Step 4: Check for Performance Issues

**N+1 Problem Symptoms**:

- Same query executed many times
- Query count grows with data size
- Queries inside `foreach` loops

**Slow Query Symptoms**:

- `millis` field >100ms
- `planSummary: "COLLSCAN"` (collection scan)
- High `docsExamined` vs `nReturned`

### Step 5: Disable Profiler (Important!)

```javascript
// After analysis, disable profiler to avoid overhead
db.setProfilingLevel(0);

// Or enable only slow query logging (production)
db.setProfilingLevel(1, { slowms: 200 });
```

---

## Common Performance Issues

### Issue 1: N+1 Queries

**Detection**: 100+ queries for 100 records

**Fix**: Use eager loading

```php
// ❌ BAD: N+1 problem
$customers = $repository->findAll();  // 1 query
foreach ($customers as $customer) {
    $type = $typeRepository->find($customer->getTypeId());  // N queries!
}

// ✅ GOOD: Eager loading
$qb = $this->createQueryBuilder(Customer::class);
$qb->field('type')->prime(true);  // Eager load
$customers = $qb->getQuery()->execute();
```

**See**: [examples/n-plus-one-detection.md](examples/n-plus-one-detection.md) for complete guide

---

### Issue 2: Slow Queries (No Index)

**Detection**: EXPLAIN shows `COLLSCAN`, execution time >100ms

**Fix**: Add index

```javascript
// Check query performance
db.customers.find({ email: 'test@example.com' }).explain('executionStats');

// If stage: "COLLSCAN" → add index
```

**Add index in XML mapping**:

```xml
<!-- config/doctrine/Customer.mongodb.xml -->
<indexes>
    <index><key name="email" order="asc"/></index>
</indexes>
```

**Apply schema update**:

```bash
docker compose exec php bin/console doctrine:mongodb:schema:update
```

**See**: [examples/slow-query-analysis.md](examples/slow-query-analysis.md) for EXPLAIN interpretation

---

### Issue 3: Missing Indexes on Filtered Fields

**Detection**: Queries filter/sort on fields without indexes

**Common patterns needing indexes**:

- WHERE clause fields: `status = 'active'`
- ORDER BY fields: `createdAt DESC`
- Compound filters: `status = 'active' AND type = 'premium'`

**See**: [reference/index-strategies.md](reference/index-strategies.md) for index selection guide

---

## Performance Thresholds

| Operation                  | Target | Max Acceptable |
| -------------------------- | ------ | -------------- |
| GET single                 | <50ms  | 100ms          |
| GET collection (100 items) | <200ms | 500ms          |
| POST/PATCH/PUT             | <100ms | 300ms          |
| Query count per endpoint   | <5     | 10             |

**See**: [reference/performance-thresholds.md](reference/performance-thresholds.md) for complete thresholds

---

## Safe Index Migrations

**MongoDB 4.2+** uses an optimized hybrid index build approach:

- Obtains exclusive locks **only briefly** at start and end of the build
- Allows concurrent reads and writes during most of the build process
- **Note**: Brief locking at start/end can still impact write latency

**Recommendation**: Schedule index creation during low-traffic periods or maintenance windows to avoid latency spikes, especially for large collections.

### Production Migration Strategy

1. **Add index to XML mapping**
2. **Schedule during low traffic** (or maintenance window for large collections)
3. **Run schema update**: `doctrine:mongodb:schema:update`
4. **Verify index created**: `db.collection.getIndexes()`
5. **Verify index is used**: Run EXPLAIN on queries
6. **Measure performance improvement**

---

## Performance Testing

```php
final class CustomerEndpointTest extends ApiTestCase
{
    public function testNoN1Queries(): void
    {
        // Arrange: Create test data
        for ($i = 0; $i < 50; $i++) {
            $this->createCustomer();
        }

        // Act: Enable query counter
        $this->enableQueryCounter();
        $this->client->request('GET', '/api/customers');

        // Assert: Should have minimal queries
        $queryCount = $this->getQueryCount();
        $this->assertLessThan(10, $queryCount, 'N+1 query detected!');
    }

    public function testEndpointPerformance(): void
    {
        // Measure response time
        $start = microtime(true);
        $response = $this->client->request('GET', '/api/customers');
        $duration = (microtime(true) - $start) * 1000;

        // Assert: Should be fast
        $this->assertLessThan(200, $duration, "Too slow: {$duration}ms");
    }
}
```

---

## Quick Commands Reference

```bash
# Enable profiler
docker compose exec database mongosh -u root -p secret --authenticationDatabase admin
```

```javascript
// List databases, then use the one with your application data
show dbs
use app  // Application database name for this project

// Enable profiler
db.setProfilingLevel(2, { slowms: 100 })

// View slow queries
db.system.profile.find({ millis: { $gt: 100 } }).sort({ millis: -1 })

// Check indexes
db.customers.getIndexes()

// EXPLAIN query
db.customers.find({ email: "test@example.com" }).explain("executionStats")

// IMPORTANT: Disable profiler after analysis
db.setProfilingLevel(0)
```

```bash
# Update schema (creates indexes)
docker compose exec php bin/console doctrine:mongodb:schema:update
```

---

## Workflow Integration

### When to Use This Skill

**Use after**:

- [api-platform-crud](../api-platform-crud/SKILL.md) - After creating endpoints
- [database-migrations](../database-migrations/SKILL.md) - After adding entities

**Use before**:

- [load-testing](../load-testing/SKILL.md) - Optimize before load testing
- [ci-workflow](../ci-workflow/SKILL.md) - Validate performance in CI

**Related skills**:

- [testing-workflow](../testing-workflow/SKILL.md) - Add performance tests
- [documentation-sync](../documentation-sync/SKILL.md) - Document performance changes

---

## Reference Documentation

### Examples (Detailed Scenarios)

- **[examples/README.md](examples/README.md)** - Examples index
- **[examples/n-plus-one-detection.md](examples/n-plus-one-detection.md)** - Complete N+1 detection and fix guide
- **[examples/slow-query-analysis.md](examples/slow-query-analysis.md)** - EXPLAIN analysis walkthrough

### Reference Guides

- **[reference/performance-thresholds.md](reference/performance-thresholds.md)** - Acceptable performance limits
- **[reference/mongodb-profiler-guide.md](reference/mongodb-profiler-guide.md)** - Complete profiler documentation
- **[reference/index-strategies.md](reference/index-strategies.md)** - When to use which index type

---

## Comparison: This Skill vs database-migrations

| Aspect      | query-performance-analysis | database-migrations          |
| ----------- | -------------------------- | ---------------------------- |
| **Purpose** | **WHAT** indexes to add    | **HOW** to create indexes    |
| **Focus**   | Performance analysis       | Schema definition            |
| **Tools**   | EXPLAIN, profiler          | Doctrine ODM, XML mappings   |
| **When**    | Debugging slow queries     | Creating entities/migrations |
| **Output**  | Performance insights       | XML configuration            |

**Workflow**: Use this skill to **identify** needed indexes, then use database-migrations for **XML syntax**.

---

## Troubleshooting

**Issue**: Can't enable MongoDB profiler

**Solution**: Verify MongoDB version (4.0+), check permissions, ensure connected to correct database

---

**Issue**: EXPLAIN shows COLLSCAN but index exists

**Solution**:

1. Verify index covers your query pattern
2. Check compound index field order
3. Ensure query uses indexed fields exactly

---

**Issue**: Container name error: "service 'mongodb' not found"

**Solution**: Use `database` as the service name (not `mongodb`):

```bash
docker compose exec database mongosh  # ✅ Correct
docker compose exec mongodb mongosh   # ❌ Wrong - service doesn't exist
```

---

**Issue**: Database name unknown

**Solution**: List databases to find the application database:

```bash
# Connect and list all databases
docker compose exec database mongosh -u root -p secret --authenticationDatabase admin --eval "db.getMongo().getDBNames()"

# Or interactively - identify your database first
docker compose exec database mongosh -u root -p secret --authenticationDatabase admin
```

```javascript
// Look for the database with your application data (not 'admin', 'config', or 'local')
show dbs

// Use your application database (app for this project, contains customers, customer_types, customer_statuses)
use app
```

---

## External Resources

- **[MongoDB EXPLAIN Documentation](https://docs.mongodb.com/manual/reference/explain-results/)**
- **[MongoDB Profiler](https://docs.mongodb.com/manual/tutorial/manage-the-database-profiler/)**
- **[MongoDB Indexing Strategies](https://docs.mongodb.com/manual/applications/indexes/)**
- **[Doctrine ODM Performance](https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/performance.html)**

---

## Best Practices

### DO ✅

- Enable profiler in development for every new feature
- Analyze queries before deploying to production
- Add performance tests to prevent regressions
- Use eager loading to prevent N+1 queries
- Create indexes for frequently filtered/sorted fields
- Disable profiler after analysis (avoid overhead)

### DON'T ❌

- Leave profiler level 2 enabled in production
- Add indexes without analyzing query patterns
- Ignore N+1 warnings (they compound quickly)
- Skip EXPLAIN analysis before adding indexes
- Forget to verify index is actually used after creation
- Hardcode database names (use `DB_NAME` variable or identify from `show dbs` first)
- Assume index creation is completely non-blocking (brief locks still occur at start/end)

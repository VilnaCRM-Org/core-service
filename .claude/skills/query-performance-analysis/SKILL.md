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

## Quick Start: Detect and Fix N+1 Queries

### Step 1: Enable MongoDB Profiler

```bash
# In MongoDB container
docker compose exec mongodb mongosh

# Enable profiler (level 2 = all operations)
use core_service_db
db.setProfilingLevel(2, { slowms: 100 })

# Check profiling status
db.getProfilingStatus()
```

### Step 2: Run Your Endpoint

```bash
# Test endpoint with curl or Postman
curl http://localhost/api/customers
```

### Step 3: Analyze Queries

```bash
# View profiled queries
db.system.profile.find().sort({ ts: -1 }).limit(10).pretty()

# Look for patterns:
# - Same query executed many times
# - Queries inside loops
# - High execution time (millis field)
```

### Step 4: Check for N+1 Pattern

**N+1 Problem Symptoms**:
```
GET /api/customers
  Query 1: Find all customers (1 query)
  Query 2: Find type for customer 1 (1 query)
  Query 3: Find type for customer 2 (1 query)
  Query 4: Find type for customer 3 (1 query)
  ...
  = 1 + N queries (BAD!)
```

**Fix with Eager Loading**:
```php
$qb = $this->documentManager->createQueryBuilder(Customer::class);
$qb->field('type')->prime(true);  // Eager load type references
$customers = $qb->getQuery()->execute();
```

---

## N+1 Query Detection

### What is N+1 Query Problem?

**Definition**: One query to fetch main results + N additional queries to fetch related data.

**Example**:
```php
// ❌ BAD: N+1 Query Problem
public function getCustomersWithTypes(): array
{
    $customers = $this->repository->findAll();  // 1 query

    foreach ($customers as $customer) {
        // This fires a query for EACH customer!
        $type = $this->typeRepository->findById($customer->getTypeId());  // N queries
        $customer->setType($type);
    }

    return $customers;
}

// Result: 1 + 100 = 101 queries for 100 customers!
```

### Detection Methods

#### Method 1: Symfony Profiler (Development)

```bash
# Run endpoint, then check profiler
open http://localhost/_profiler

# Look at "Database" panel
# Count queries - if more than ~10, investigate
```

#### Method 2: MongoDB Profiler

```javascript
// Enable MongoDB profiler
db.setProfilingLevel(2, { slowms: 50 })

// After running endpoint, analyze
db.system.profile.aggregate([
    { $group: {
        _id: { ns: "$ns", op: "$op" },
        count: { $sum: 1 },
        avgMs: { $avg: "$millis" }
    }},
    { $sort: { count: -1 }}
])

// Look for high "count" values - indicates repeated queries
```

#### Method 3: Manual Code Review

**Red Flags**:
```php
// 🚨 Query inside loop
foreach ($customers as $customer) {
    $type = $this->repository->find($customer->getTypeId());  // BAD!
}

// 🚨 Lazy loading in loop
foreach ($orders as $order) {
    echo $order->getCustomer()->getName();  // Lazy load = query!
}

// 🚨 Accessing relations without eager loading
$customers = $this->repository->findAll();
foreach ($customers as $customer) {
    echo $customer->getStatus()->getName();  // N+1!
}
```

### Solutions for N+1 Queries

#### Solution 1: Eager Loading (MongoDB Priming)

```php
// ✅ GOOD: Eager load with prime()
$qb = $this->documentManager->createQueryBuilder(Customer::class);
$qb->field('type')->prime(true);
$qb->field('status')->prime(true);
$customers = $qb->getQuery()->execute();

// Now accessing relations doesn't trigger queries
foreach ($customers as $customer) {
    echo $customer->getType();    // Already loaded!
    echo $customer->getStatus();  // Already loaded!
}
```

#### Solution 2: Single Query with Aggregation

```php
// ✅ GOOD: Use aggregation pipeline
$pipeline = [
    [
        '$lookup' => [
            'from' => 'customer_types',
            'localField' => 'type_id',
            'foreignField' => '_id',
            'as' => 'type_data'
        ]
    ],
    [
        '$lookup' => [
            'from' => 'customer_statuses',
            'localField' => 'status_id',
            'foreignField' => '_id',
            'as' => 'status_data'
        ]
    ]
];

$result = $this->collection->aggregate($pipeline);
```

#### Solution 3: Batch Loading

```php
// ✅ GOOD: Load all needed IDs in one query
$typeIds = array_map(fn($c) => $c->getTypeId(), $customers);
$types = $this->typeRepository->findByIds($typeIds);  // 1 query

$typeMap = [];
foreach ($types as $type) {
    $typeMap[$type->getId()] = $type;
}

foreach ($customers as $customer) {
    $customer->setType($typeMap[$customer->getTypeId()]);  // No query!
}
```

---

## Slow Query Analysis with EXPLAIN

### Using MongoDB explain()

```javascript
// In MongoDB shell
db.customers.find({ email: "test@example.com" }).explain("executionStats")
```

**Key Metrics to Check**:

```json
{
  "executionStats": {
    "executionTimeMillis": 245,     // 🚨 Goal: <100ms
    "totalDocsExamined": 10000,     // 🚨 Documents scanned
    "totalKeysExamined": 1,         // ✅ Index keys used
    "nReturned": 1,                 // Documents returned
    "executionStages": {
      "stage": "IXSCAN"             // ✅ Index scan (good!)
      // or "COLLSCAN"               // 🚨 Collection scan (bad!)
    }
  }
}
```

### Interpreting EXPLAIN Results

#### ✅ Good Performance (Index Used)

```json
{
  "executionTimeMillis": 5,
  "totalDocsExamined": 1,
  "totalKeysExamined": 1,
  "executionStages": {
    "stage": "IXSCAN",
    "indexName": "email_1"
  }
}
```

**Analysis**: Fast! Uses index, examines only 1 document.

#### 🚨 Poor Performance (Collection Scan)

```json
{
  "executionTimeMillis": 450,
  "totalDocsExamined": 50000,
  "totalKeysExamined": 0,
  "executionStages": {
    "stage": "COLLSCAN"
  }
}
```

**Analysis**: Slow! Scans entire collection, no index used. **FIX**: Add index!

#### ⚠️ Inefficient Index (High Examined Count)

```json
{
  "executionTimeMillis": 180,
  "totalDocsExamined": 5000,
  "totalKeysExamined": 5000,
  "nReturned": 10,
  "executionStages": {
    "stage": "IXSCAN",
    "indexName": "status_1"
  }
}
```

**Analysis**: Uses index but examines 5000 docs to return 10. **FIX**: Add compound index or refine query.

---

## Detecting Missing Indexes

### Automated Detection

#### Method 1: Analyze MongoDB Slow Queries

```bash
# Check MongoDB logs for slow queries
docker compose logs mongodb | grep "Slow query"

# Look for COLLSCAN warnings
docker compose logs mongodb | grep "COLLSCAN"
```

#### Method 2: Query Pattern Analysis

**Check Repository Methods**:
```php
// Find methods that filter/sort
public function findByEmail(string $email): ?Customer
{
    // Does 'email' field have an index?
}

public function findActiveCustomers(): array
{
    // Does 'status' field have an index?
}

public function findRecentCustomers(int $limit): array
{
    // Does 'createdAt' field have an index for sorting?
}
```

#### Method 3: Run Performance Check Script

Create `scripts/check-query-performance.php`:

```php
<?php

// Test common queries and check execution time
$queries = [
    'findByEmail' => ['email' => 'test@example.com'],
    'findByStatus' => ['status' => 'active'],
    'findRecent' => ['sort' => ['createdAt' => -1], 'limit' => 10],
];

foreach ($queries as $name => $criteria) {
    $start = microtime(true);
    $result = $collection->find($criteria)->toArray();
    $duration = (microtime(true) - $start) * 1000;

    echo sprintf("%s: %.2fms\n", $name, $duration);

    if ($duration > 100) {
        echo "⚠️  SLOW! Check indexes for: " . json_encode($criteria) . "\n";
    }
}
```

### Common Missing Index Patterns

#### Pattern 1: WHERE Clause Fields

```php
// Query filters by status
$qb->field('status')->equals('active');

// ✅ NEEDS INDEX: status field
```

```xml
<index><key name="status"/></index>
```

#### Pattern 2: ORDER BY Fields

```php
// Query sorts by createdAt DESC
$qb->sort('createdAt', 'desc');

// ✅ NEEDS INDEX: createdAt field with DESC order
```

```xml
<index><key name="createdAt" order="desc"/></index>
```

#### Pattern 3: Compound Filters

```php
// Query filters by multiple fields
$qb->field('status')->equals('active')
   ->field('type')->equals('premium');

// ✅ NEEDS COMPOUND INDEX: status + type
```

```xml
<index>
    <key name="status"/>
    <key name="type"/>
</index>
```

#### Pattern 4: Text Search

```php
// Query searches text fields
$qb->text('search term');

// ✅ NEEDS TEXT INDEX: searchable fields
```

```xml
<index>
    <key name="name"/>
    <key name="email"/>
    <option name="type" value="text"/>
</index>
```

---

## Safe Online Index Migrations

### MongoDB Index Creation (Non-Blocking)

**Good News**: MongoDB 4.2+ creates indexes in the background by default!

```javascript
// This is non-blocking in MongoDB 4.2+
db.customers.createIndex({ email: 1 })
```

### Doctrine ODM Index Creation

**Step 1: Add Index to XML Mapping**

```xml
<!-- config/doctrine/Customer.mongodb.xml -->
<indexes>
    <index><key name="email" order="asc"/></index>
</indexes>
```

**Step 2: Update Schema (Creates Index)**

```bash
# This creates indexes in background (safe for production)
docker compose exec php bin/console doctrine:mongodb:schema:update
```

### Verification Steps

**Step 1: Verify Index Created**

```javascript
// In MongoDB shell
db.customers.getIndexes()

// Look for your new index
{
  "v": 2,
  "key": { "email": 1 },
  "name": "email_1"
}
```

**Step 2: Verify Index is Used**

```javascript
// Run EXPLAIN on query
db.customers.find({ email: "test@example.com" }).explain("executionStats")

// Check executionStages.indexName matches your index
"indexName": "email_1"  // ✅ Using your new index!
```

**Step 3: Measure Performance Improvement**

```php
// Before index
$start = microtime(true);
$customer = $repository->findByEmail('test@example.com');
$before = (microtime(true) - $start) * 1000;

// After index (should be faster!)
$start = microtime(true);
$customer = $repository->findByEmail('test@example.com');
$after = (microtime(true) - $start) * 1000;

echo "Before: {$before}ms, After: {$after}ms\n";
```

### Production Migration Strategy

**Option 1: Blue-Green Deployment**
1. Add index to XML mapping
2. Deploy new code (no schema update yet)
3. Run `doctrine:mongodb:schema:update` on live database
4. Verify index creation
5. Application automatically uses new index

**Option 2: Manual Index Creation**
1. Create index manually in production first:
   ```javascript
   db.customers.createIndex({ email: 1 })
   ```
2. Verify index exists
3. Update XML mapping in code
4. Deploy code (schema already has index)

**Option 3: Phased Rollout**
1. Add index during low-traffic window
2. Monitor performance
3. Verify no issues
4. Full deployment

---

## Performance Regression Testing

### Add Performance Tests

```php
// tests/Performance/CustomerEndpointTest.php
final class CustomerEndpointTest extends PerformanceTestCase
{
    public function testGetCustomersPerformance(): void
    {
        // Arrange: Create 100 customers
        for ($i = 0; $i < 100; $i++) {
            $this->createCustomer();
        }

        // Act: Measure endpoint performance
        $start = microtime(true);
        $response = $this->client->request('GET', '/api/customers');
        $duration = (microtime(true) - $start) * 1000;

        // Assert: Should complete within acceptable time
        $this->assertLessThan(200, $duration, 'GET /api/customers too slow');

        // Assert: Should not have N+1 queries
        $queryCount = $this->getQueryCount();
        $this->assertLessThan(10, $queryCount, 'Too many queries (N+1 problem?)');
    }
}
```

### Query Count Assertions

```php
public function testNoN1Queries(): void
{
    // Enable query counter
    $this->enableQueryCounter();

    // Execute operation
    $customers = $this->repository->findAll();
    foreach ($customers as $customer) {
        $customer->getType();  // Should not trigger queries
        $customer->getStatus();  // Should not trigger queries
    }

    // Assert: Should be 1 query (the findAll), not N+1
    $this->assertQueryCount(1, 'N+1 query detected!');
}
```

---

## Integration with Other Skills

**Use after**:
- [api-platform-crud](../api-platform-crud/SKILL.md) - After creating endpoints
- [database-migrations](../database-migrations/SKILL.md) - After adding entities/indexes

**Use before**:
- [load-testing](../load-testing/SKILL.md) - Optimize before load testing
- [ci-workflow](../ci-workflow/SKILL.md) - Validate performance in CI

**Related skills**:
- [testing-workflow](../testing-workflow/SKILL.md) - Add performance tests
- [structurizr-architecture-sync](../structurizr-architecture-sync/SKILL.md) - Document performance changes

---

## Quick Reference Commands

```bash
# Enable MongoDB profiler
docker compose exec mongodb mongosh
use core_service_db
db.setProfilingLevel(2, { slowms: 100 })

# View slow queries
db.system.profile.find({ millis: { $gt: 100 } }).sort({ ts: -1 })

# Check indexes
db.customers.getIndexes()

# EXPLAIN query
db.customers.find({ email: "test@example.com" }).explain("executionStats")

# Update schema (creates indexes)
docker compose exec php bin/console doctrine:mongodb:schema:update

# Run performance tests
make unit-tests --filter=Performance
```

---

## Reference Documentation

- **[examples/n-plus-one-detection.md](examples/n-plus-one-detection.md)** - Complete N+1 detection examples
- **[examples/slow-query-analysis.md](examples/slow-query-analysis.md)** - EXPLAIN analysis guide
- **[examples/missing-index-detection.md](examples/missing-index-detection.md)** - Finding missing indexes
- **[reference/performance-thresholds.md](reference/performance-thresholds.md)** - Acceptable performance limits
- **[reference/mongodb-profiler-guide.md](reference/mongodb-profiler-guide.md)** - Complete profiler documentation
- **[reference/index-strategies.md](reference/index-strategies.md)** - When to use which index type

---

## Troubleshooting

**Issue**: Can't enable MongoDB profiler

**Solution**: Check MongoDB version (requires 4.0+), verify permissions

---

**Issue**: EXPLAIN shows COLLSCAN but index exists

**Solution**:
1. Verify index covers your query pattern
2. Check compound index field order
3. Ensure query uses indexed fields

---

**Issue**: Index not improving performance

**Solution**:
1. Check if index is actually used (EXPLAIN)
2. Verify index selectivity (high cardinality fields)
3. Consider compound index with query order

---

**Issue**: Too many indexes slowing writes

**Solution**:
1. Remove unused indexes
2. Combine similar indexes into compound indexes
3. Profile write operations for impact

---

## External Resources

- **MongoDB EXPLAIN Documentation**: https://docs.mongodb.com/manual/reference/explain-results/
- **MongoDB Profiler**: https://docs.mongodb.com/manual/tutorial/manage-the-database-profiler/
- **MongoDB Indexing Strategies**: https://docs.mongodb.com/manual/applications/indexes/
- **Doctrine ODM Performance**: https://www.doctrine-project.org/projects/doctrine-mongodb-odm/en/latest/reference/performance.html

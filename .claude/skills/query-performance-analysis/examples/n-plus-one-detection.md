# N+1 Query Detection and Fix

## Scenario

You've implemented GET `/api/customers` that returns customers with their types and statuses. Users complain it's slow. Profiler shows 201 database queries!

## Problem Description

```php
// Current implementation
public function getCustomers(): array
{
    $customers = $this->customerRepository->findAll();  // 1 query

    $result = [];
    foreach ($customers as $customer) {
        $result[] = [
            'id' => $customer->getId(),
            'name' => $customer->getName(),
            'type' => $this->getTypeName($customer->getTypeId()),      // 1 query per customer!
            'status' => $this->getStatusName($customer->getStatusId()), // 1 query per customer!
        ];
    }

    return $result;
}

private function getTypeName(string $typeId): string
{
    $type = $this->typeRepository->findById($typeId);  // Query executed in loop!
    return $type->getName();
}

private function getStatusName(string $statusId): string
{
    $status = $this->statusRepository->findById($statusId);  // Query executed in loop!
    return $status->getName();
}
```

**Result**: 1 + 100 + 100 = **201 queries** for 100 customers!

---

## Detection Steps

### Step 1: Enable Symfony Profiler

```bash
# Run the endpoint
curl http://localhost/api/customers

# Check profiler
open http://localhost/_profiler

# Look at "Doctrine" or "Database" panel
# Query count: 201 queries 🚨
```

### Step 2: Enable MongoDB Profiler

```bash
# Connect to MongoDB
docker compose exec database mongosh -u root -p secret --authenticationDatabase admin
```

```javascript
// List all databases to find yours
show dbs

// Switch to your database (typically 'app' for this project)
use app

// Enable profiler
db.setProfilingLevel(2, { slowms: 100 })
```

### Step 3: Run Endpoint and Analyze

```bash
# Run endpoint
curl http://localhost/api/customers
```

```javascript
// Check profiled queries
db.system.profile.aggregate([
  {
    $group: {
      _id: {
        collection: '$ns',
        operation: '$op',
        query: '$command.filter',
      },
      count: { $sum: 1 },
      avgMs: { $avg: '$millis' },
    },
  },
  {
    $sort: { count: -1 },
  },
]);
```

**Output**:

```json
[
  {
    "_id": { "collection": "app.customer_types", "operation": "query" },
    "count": 100, // 🚨 Same query 100 times!
    "avgMs": 5
  },
  {
    "_id": { "collection": "app.customer_statuses", "operation": "query" },
    "count": 100, // 🚨 Same query 100 times!
    "avgMs": 4
  },
  {
    "_id": { "collection": "app.customers", "operation": "query" },
    "count": 1,
    "avgMs": 25
  }
]
```

**Analysis**: Two queries are executed 100 times each → **N+1 problem!**

---

## Solutions

### Solution 1: Eager Loading with Priming (MongoDB ODM)

```php
// ✅ FIXED: Use prime() to eager load references
public function getCustomers(): array
{
    $qb = $this->documentManager->createQueryBuilder(Customer::class);

    // Prime (eager load) type and status references
    $qb->field('type')->prime(true);
    $qb->field('status')->prime(true);

    $customers = $qb->getQuery()->execute();

    $result = [];
    foreach ($customers as $customer) {
        $result[] = [
            'id' => $customer->getId(),
            'name' => $customer->getName(),
            // These are already loaded - no additional queries!
            'type' => $this->getTypeName($customer->getTypeId()),
            'status' => $this->getStatusName($customer->getStatusId()),
        ];
    }

    return $result;
}
```

**Result**: **3 queries total** (customers + types + statuses)

---

### Solution 2: Batch Loading

```php
// ✅ ALTERNATIVE: Load all types and statuses in 2 queries
public function getCustomers(): array
{
    // 1. Get all customers
    $customers = $this->customerRepository->findAll();  // 1 query

    // 2. Extract all type IDs
    $typeIds = array_unique(array_map(
        fn($c) => $c->getTypeId(),
        $customers
    ));

    // 3. Load all types in one query
    $types = $this->typeRepository->findByIds($typeIds);  // 1 query

    // 4. Extract all status IDs
    $statusIds = array_unique(array_map(
        fn($c) => $c->getStatusId(),
        $customers
    ));

    // 5. Load all statuses in one query
    $statuses = $this->statusRepository->findByIds($statusIds);  // 1 query

    // 6. Create lookup maps
    $typeMap = [];
    foreach ($types as $type) {
        $typeMap[$type->getId()] = $type->getName();
    }

    $statusMap = [];
    foreach ($statuses as $status) {
        $statusMap[$status->getId()] = $status->getName();
    }

    // 7. Build result without additional queries
    $result = [];
    foreach ($customers as $customer) {
        $result[] = [
            'id' => $customer->getId(),
            'name' => $customer->getName(),
            'type' => $typeMap[$customer->getTypeId()] ?? 'Unknown',
            'status' => $statusMap[$customer->getStatusId()] ?? 'Unknown',
        ];
    }

    return $result;
}

// Repository method
public function findByIds(array $ids): array
{
    return $this->createQueryBuilder(CustomerType::class)
        ->field('id')->in($ids)
        ->getQuery()
        ->execute()
        ->toArray();
}
```

**Result**: **3 queries total**

---

### Solution 3: MongoDB Aggregation Pipeline

```php
// ✅ ALTERNATIVE: Single query with $lookup
public function getCustomers(): array
{
    $pipeline = [
        // Lookup types
        [
            '$lookup' => [
                'from' => 'customer_types',
                'localField' => 'type_id',
                'foreignField' => '_id',
                'as' => 'type_data'
            ]
        ],
        // Lookup statuses
        [
            '$lookup' => [
                'from' => 'customer_statuses',
                'localField' => 'status_id',
                'foreignField' => '_id',
                'as' => 'status_data'
            ]
        ],
        // Unwind arrays (convert from array to object)
        [
            '$unwind' => [
                'path' => '$type_data',
                'preserveNullAndEmptyArrays' => true
            ]
        ],
        [
            '$unwind' => [
                'path' => '$status_data',
                'preserveNullAndEmptyArrays' => true
            ]
        ],
        // Project final structure
        [
            '$project' => [
                '_id' => 1,
                'name' => 1,
                'email' => 1,
                'type_name' => '$type_data.name',
                'status_name' => '$status_data.name'
            ]
        ]
    ];

    $collection = $this->documentManager->getDocumentCollection(Customer::class);
    return $collection->aggregate($pipeline)->toArray();
}
```

**Result**: **1 query total** (most efficient!)

---

## Verification

### Step 1: Clear MongoDB Profile

```javascript
db.system.profile.drop();
db.setProfilingLevel(2, { slowms: 100 });
```

### Step 2: Run Endpoint Again

```bash
curl http://localhost/api/customers
```

### Step 3: Check Query Count

```javascript
db.system.profile.count();
// Should be 3 (or 1 with aggregation)
```

### Step 4: Check Execution Time

```javascript
db.system.profile.find().sort({ ts: -1 }).limit(10).pretty();
```

**Before fix**:

```json
{
  "totalQueries": 201,
  "totalTimeMs": 1250
}
```

**After fix (batch loading)**:

```json
{
  "totalQueries": 3,
  "totalTimeMs": 45
}
```

**Improvement**: 27x faster, 67x fewer queries! ✅

---

## Add Performance Test

```php
// tests/Performance/CustomerEndpointTest.php

final class CustomerEndpointTest extends ApiTestCase
{
    public function testGetCustomersHasNoN1Queries(): void
    {
        // Arrange: Create 50 customers with types and statuses
        for ($i = 0; $i < 50; $i++) {
            $this->createCustomer([
                'name' => "Customer $i",
                'type' => '/api/customer_types/1',
                'status' => '/api/customer_statuses/1',
            ]);
        }

        // Act: Enable query counter
        $this->enableQueryCounter();

        $response = $this->client->request('GET', '/api/customers');

        // Assert: Should have minimal queries (not N+1)
        $queryCount = $this->getQueryCount();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertLessThan(10, $queryCount,
            "N+1 query detected! Expected <10 queries, got {$queryCount}"
        );

        // Ideally should be 3 queries:
        // 1. Customers
        // 2. Types
        // 3. Statuses
        $this->assertEquals(3, $queryCount, 'Should use batch loading (3 queries)');
    }

    public function testGetCustomersPerformance(): void
    {
        // Arrange: Create realistic data set
        for ($i = 0; $i < 100; $i++) {
            $this->createCustomer();
        }

        // Act: Measure response time
        $start = microtime(true);
        $response = $this->client->request('GET', '/api/customers');
        $duration = (microtime(true) - $start) * 1000;

        // Assert: Should respond quickly
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertLessThan(200, $duration,
            "GET /api/customers too slow: {$duration}ms"
        );
    }
}
```

---

## Common Questions

### Q: How do I know if I have an N+1 problem?

**A**: Look for these signs:

- Query count grows with data size (100 items = 100+ queries)
- Same query executed many times with different parameters
- Queries inside `foreach` loops in your code
- Profiler shows high query count for simple operations

### Q: Which solution should I use?

**A**:

- **Priming (Solution 1)**: Simplest, works well with Doctrine ODM
- **Batch loading (Solution 2)**: More control, works with any ORM
- **Aggregation (Solution 3)**: Best performance, requires MongoDB knowledge

Choose based on your comfort level and requirements.

### Q: What if I can't use eager loading?

**A**: Use batch loading (Solution 2). Always better than N+1 queries.

### Q: How do I test for N+1 in CI?

**A**: Add query count assertions to your tests (see "Add Performance Test" section above).

### Q: What's an acceptable query count?

**A**: Target <5 queries per endpoint, max 10. Never 100+!

---

## Next Steps

1. **Run EXPLAIN on remaining queries**: See [slow-query-analysis.md](slow-query-analysis.md)
2. **Check for missing indexes**: See [missing-index-detection.md](missing-index-detection.md)
3. **Add load tests**: Use [load-testing skill](../../load-testing/SKILL.md)
4. **Monitor in production**: Set up query monitoring

---

## Prevention Checklist

After fixing N+1:

- [ ] Profiler shows expected query count (<10)
- [ ] Performance test added to prevent regression
- [ ] Code review to check for similar patterns elsewhere
- [ ] Documentation updated with eager loading pattern
- [ ] Team informed about N+1 anti-pattern

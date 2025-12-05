# Slow Query Analysis with EXPLAIN

## Scenario

Search endpoint `/api/customers?search=john` takes 3 seconds to respond. Need to identify why it's slow and fix it.

## Problem Description

```php
public function searchCustomers(string $term): array
{
    return $this->createQueryBuilder(Customer::class)
        ->field('name')->equals(new \MongoDB\BSON\Regex($term, 'i'))
        ->getQuery()
        ->execute()
        ->toArray();
}
```

**Symptom**: Takes 3+ seconds for 50,000 documents

---

## Step 1: Enable MongoDB Profiler

```bash
docker compose exec mongodb mongosh

use core_service_db
db.setProfilingLevel(2, { slowms: 100 })
```

---

## Step 2: Execute Query

```bash
curl "http://localhost/api/customers?search=john"
```

---

## Step 3: Find Slow Queries

```javascript
// Find queries slower than 100ms
db.system.profile.find({
    millis: { $gt: 100 }
}).sort({ ts: -1 }).limit(5).pretty()
```

**Output**:
```json
{
  "op": "query",
  "ns": "core_service_db.customers",
  "command": {
    "find": "customers",
    "filter": { "name": { "$regex": "john", "$options": "i" } }
  },
  "execStats": {
    "executionTimeMillis": 2847,  // 🚨 2.8 seconds!
    "totalDocsExamined": 50000,   // 🚨 Scanned all docs!
    "nReturned": 12
  }
}
```

---

## Step 4: Run EXPLAIN

```javascript
db.customers.find({
    name: { $regex: /john/i }
}).explain("executionStats")
```

**Full Output**:
```json
{
  "queryPlanner": {
    "winningPlan": {
      "stage": "COLLSCAN",  // 🚨 Collection scan!
      "filter": {
        "name": { "$regex": "john" }
      }
    },
    "rejectedPlans": []
  },
  "executionStats": {
    "executionSuccess": true,
    "nReturned": 12,
    "executionTimeMillis": 2847,
    "totalKeysExamined": 0,        // 🚨 No index used!
    "totalDocsExamined": 50000,    // 🚨 Scanned entire collection!
    "executionStages": {
      "stage": "COLLSCAN",
      "nReturned": 12,
      "executionTimeMillisEstimate": 2823,
      "works": 50002,
      "advanced": 12,
      "needTime": 49989,
      "docsExamined": 50000
    }
  }
}
```

---

## Analysis

### Red Flags 🚨

1. **`stage: "COLLSCAN"`** - Collection scan (no index)
2. **`totalDocsExamined: 50000`** - Scanned every document
3. **`totalKeysExamined: 0`** - No index keys examined
4. **`executionTimeMillis: 2847`** - Very slow (2.8s)
5. **`nReturned: 12` vs `docsExamined: 50000`** - Examined 4,166 docs per result!

### Problem

Regex query on `name` field with no index → **full collection scan**.

---

## Solution: Add Text Index

### Option 1: Text Index (Best for Full-Text Search)

```xml
<!-- config/doctrine/Customer.mongodb.xml -->
<indexes>
    <index>
        <key name="name"/>
        <key name="email"/>
        <option name="type" value="text"/>
    </index>
</indexes>
```

**Update schema**:
```bash
docker compose exec php bin/console doctrine:mongodb:schema:update
```

**Update query to use text search**:
```php
public function searchCustomers(string $term): array
{
    return $this->createQueryBuilder(Customer::class)
        ->text($term)  // Use text index
        ->getQuery()
        ->execute()
        ->toArray();
}
```

---

### Option 2: Regular Index with Prefix Match

```xml
<indexes>
    <index><key name="name" order="asc"/></index>
</indexes>
```

**Update query**:
```php
public function searchCustomers(string $term): array
{
    // Use prefix match (can use index)
    return $this->createQueryBuilder(Customer::class)
        ->field('name')->equals(new \MongoDB\BSON\Regex('^' . $term, 'i'))
        ->getQuery()
        ->execute()
        ->toArray();
}
```

**Note**: Prefix regex (`^term`) can use index. Non-prefix regex cannot!

---

## Verification

### Step 1: Run EXPLAIN Again

```javascript
// For text index
db.customers.find({
    $text: { $search: "john" }
}).explain("executionStats")
```

**Output After Fix**:
```json
{
  "queryPlanner": {
    "winningPlan": {
      "stage": "TEXT",  // ✅ Using text index!
      "indexName": "name_text_email_text"
    }
  },
  "executionStats": {
    "executionSuccess": true,
    "nReturned": 12,
    "executionTimeMillis": 15,     // ✅ 15ms (was 2847ms!)
    "totalKeysExamined": 24,       // ✅ Only examined index entries
    "totalDocsExamined": 12,       // ✅ Only examined matching docs
    "executionStages": {
      "stage": "TEXT",
      "nReturned": 12,
      "executionTimeMillisEstimate": 12
    }
  }
}
```

### Improvement

- **Before**: 2847ms, scanned 50,000 docs
- **After**: 15ms, scanned 12 docs
- **Result**: **189x faster!** ✅

---

## EXPLAIN Key Metrics Reference

| Field | Good Value | Bad Value | Meaning |
|-------|-----------|-----------|---------|
| `stage` | `IXSCAN`, `TEXT`, `IDHACK` | `COLLSCAN` | Execution method |
| `executionTimeMillis` | <100ms | >500ms | Total query time |
| `totalKeysExamined` | Low number | 0 | Index entries examined |
| `totalDocsExamined` | ≈ nReturned | >> nReturned | Documents scanned |
| `nReturned` | Any | Any | Documents returned |
| `indexName` | Present | Absent | Index used |

---

## Common Slow Query Patterns

### Pattern 1: No Index (COLLSCAN)

```javascript
// Slow
db.customers.find({ status: "active" })

// Stage: COLLSCAN, Examined: 50000
```

**Fix**: Add index on `status`

---

### Pattern 2: Non-Selective Index

```javascript
// Has index but not selective
db.customers.find({ status: "active" })  // 49,000 of 50,000 are active!

// Stage: IXSCAN, Examined: 49000, Returned: 49000
```

**Fix**: Add compound index or refine query

---

### Pattern 3: Wrong Index Order (Compound Index)

```xml
<!-- Index: status + createdAt -->
<index>
    <key name="status"/>
    <key name="createdAt" order="desc"/>
</index>
```

```javascript
// This can't use the index efficiently!
db.customers.find({ createdAt: { $gt: date } })

// Stage: COLLSCAN (skips first field in compound index)
```

**Fix**: Create separate index on `createdAt` or reorder compound index

---

### Pattern 4: Regex Without Prefix

```javascript
// Can't use index
db.customers.find({ name: { $regex: /john/i } })

// Stage: COLLSCAN
```

**Fix**: Use text index or prefix regex (`/^john/i`)

---

### Pattern 5: Sort Without Index

```javascript
// Sort field not indexed
db.customers.find({}).sort({ createdAt: -1 })

// Stage: COLLSCAN + in-memory sort
```

**Fix**: Add index on `createdAt` with correct order

---

## Performance Testing

```php
final class CustomerSearchPerformanceTest extends ApiTestCase
{
    public function testSearchCustomersPerformance(): void
    {
        // Arrange: Create realistic dataset
        for ($i = 0; $i < 1000; $i++) {
            $this->createCustomer(['name' => "Customer $i"]);
        }

        // Act: Measure search performance
        $start = microtime(true);
        $response = $this->client->request('GET', '/api/customers', [
            'query' => ['search' => 'Customer']
        ]);
        $duration = (microtime(true) - $start) * 1000;

        // Assert: Should be fast
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertLessThan(100, $duration,
            "Search too slow: {$duration}ms"
        );
    }

    public function testSearchUsesTextIndex(): void
    {
        $this->createCustomer(['name' => 'John Doe']);
        $this->createCustomer(['name' => 'Jane Smith']);

        $this->enableQueryAnalysis();

        $this->client->request('GET', '/api/customers', [
            'query' => ['search' => 'John']
        ]);

        $explain = $this->getLastQueryExplain();

        // Assert: Uses text index
        $this->assertEquals('TEXT', $explain['executionStats']['executionStages']['stage']);
        $this->assertLessThan(100, $explain['executionStats']['executionTimeMillis']);
    }
}
```

---

## Common Questions

### Q: When should I run EXPLAIN?

**A**: Always run EXPLAIN when:
- Query takes >100ms
- Adding a new query pattern
- Suspecting index isn't being used
- Optimizing existing queries

### Q: What if IXSCAN shows high `docsExamined`?

**A**: Index isn't selective enough. Consider:
- Compound index with better field order
- More specific query filters
- Different index strategy

### Q: Can I run EXPLAIN in production?

**A**: Yes, but:
- Use `explain("executionStats")` (doesn't execute query)
- Don't run on every request (performance overhead)
- Use selectively for debugging

### Q: How do I find ALL slow queries?

**A**: Use profiler:
```javascript
db.system.profile.find({
    millis: { $gt: 100 }
}).sort({ millis: -1 })
```

---

## Next Steps

1. **Check for N+1 queries**: [n-plus-one-detection.md](n-plus-one-detection.md)
2. **Add missing indexes**: [missing-index-detection.md](missing-index-detection.md)
3. **Deploy safely**: [safe-index-migration.md](safe-index-migration.md)

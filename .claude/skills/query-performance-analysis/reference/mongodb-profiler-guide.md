# MongoDB Profiler Complete Guide

## Overview

MongoDB Profiler logs all database operations for performance analysis. Essential for detecting slow queries and N+1 problems.

## Profiling Levels

| Level | Description          | Use Case              | Performance Impact |
| ----- | -------------------- | --------------------- | ------------------ |
| 0     | Off                  | Production (default)  | None               |
| 1     | Slow operations only | Production monitoring | Minimal            |
| 2     | All operations       | Development/debugging | High               |

## Enable Profiler

### Development (All Queries)

```javascript
docker compose exec database mongosh -u root -p secret --authenticationDatabase admin

// List databases first
show dbs

// Switch to your application database (typically 'app' for this project)
use app

// Log all operations slower than 50ms
db.setProfilingLevel(2, { slowms: 50 })
```

### Production (Slow Queries Only)

```javascript
// Log only operations slower than 200ms
db.setProfilingLevel(1, { slowms: 200 });
```

### Check Status

```javascript
db.getProfilingStatus()

// Output:
{
  "was": 2,
  "slowms": 50,
  "sampleRate": 1.0
}
```

## Profiler Collection

Profiled queries are stored in `system.profile` collection.

### View Recent Queries

```javascript
db.system.profile.find().sort({ ts: -1 }).limit(10).pretty();
```

### View Slow Queries

```javascript
db.system.profile
  .find({
    millis: { $gt: 100 },
  })
  .sort({ millis: -1 })
  .pretty();
```

### Find Specific Operations

```javascript
// Find queries on 'customers' collection
// Format: ns: "<database_name>.<collection_name>"
db.system.profile
  .find({
    ns: 'app.customers',
    op: 'query',
  })
  .pretty();

// Find updates
db.system.profile
  .find({
    op: 'update',
  })
  .pretty();

// Find inserts
db.system.profile
  .find({
    op: 'insert',
  })
  .pretty();
```

## Profile Entry Fields

### Key Fields

```javascript
{
  "op": "query",              // Operation type: query, insert, update, delete, command
  "ns": "dbname.collection",  // Namespace (database.collection)
  "command": {                // The actual command executed
    "find": "customers",
    "filter": { "status": "active" }
  },
  "keysExamined": 100,        // Number of index keys scanned
  "docsExamined": 100,        // Number of documents scanned
  "nreturned": 10,            // Number of documents returned
  "millis": 245,              // Execution time in milliseconds
  "ts": ISODate("..."),       // Timestamp
  "client": "127.0.0.1:1234", // Client connection
  "user": "admin"             // User who executed
}
```

## Common Analysis Queries

### Find N+1 Patterns

```javascript
// Group by query pattern and count
db.system.profile.aggregate([
  {
    $match: {
      op: 'query',
      ns: { $ne: 'app.system.profile' }, // Exclude profiler collection itself
    },
  },
  {
    $group: {
      _id: {
        collection: '$ns',
        filter: '$command.filter',
      },
      count: { $sum: 1 },
      avgMs: { $avg: '$millis' },
      totalMs: { $sum: '$millis' },
    },
  },
  {
    $match: {
      count: { $gt: 10 }, // Queries executed >10 times
    },
  },
  {
    $sort: { count: -1 },
  },
]);

// Look for high "count" values = repeated queries = N+1 problem!
```

### Find Collection Scans

```javascript
// Find queries that scanned entire collection
db.system.profile
  .find({
    op: 'query',
    docsExamined: { $gt: 1000 }, // Scanned >1000 docs
    planSummary: 'COLLSCAN', // Collection scan
  })
  .sort({ millis: -1 });
```

### Find Inefficient Indexes

```javascript
// Find queries where docsExamined >> nreturned
db.system.profile
  .find({
    op: 'query',
    $expr: {
      $gt: [
        { $divide: ['$docsExamined', { $max: ['$nreturned', 1] }] },
        10, // Examined 10x more docs than returned
      ],
    },
  })
  .sort({ millis: -1 });
```

### Find Slowest Queries

```javascript
db.system.profile.find().sort({ millis: -1 }).limit(10);
```

### Queries by Time Range

```javascript
db.system.profile
  .find({
    ts: {
      $gte: ISODate('2024-01-15T10:00:00Z'),
      $lt: ISODate('2024-01-15T11:00:00Z'),
    },
  })
  .sort({ millis: -1 });
```

## Performance Summary

```javascript
// Get overall statistics
db.system.profile.aggregate([
  {
    $group: {
      _id: '$op',
      count: { $sum: 1 },
      avgMs: { $avg: '$millis' },
      maxMs: { $max: '$millis' },
      totalMs: { $sum: '$millis' },
    },
  },
  {
    $sort: { totalMs: -1 },
  },
]);
```

**Output Example**:

```json
[
  {
    "_id": "query",
    "count": 1532,
    "avgMs": 45.2,
    "maxMs": 2847,
    "totalMs": 69246
  },
  {
    "_id": "insert",
    "count": 245,
    "avgMs": 12.5,
    "totalMs": 3062
  }
]
```

## Managing Profile Data

### Clear Profile Collection

```javascript
// Profile collection can grow large - clear periodically
db.system.profile.drop();

// Re-enable profiling
db.setProfilingLevel(2, { slowms: 50 });
```

### Limit Profile Collection Size

```javascript
// Create capped collection (auto-removes old entries)
db.setProfilingLevel(0); // Disable first

db.system.profile.drop();

db.createCollection('system.profile', {
  capped: true,
  size: 4000000, // 4MB
});

db.setProfilingLevel(2, { slowms: 50 }); // Re-enable
```

### Profile Sampling (Production)

```javascript
// In production, sample only 10% of queries
db.setProfilingLevel(1, {
  slowms: 200,
  sampleRate: 0.1, // Sample 10% of operations
});
```

## Integration with PHP

### Log Profiler Results in Tests

```php
final class ProfilerHelper
{
    public function getProfiledQueries(): array
    {
        $mongodb = $this->getMongoClient();
        // Set DB_NAME in your .env file with the name from 'show dbs' command
        $dbName = $_ENV['DB_NAME'] ?? throw new \RuntimeException('DB_NAME not configured');
        $collection = $mongodb->selectCollection($dbName, 'system.profile');

        return $collection->find(
            ['millis' => ['$gt' => 100]],
            ['sort' => ['ts' => -1], 'limit' => 10]
        )->toArray();
    }

    public function getQueryCount(string $collectionName): int
    {
        $mongodb = $this->getMongoClient();
        // Set DB_NAME in your .env file with the name from 'show dbs' command
        $dbName = $_ENV['DB_NAME'] ?? throw new \RuntimeException('DB_NAME not configured');
        $collection = $mongodb->selectCollection($dbName, 'system.profile');

        return $collection->countDocuments([
            'ns' => "{$dbName}.{$collectionName}",
            'op' => 'query'
        ]);
    }

    public function assertNoN1Queries(int $maxQueries = 10): void
    {
        $queryCount = $this->getQueryCount('customers');

        if ($queryCount > $maxQueries) {
            $queries = $this->getProfiledQueries();
            $this->fail(
                "N+1 query detected! Found {$queryCount} queries (max: {$maxQueries})\n" .
                "Queries:\n" . json_encode($queries, JSON_PRETTY_PRINT)
            );
        }
    }
}
```

## Best Practices

### DO

✅ Enable profiling in development (level 2)
✅ Use level 1 with high slowms in production (200-500ms)
✅ Clear profile collection regularly
✅ Use sampling in production (sampleRate: 0.1)
✅ Analyze profile data after implementing new features
✅ Set appropriate slowms threshold per environment

### DON'T

❌ Leave level 2 enabled in production (performance impact)
❌ Set slowms too low in production (<100ms = noise)
❌ Forget to clear profile collection (grows infinitely)
❌ Profile on every request in production
❌ Ignore repeated query patterns

## Profiling Workflow

### Development Workflow

1. **Enable profiler** (level 2, slowms: 50)
2. **Run endpoint/feature**
3. **Analyze profile data**
4. **Fix issues** (N+1, slow queries, missing indexes)
5. **Clear profile collection**
6. **Re-test**
7. **Verify improvements**

### Production Monitoring

1. **Enable level 1** (slowms: 200, sampleRate: 0.1)
2. **Export profile data** periodically to monitoring system
3. **Alert on slow queries**
4. **Investigate patterns**
5. **Fix in development**
6. **Deploy fixes**

## Common Patterns to Look For

### 🚨 Red Flag #1: High Query Count

```
count: 100+ for same query pattern
→ N+1 problem!
```

### 🚨 Red Flag #2: COLLSCAN

```
planSummary: "COLLSCAN"
→ Missing index!
```

### 🚨 Red Flag #3: High docsExamined

```
docsExamined: 10000, nreturned: 10
→ Inefficient index or query!
```

### 🚨 Red Flag #4: Slow Execution

```
millis: >1000
→ Needs optimization!
```

## Troubleshooting

**Issue**: Profiler not capturing queries

**Solution**:

```javascript
// Check profiling level
db.getProfilingStatus();

// Ensure level > 0
db.setProfilingLevel(2);
```

---

**Issue**: Profile collection too large

**Solution**:

```javascript
// Check size
db.system.profile.stats().size;

// Clear if needed
db.system.profile.drop();
db.setProfilingLevel(2, { slowms: 50 });
```

---

**Issue**: Can't find specific queries

**Solution**:

```javascript
// Check namespace
db.system.profile.distinct('ns');

// Check operations
db.system.profile.distinct('op');

// Verify timestamp range
db.system.profile.find().sort({ ts: -1 }).limit(1);
```

## External Resources

- **[MongoDB Profiler Documentation](https://docs.mongodb.com/manual/tutorial/manage-the-database-profiler/)**
- **[Analyzing MongoDB Performance](https://docs.mongodb.com/manual/administration/analyzing-mongodb-performance/)**

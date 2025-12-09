# Index Selection Strategies

Quick guide for choosing the right index type and structure.

## When to Add an Index

Add an index when:

- Query filters on a field (`WHERE status = 'active'`)
- Query sorts on a field (`ORDER BY createdAt DESC`)
- Query uses both filter and sort on same fields
- EXPLAIN shows COLLSCAN (collection scan)
- Query execution time >100ms

**Don't index**:

- Fields that are rarely queried
- Fields with very low cardinality (e.g., boolean with only 2 values)
- Collections with <1000 documents (usually fast enough without indexes)

---

## Index Type Selection

### Single Field Index

**When**: Queries filter/sort on ONE field

```xml
<index><key name="email" order="asc"/></index>
```

**Use cases**:

- `find({ email: "user@example.com" })`
- `find({}).sort({ createdAt: -1 })`
- Unique constraints

---

### Compound Index

**When**: Queries filter/sort on MULTIPLE fields

```xml
<index>
    <key name="status"/>
    <key name="type"/>
    <key name="createdAt" order="desc"/>
</index>
```

**Use cases**:

- `find({ status: "active", type: "premium" })`
- `find({ status: "active" }).sort({ createdAt: -1 })`

**IMPORTANT**: Field order matters!

- Most selective field first
- Equality filters before range filters
- Sort fields last

---

### Text Index

**When**: Full-text search on string fields

```xml
<index>
    <key name="name"/>
    <key name="email"/>
    <key name="description"/>
    <option name="type" value="text"/>
</index>
```

**Use cases**:

- Search functionality
- `$text` queries
- Multiple string fields

**Limitations**:

- Only ONE text index per collection
- Slower than regular indexes
- Can't use for exact matches

---

### Unique Index

**When**: Field must be unique (like email, username)

```xml
<index>
    <key name="email" order="asc"/>
    <option name="unique" value="true"/>
</index>
```

**Use cases**:

- Enforcing uniqueness at database level
- Primary keys
- Natural keys (email, username)

---

### Sparse Index

**When**: Field exists in only some documents

```xml
<index>
    <key name="taxId" order="asc"/>
    <option name="sparse" value="true"/>
</index>
```

**Use cases**:

- Optional fields
- Reduces index size
- Documents without field aren't indexed

---

## Compound Index Field Order

**Rule**: Equality → Sort → Range

### Example Query

```javascript
db.customers
  .find({
    status: 'active', // Equality
    type: 'premium', // Equality
  })
  .sort({
    createdAt: -1, // Sort
  });
```

### Optimal Index

```xml
<index>
    <key name="status"/>        <!-- Equality first -->
    <key name="type"/>          <!-- Equality second -->
    <key name="createdAt" order="desc"/>  <!-- Sort last -->
</index>
```

### Why This Order?

1. **Equality fields** narrow down results fastest
2. **Sort fields** can use index for sorting
3. **Range fields** would stop index usage for subsequent fields

---

## Index Prefix Rule

A compound index can be used for queries on its **prefixes**.

**Index**: `{ status: 1, type: 1, createdAt: -1 }`

**Can be used for**:

- ✅ `{ status: "active" }`
- ✅ `{ status: "active", type: "premium" }`
- ✅ `{ status: "active", type: "premium", createdAt: { $gte: date } }`

**Cannot be used for**:

- ❌ `{ type: "premium" }` (skips first field)
- ❌ `{ createdAt: { $gte: date } }` (skips first two fields)
- ❌ `{ type: "premium", createdAt: { $gte: date } }` (skips first field)

---

## Common Patterns

### Pattern 1: Status Filter + Date Sort

**Query**: Active items, newest first

```javascript
db.items.find({ status: 'active' }).sort({ createdAt: -1 });
```

**Index**:

```xml
<index>
    <key name="status"/>
    <key name="createdAt" order="desc"/>
</index>
```

---

### Pattern 2: Multiple Filters

**Query**: Filter by status and type

```javascript
db.customers.find({ status: 'active', type: 'premium' });
```

**Index**:

```xml
<index>
    <key name="status"/>
    <key name="type"/>
</index>
```

---

### Pattern 3: Range Query + Sort

**Query**: Recent items from last week

```javascript
db.items.find({ createdAt: { $gte: lastWeek } }).sort({ createdAt: -1 });
```

**Index**:

```xml
<index><key name="createdAt" order="desc"/></index>
```

---

### Pattern 4: Unique Lookup

**Query**: Find by email

```javascript
db.users.findOne({ email: 'user@example.com' });
```

**Index**:

```xml
<index>
    <key name="email" order="asc"/>
    <option name="unique" value="true"/>
</index>
```

---

## Decision Flowchart

```
Does query filter on ONE field?
├─ Yes → Single field index
└─ No
    ├─ Does query filter on MULTIPLE fields?
    │   └─ Yes → Compound index (equality → sort → range)
    └─ Does query need full-text search?
        └─ Yes → Text index
```

---

## Performance Considerations

### Index Size

- Each index consumes disk space and RAM
- Target: 3-5 indexes per collection max
- Remove unused indexes

### Write Performance

- More indexes = slower writes
- Balance read vs write performance
- Use `db.collection.stats()` to check index size

### Index Selectivity

- High cardinality fields make good indexes
- Low cardinality fields (e.g., boolean) are poor indexes
- Example:
  - ✅ Good: `email` (unique values)
  - ❌ Poor: `isActive` (only true/false)

---

## Verification Steps

After creating an index:

1. **Verify index exists**:

   ```javascript
   db.collection.getIndexes();
   ```

2. **Verify index is used**:

   ```javascript
   db.collection.find({ field: value }).explain('executionStats');
   // Look for stage: "IXSCAN" and indexName
   ```

3. **Measure performance**:
   ```javascript
   db.collection.find({ field: value }).explain('executionStats').executionStats
     .executionTimeMillis;
   // Should be <100ms
   ```

---

## Anti-Patterns

### ❌ Anti-Pattern 1: Too Many Indexes

**Problem**: 10+ indexes on one collection

**Impact**: Slow writes, high memory usage

**Solution**: Remove unused indexes, consolidate similar indexes

---

### ❌ Anti-Pattern 2: Wrong Compound Index Order

**Problem**: Sort field before equality fields

```xml
<!-- ❌ BAD -->
<index>
    <key name="createdAt" order="desc"/>
    <key name="status"/>
</index>
```

**Solution**: Equality fields first

```xml
<!-- ✅ GOOD -->
<index>
    <key name="status"/>
    <key name="createdAt" order="desc"/>
</index>
```

---

### ❌ Anti-Pattern 3: Indexing Low-Cardinality Fields

**Problem**: Index on boolean or enum with few values

```xml
<!-- ❌ BAD: Only 2 possible values -->
<index><key name="isActive"/></index>
```

**Solution**: Use compound index with more selective field first

---

### ❌ Anti-Pattern 4: Duplicate Indexes

**Problem**: Multiple similar indexes

```xml
<!-- ❌ BAD: Second index is redundant -->
<index><key name="status"/></index>
<index><key name="status"/><key name="type"/></index>
```

**Solution**: Keep only compound index (covers both cases due to prefix rule)

---

## External Resources

- **[MongoDB Index Types](https://docs.mongodb.com/manual/indexes/#index-types)**
- **[Compound Index Performance](https://docs.mongodb.com/manual/core/index-compound/)**
- **[ESR Rule (Equality, Sort, Range)](https://docs.mongodb.com/manual/tutorial/equality-sort-range-rule/)**

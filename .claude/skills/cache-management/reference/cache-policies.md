# Cache Policies Reference

Complete guide for declaring cache policies: cache keys, TTLs, consistency classes, and invalidation strategies.

## Cache Policy Declaration Template

Before implementing caching, declare the complete policy:

```php
/**
 * Cache Policy for {Operation/Query}
 *
 * Key Pattern: {namespace}.{identifier}
 * TTL: {duration} ({reason})
 * Consistency: {Strong|Eventual|SWR}
 * Invalidation: {trigger conditions}
 * Tags: [{tag1}, {tag2}]
 * Notes: {additional considerations}
 */
```

**Example**:

```php
/**
 * Cache Policy for Customer By ID Query
 *
 * Key Pattern: customer.{id}
 * TTL: 300s (5 minutes - balance between freshness and performance)
 * Consistency: Stale-While-Revalidate
 * Invalidation: On customer update/delete commands
 * Tags: [customer, customer.{id}]
 * Notes: Read-heavy operation, tolerates brief staleness
 */
public function findById(string $id): ?Customer
{
    // Implementation...
}
```

---

## Cache Key Design

### Key Naming Pattern

**Format**: `{namespace}.{entity}.{identifier}.{variation}`

**Components**:
- **Namespace**: Domain/module (e.g., `customer`, `order`, `product`)
- **Entity**: Specific entity type (optional if namespace is entity)
- **Identifier**: Unique ID, filter, or query parameter
- **Variation**: Optional version, locale, or variant

**Examples**:

```php
// Single entity by ID
'customer.{id}' => 'customer.abc123'

// List queries
'customer.list.active' => 'customer.list.active'
'customer.list.page.{page}' => 'customer.list.page.1'

// Filtered queries
'order.by_customer.{customerId}' => 'order.by_customer.abc123'
'product.category.{categoryId}.active' => 'product.category.electronics.active'

// Aggregations
'stats.customer.count.{date}' => 'stats.customer.count.2024-12-10'
'metrics.revenue.daily.{date}' => 'metrics.revenue.daily.2024-12-10'

// Versioned keys (when cache structure changes)
'customer.v2.{id}' => 'customer.v2.abc123'
```

### Key Design Best Practices

**✅ DO**:
- Use lowercase with dots/underscores
- Include namespace to avoid collisions
- Keep keys short but descriptive
- Use consistent patterns across codebase
- Include version if structure might change
- Make keys predictable and debuggable

**❌ DON'T**:
- Use special characters (!, @, #, $, %, etc.)
- Include sensitive data in keys
- Create extremely long keys (>100 chars)
- Use dynamic/unpredictable patterns
- Mix naming conventions

---

## TTL Selection Guide

### TTL Decision Matrix

| Data Freshness Requirement | TTL Range     | Use Cases                          |
| -------------------------- | ------------- | ---------------------------------- |
| **Real-time**              | No cache      | Live notifications, stock prices   |
| **Near real-time**         | 1-10 seconds  | Live dashboards, active sessions   |
| **Fresh**                  | 30-60 seconds | Search results, recommendations    |
| **Moderately fresh**       | 5-15 minutes  | User profiles, product details     |
| **Stable**                 | 1-6 hours     | Product catalogs, category lists   |
| **Static**                 | 1-7 days      | Configuration, rarely-changed data |

### TTL Calculation Factors

**Consider these factors when choosing TTL**:

1. **Data change frequency**
   - Frequently updated → Shorter TTL
   - Rarely updated → Longer TTL

2. **Business impact of stale data**
   - High impact (prices, inventory) → Shorter TTL or invalidation
   - Low impact (descriptions, images) → Longer TTL

3. **Query cost**
   - Expensive queries → Longer TTL with invalidation
   - Cheap queries → Shorter TTL or no cache

4. **Traffic patterns**
   - High traffic → Longer TTL to reduce load
   - Low traffic → Shorter TTL acceptable

### TTL Examples by Entity Type

```php
// User profile (updated occasionally)
$item->expiresAfter(600); // 10 minutes

// Product catalog (updated rarely)
$item->expiresAfter(3600); // 1 hour

// Search results (can be slightly stale)
$item->expiresAfter(60); // 1 minute

// Configuration (very stable)
$item->expiresAfter(86400); // 24 hours

// Session data (security-sensitive)
$item->expiresAfter(1800); // 30 minutes

// Aggregated statistics (computed overnight)
$item->expiresAfter(43200); // 12 hours
```

---

## Consistency Classes

### 1. Strong Consistency (No Cache)

**When to use**:
- Real-time data required
- Security-sensitive operations
- Financial transactions
- Inventory management

**Implementation**:

```php
// Don't cache - always read from database
public function getCurrentBalance(string $userId): Money
{
    // NO caching - always fresh from DB
    return $this->dm->find(Account::class, $userId)->balance();
}
```

### 2. Eventual Consistency

**When to use**:
- Read-heavy operations
- Tolerate brief staleness
- Non-critical data

**Implementation**:

```php
/**
 * Cache Policy:
 * Consistency: Eventual (TTL-based)
 * TTL: 300s
 */
public function findById(string $id): ?Customer
{
    return $this->cache->get(
        "customer.{$id}",
        function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);
            return $this->loadFromDatabase($id);
        }
    );
}
```

### 3. Stale-While-Revalidate (SWR)

**When to use**:
- High traffic queries
- Tolerate stale data briefly
- Want fast responses + fresh data

**Implementation**:

```php
/**
 * Cache Policy:
 * Consistency: Stale-While-Revalidate
 * TTL: 300s (fresh)
 * SWR Window: 60s (stale but revalidating)
 */
public function findById(string $id): ?Customer
{
    return $this->cache->get(
        "customer.{$id}",
        function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);
            // Probabilistic early expiration for background refresh
            return $this->loadFromDatabase($id);
        },
        beta: 1.0 // Enable probabilistic early revalidation
    );
}
```

**See**: [swr-pattern.md](swr-pattern.md) for complete SWR implementation

---

## Cache Tags for Invalidation

### Tag Design Patterns

**Use hierarchical tags for flexible invalidation**:

```php
// Single entity - multiple tags
$item->tag([
    'customer',           // Invalidate ALL customers
    "customer.{$id}",     // Invalidate SPECIFIC customer
]);

// List queries - broader tags
$item->tag([
    'customer',           // All customer data
    'customer.list',      // All customer lists
    'customer.list.active', // Specific list variant
]);

// Related entities
$item->tag([
    'order',
    "order.{$orderId}",
    "customer.{$customerId}", // Also invalidate when customer changes
]);
```

### Tag Invalidation Strategies

**1. Single Entity Invalidation**:

```php
// When updating a specific customer
$this->cache->invalidateTags(["customer.{$id}"]);
```

**2. Batch Invalidation**:

```php
// When customer data structure changes
$this->cache->invalidateTags(['customer']);
```

**3. Related Entity Invalidation**:

```php
// When order is created, invalidate customer's order list
$this->cache->invalidateTags([
    "order.{$orderId}",
    "customer.{$customerId}.orders",
]);
```

---

## Complete Policy Examples

### Example 1: Customer Profile

```php
/**
 * Cache Policy for Customer Profile Query
 *
 * Key Pattern: customer.{id}
 * TTL: 600s (10 minutes)
 * Consistency: Stale-While-Revalidate
 * Invalidation: On UpdateCustomer/DeleteCustomer commands
 * Tags: [customer, customer.{id}]
 * Notes:
 *  - Read-heavy operation (10:1 read:write ratio)
 *  - Tolerates brief staleness for profile data
 *  - SWR ensures fast response while keeping fresh
 */
public function findById(string $id): ?Customer
{
    return $this->cache->get(
        "customer.{$id}",
        function (ItemInterface $item) use ($id) {
            $item->expiresAfter(600);
            $item->tag(['customer', "customer.{$id}"]);

            $this->logger->debug('Cache miss - loading customer', [
                'customer_id' => $id,
            ]);

            return $this->dm->find(Customer::class, $id);
        },
        beta: 1.0
    );
}
```

### Example 2: Product Catalog

```php
/**
 * Cache Policy for Active Products List
 *
 * Key Pattern: product.list.active.page.{page}
 * TTL: 3600s (1 hour)
 * Consistency: Eventual
 * Invalidation: On product create/update/delete, manual on catalog import
 * Tags: [product, product.list, product.list.active]
 * Notes:
 *  - Product catalog changes infrequently
 *  - High cache hit rate expected
 *  - Invalidate all on bulk import
 */
public function findActiveProducts(int $page = 1): array
{
    $cacheKey = "product.list.active.page.{$page}";

    return $this->cache->get($cacheKey, function (ItemInterface $item) use ($page) {
        $item->expiresAfter(3600);
        $item->tag(['product', 'product.list', 'product.list.active']);

        return $this->queryActiveProducts($page);
    });
}
```

### Example 3: Aggregated Statistics

```php
/**
 * Cache Policy for Daily Revenue Stats
 *
 * Key Pattern: stats.revenue.daily.{date}
 * TTL: 43200s (12 hours)
 * Consistency: Eventual
 * Invalidation: Time-based only (historical data doesn't change)
 * Tags: [stats, stats.revenue]
 * Notes:
 *  - Historical stats don't change after day ends
 *  - Expensive aggregation query
 *  - Can use longer TTL for past dates
 */
public function getDailyRevenue(string $date): Money
{
    $cacheKey = "stats.revenue.daily.{$date}";
    $ttl = $this->calculateTtl($date);

    return $this->cache->get($cacheKey, function (ItemInterface $item) use ($date, $ttl) {
        $item->expiresAfter($ttl);
        $item->tag(['stats', 'stats.revenue']);

        return $this->calculateRevenueForDate($date);
    });
}

private function calculateTtl(string $date): int
{
    $queryDate = new \DateTimeImmutable($date);
    $now = new \DateTimeImmutable();

    // Past dates: cache for 7 days (data won't change)
    if ($queryDate < $now->modify('midnight')) {
        return 86400 * 7;
    }

    // Today: cache for 1 hour (still changing)
    return 3600;
}
```

---

## Policy Selection Checklist

Before implementing cache, answer these questions:

1. **What is the query cost?**
   - High cost → Consider caching
   - Low cost → May not need cache

2. **How frequently does data change?**
   - Rarely → Longer TTL
   - Frequently → Shorter TTL or no cache

3. **What's the business impact of stale data?**
   - High impact → Strong consistency or short TTL
   - Low impact → Eventual consistency or SWR

4. **What's the read:write ratio?**
   - High (>10:1) → Great candidate for caching
   - Low (<3:1) → Cache invalidation overhead may outweigh benefits

5. **Can invalidation be triggered explicitly?**
   - Yes → Use invalidation + longer TTL
   - No → Use shorter TTL

6. **What's the expected traffic pattern?**
   - High traffic → Caching essential
   - Low traffic → Cache may not be beneficial

---

## Advanced Patterns

### Conditional Caching

**Cache based on query parameters**:

```php
public function search(SearchCriteria $criteria): array
{
    // Only cache simple searches
    if ($criteria->isComplex()) {
        return $this->executeSearch($criteria);
    }

    $cacheKey = $this->buildSearchCacheKey($criteria);

    return $this->cache->get($cacheKey, function (ItemInterface $item) use ($criteria) {
        $item->expiresAfter(60); // Short TTL for search
        $item->tag(['search', "search.{$criteria->category()}"]);

        return $this->executeSearch($criteria);
    });
}
```

### Tiered TTL

**Different TTL based on data state**:

```php
public function findById(string $id): ?Customer
{
    $customer = $this->loadFromCache($id);

    // Active customers: 5 min TTL
    // Inactive customers: 1 hour TTL (change less frequently)
    $ttl = $customer->isActive() ? 300 : 3600;

    // Re-cache with appropriate TTL
    // (This pattern is advanced - usually keep TTL consistent)
}
```

### Cache Warming

**Pre-populate cache for known queries**:

```php
public function warmCache(): void
{
    $this->logger->info('Starting cache warmup');

    // Warm frequently accessed customers
    $topCustomerIds = $this->getTopCustomerIds();

    foreach ($topCustomerIds as $customerId) {
        $this->findById($customerId); // Populates cache
    }

    $this->logger->info('Cache warmup completed', [
        'warmed_count' => count($topCustomerIds),
    ]);
}
```

---

## Summary

**Key Takeaways**:

1. Always declare cache policy before implementing
2. Choose TTL based on data freshness requirements and query cost
3. Use cache tags for flexible invalidation
4. Select consistency class based on business requirements
5. Document policy decisions for maintainability

**Policy Declaration is MANDATORY** - Every cached query must have:
- ✅ Explicit cache key pattern
- ✅ Defined TTL with rationale
- ✅ Declared consistency class
- ✅ Documented invalidation strategy
- ✅ Configured cache tags

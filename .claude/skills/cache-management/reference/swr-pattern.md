# Stale-While-Revalidate (SWR) Pattern

Complete guide for implementing stale-while-revalidate caching pattern for high-traffic, read-heavy queries that tolerate brief staleness.

## ⚠️ Interface Requirements

**CRITICAL**: SWR implementation depends on which cache interface you use:

| Interface                     | Methods Available                                      | Use Case                                 |
| ----------------------------- | ------------------------------------------------------ | ---------------------------------------- |
| `TagAwareCacheInterface`      | `get()`, `invalidateTags()`, `beta` parameter          | **Recommended**: Simple SWR with tags    |
| `TagAwareAdapterInterface`    | `getItem()`, `save()`, `isHit()`, custom metadata      | Advanced: Custom SWR logic               |

**For most cases, use `TagAwareCacheInterface` with the `beta` parameter.** See examples below.

## What is SWR?

**Stale-While-Revalidate** is a caching strategy that:

1. Serves **cached (potentially stale) data immediately** for fast responses
2. **Revalidates in the background** to refresh the cache
3. Returns **fresh data on subsequent requests**

**Benefits**:
- Fast response times (always serves from cache if available)
- Fresh data (background refresh keeps cache up-to-date)
- Reduced database load (background refresh, not on every request)
- Better user experience (no waiting for refresh)

**Trade-off**:
- Users may see stale data briefly between TTL expiration and background refresh

---

## When to Use SWR

**Ideal for**:
- High-traffic, read-heavy endpoints (100+ req/sec)
- Data that tolerates brief staleness (user profiles, product catalogs)
- Expensive queries that benefit from caching
- Scenarios where consistency isn't critical

**NOT recommended for**:
- Financial transactions or inventory
- Real-time data requirements
- Security-sensitive operations
- Low-traffic endpoints (overhead not justified)

---

## SWR Implementation in Symfony

Symfony Cache supports SWR through the `beta` parameter (probabilistic early expiration).

### Basic SWR Pattern

```php
public function findById(string $id): ?Customer
{
    return $this->cache->get(
        "customer.{$id}",
        function (ItemInterface $item) use ($id) {
            // Primary TTL: 300 seconds (5 minutes)
            $item->expiresAfter(300);
            $item->tag(['customer', "customer.{$id}"]);

            $this->logger->debug('Cache miss - loading customer', [
                'customer_id' => $id,
            ]);

            return $this->loadCustomerFromDatabase($id);
        },
        // Beta: Probabilistic early expiration for background refresh
        // Value between 0 and INF (typically 1.0)
        // Higher values = more likely to recompute before expiration
        beta: 1.0
    );
}
```

**How `beta` works**:

- `beta = 0`: No early expiration (standard caching)
- `beta = 1.0`: **Recommended** - Balanced probabilistic refresh
- `beta > 1.0`: Higher chance of early refresh (more aggressive)

The formula: If `(TTL - age) < beta * log(random())`, refresh in background

---

## Recommended SWR Implementation

### Simple SWR with Beta Parameter (Recommended)

**For most use cases**, use `TagAwareCacheInterface` with the `beta` parameter. This leverages Symfony's built-in probabilistic early expiration:

```php
final readonly class CustomerRepository
{
    public function __construct(
        private DocumentManager $dm,
        private TagAwareCacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    public function findByIdWithSwr(string $id): ?Customer
    {
        $cacheKey = "customer.{$id}";

        try {
            return $this->cache->get(
                $cacheKey,
                function (ItemInterface $item) use ($id, $cacheKey) {
                    $item->expiresAfter(300); // TTL: 5 minutes
                    $item->tag(['customer', "customer.{$id}"]);

                    $this->logger->info('Cache miss - loading customer from database', [
                        'cache_key' => $cacheKey,
                        'customer_id' => $id,
                    ]);

                    return $this->dm->find(Customer::class, $id);
                },
                beta: 1.0 // Enable probabilistic early expiration for SWR
            );

        } catch (\Exception $e) {
            $this->logger->error('Cache error - falling back to database', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);

            // Fallback to database on cache error
            return $this->dm->find(Customer::class, $id);
        }
    }
}
```

**Pros**:
- Simple, uses standard `TagAwareCacheInterface`
- Built-in Symfony support for probabilistic refresh
- Works with cache tags for invalidation
- No need for PSR-6 adapter injection

**Cons**:
- Less control over SWR timing
- No custom metadata tracking
- Refresh happens inline (not truly async)

---

### Advanced SWR with PSR-6 Adapter (For Custom Control)

**For advanced use cases** where you need fine-grained control over SWR behavior with custom metadata tracking, inject the PSR-6 `TagAwareAdapterInterface` directly:

**Important**: This requires `getItem()`, `save()` methods from PSR-6, not available in `TagAwareCacheInterface`.

```php
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Psr\Cache\CacheItemInterface;

final readonly class CustomerRepository
{
    public function __construct(
        private DocumentManager $dm,
        private TagAwareAdapterInterface $cacheAdapter, // PSR-6 for getItem() support
        private LoggerInterface $logger,
        private MessageBusInterface $messageBus
    ) {}

    public function findByIdWithCustomSwr(string $id): ?Customer
    {
        $cacheKey = "customer.{$id}";

        try {
            $cacheItem = $this->cacheAdapter->getItem($cacheKey);

            if ($cacheItem->isHit()) {
                $customer = $cacheItem->get();

                // Check if cache is stale but within SWR window
                if ($this->isStaleButRevalidating($cacheItem)) {
                    // Serve stale data immediately
                    $this->logger->info('Serving stale data while revalidating', [
                        'cache_key' => $cacheKey,
                        'customer_id' => $id,
                    ]);

                    // Trigger background refresh
                    $this->triggerBackgroundRefresh($id);

                    return $customer;
                }

                // Cache is fresh - return it
                return $customer;
            }

            // Cache miss - load and cache
            return $this->loadAndCache($id, $cacheItem);

        } catch (\Exception $e) {
            $this->logger->error('Cache error - falling back to database', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
            ]);

            // Fallback to database on cache error
            return $this->dm->find(Customer::class, $id);
        }
    }

    private function isStaleButRevalidating(CacheItemInterface $item): bool
    {
        $metadata = $item->getMetadata();

        if (!isset($metadata['created_at'], $metadata['ttl'], $metadata['swr_window'])) {
            return false;
        }

        $age = time() - $metadata['created_at'];
        $ttl = $metadata['ttl'];
        $swrWindow = $metadata['swr_window'];

        // Stale if: age > TTL BUT age < (TTL + SWR window)
        return $age > $ttl && $age < ($ttl + $swrWindow);
    }

    private function loadAndCache(string $id, CacheItemInterface $item): ?Customer
    {
        $customer = $this->dm->find(Customer::class, $id);

        if ($customer === null) {
            return null;
        }

        // Cache with metadata for SWR
        $item->set($customer);
        $item->expiresAfter(300); // 5 minutes
        $item->tag(['customer', "customer.{$id}"]);

        // Store metadata for SWR logic
        if (method_exists($item, 'setMetadata')) {
            $item->setMetadata([
                'created_at' => time(),
                'ttl' => 300,
                'swr_window' => 60, // Serve stale for 60 seconds while refreshing
            ]);
        }

        $this->cacheAdapter->save($item);

        $this->logger->info('Customer loaded and cached', [
            'customer_id' => $id,
        ]);

        return $customer;
    }

    private function triggerBackgroundRefresh(string $id): void
    {
        // Dispatch async message for background refresh
        $this->messageBus->dispatch(new RefreshCustomerCacheMessage($id));

        $this->logger->debug('Background cache refresh triggered', [
            'customer_id' => $id,
        ]);
    }
}
```

**Pros**:
- Fine-grained control over SWR timing
- Custom metadata for staleness detection
- True background refresh via message bus
- Explicit control over refresh triggers

**Cons**:
- More complex implementation
- Requires PSR-6 adapter injection
- More code to maintain
- Need message bus setup for background jobs

### Background Refresh Message Handler

```php
final readonly class RefreshCustomerCacheHandler
{
    public function __construct(
        private DocumentManager $dm,
        private TagAwareCacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    public function __invoke(RefreshCustomerCacheMessage $message): void
    {
        $this->logger->info('Refreshing customer cache in background', [
            'customer_id' => $message->customerId,
        ]);

        // Load fresh data from database
        $customer = $this->dm->find(Customer::class, $message->customerId);

        if ($customer === null) {
            $this->logger->warning('Customer not found during cache refresh', [
                'customer_id' => $message->customerId,
            ]);
            return;
        }

        // Update cache using standard get() with callback
        $cacheKey = "customer.{$message->customerId}";
        
        $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($customer, $message) {
                $item->expiresAfter(300);
                $item->tag(['customer', "customer.{$message->customerId}"]);
                
                $this->logger->info('Customer cache refreshed successfully', [
                    'customer_id' => $message->customerId,
                ]);
                
                return $customer;
            }
        );
    }
}
```

---

## SWR Configuration Examples

### Example 1: User Profile (High Traffic)

```php
/**
 * Cache Policy for User Profile
 *
 * TTL: 600s (10 minutes) - fresh window
 * SWR Window: 120s (2 minutes) - stale-while-revalidate
 * Total: Up to 12 minutes (10 fresh + 2 stale)
 *
 * Reasoning:
 *  - High traffic (1000+ req/sec)
 *  - User profile changes infrequently
 *  - Tolerate 2-minute staleness during refresh
 */
public function getUserProfile(string $userId): UserProfile
{
    return $this->cache->get(
        "user.profile.{$userId}",
        fn(ItemInterface $item) => $this->loadUserProfile($userId, $item),
        beta: 1.0
    );
}

private function loadUserProfile(string $userId, ItemInterface $item): UserProfile
{
    $item->expiresAfter(600);
    $item->tag(['user', "user.{$userId}"]);

    return $this->dm->find(UserProfile::class, $userId);
}
```

### Example 2: Product Catalog (Medium Traffic)

```php
/**
 * Cache Policy for Product Catalog
 *
 * TTL: 3600s (1 hour) - fresh window
 * SWR Window: 300s (5 minutes) - stale-while-revalidate
 * Total: Up to 65 minutes (60 fresh + 5 stale)
 *
 * Reasoning:
 *  - Medium traffic (100 req/sec)
 *  - Product data changes hourly
 *  - Tolerate 5-minute staleness acceptable
 */
public function getProductCatalog(string $category): array
{
    return $this->cache->get(
        "product.catalog.{$category}",
        fn(ItemInterface $item) => $this->loadProductCatalog($category, $item),
        beta: 1.0
    );
}
```

### Example 3: Search Results (Low Staleness Tolerance)

```php
/**
 * Cache Policy for Search Results
 *
 * TTL: 60s (1 minute) - fresh window
 * SWR Window: 10s - stale-while-revalidate
 * Total: Up to 70 seconds (60 fresh + 10 stale)
 *
 * Reasoning:
 *  - Search results should be relatively fresh
 *  - Short SWR window (10s) for minimal staleness
 *  - High traffic justifies SWR overhead
 */
public function search(SearchQuery $query): SearchResults
{
    $cacheKey = $this->buildSearchCacheKey($query);

    return $this->cache->get(
        $cacheKey,
        fn(ItemInterface $item) => $this->executeSearch($query, $item),
        beta: 1.0
    );
}
```

---

## SWR vs Standard Caching

### Standard Caching (No SWR)

```
Request → Cache Hit? → Yes → Return cached data ✓
                    ↓
                    No → Load from DB → Cache → Return ✗ (slow)

After TTL expires:
Request → Cache Miss → Load from DB → Cache → Return ✗ (slow)
```

**Problem**: Every request after TTL expiration experiences slow response

### SWR Caching

```
Request → Cache Hit (fresh)? → Yes → Return cached data ✓ (fast)
                            ↓
                            No (but stale)? → Return stale data ✓ (fast)
                                           → Trigger background refresh
                            ↓
                            No (expired) → Load from DB → Cache → Return ✗ (slow, first time only)

Subsequent requests:
Request → Cache Hit (fresh) → Return fresh data ✓ (fast)
```

**Benefit**: Only the background refresh is slow; users always get fast responses

---

## Testing SWR Behavior

### Test 1: Serve Fresh Data

```php
public function testServeFreshDataWithinTtl(): void
{
    $customer = $this->createTestCustomer();

    // First request - cache miss
    $result1 = $this->repository->findById($customer->id());
    self::assertSame('John Doe', $result1->name());

    // Second request within TTL - cache hit (fresh)
    $result2 = $this->repository->findById($customer->id());
    self::assertSame('John Doe', $result2->name());
}
```

### Test 2: Serve Stale Data While Revalidating

```php
public function testServeStaleDataDuringRevalidation(): void
{
    $customer = $this->createTestCustomer();

    // Cache customer
    $result1 = $this->repository->findByIdWithSwr($customer->id());
    self::assertSame('John Doe', $result1->name());

    // Wait for TTL to expire (but within SWR window)
    sleep(6); // TTL=5s, SWR window=10s

    // Update customer directly in DB (bypass repository)
    $this->updateCustomerNameDirectly($customer->id(), 'Jane Doe');

    // Should serve stale data immediately
    $result2 = $this->repository->findByIdWithSwr($customer->id());
    self::assertSame('John Doe', $result2->name()); // Still stale!

    // Background refresh should have triggered
    // Wait for background job to complete
    sleep(2);

    // Next request should have fresh data
    $result3 = $this->repository->findByIdWithSwr($customer->id());
    self::assertSame('Jane Doe', $result3->name()); // Now fresh!
}
```

### Test 3: Fallback After SWR Window Expires

```php
public function testLoadFromDatabaseAfterSwrWindowExpires(): void
{
    $customer = $this->createTestCustomer();

    // Cache customer
    $this->repository->findByIdWithSwr($customer->id());

    // Wait for both TTL and SWR window to expire
    sleep(16); // TTL=5s, SWR=10s, total=15s

    // Update customer in DB
    $this->updateCustomerNameDirectly($customer->id(), 'Jane Doe');

    // Should load from database (cache completely expired)
    $result = $this->repository->findByIdWithSwr($customer->id());
    self::assertSame('Jane Doe', $result->name());
}
```

---

## SWR Performance Characteristics

### Response Time Distribution

**Standard Caching**:
```
|------------- TTL (5 min) -------------|
↓                                        ↓
Fast (0-5ms) ........................... Slow (50-100ms) [DB load]
↑                                        ↑
Cache hit                                Cache miss
```

**SWR Caching**:
```
|-- TTL (5 min) --|-- SWR (1 min) --|
↓                 ↓                 ↓
Fast ............ Fast ........... Slow (first request only)
↑                 ↑                 ↑
Fresh cache       Stale cache       Expired
                  (refresh in bg)
```

### Metrics to Track

```php
// Track SWR effectiveness
$this->metrics->increment('cache.hit', ['type' => 'fresh']);
$this->metrics->increment('cache.hit', ['type' => 'stale']);
$this->metrics->increment('cache.miss');
$this->metrics->increment('cache.refresh.background');
```

---

## SWR Best Practices

### ✅ DO

- Use SWR for high-traffic, read-heavy endpoints
- Set reasonable SWR window (10-20% of TTL)
- Monitor background refresh success rate
- Test stale data scenarios thoroughly
- Log when serving stale data
- Use async background refresh (message bus)
- Combine with cache tags for invalidation

### ❌ DON'T

- Use SWR for financial/critical data
- Set SWR window too long (staleness issues)
- Use SWR for low-traffic endpoints (overhead not justified)
- Forget to test background refresh failures
- Rely solely on SWR (still use explicit invalidation)

---

## Combining SWR with Explicit Invalidation

**Best practice**: Use SWR for performance + explicit invalidation for consistency

```php
// Read with SWR (fast, tolerates brief staleness)
public function findById(string $id): ?Customer
{
    return $this->cache->get(
        "customer.{$id}",
        fn($item) => $this->loadCustomer($id, $item),
        beta: 1.0 // SWR enabled
    );
}

// Write with explicit invalidation (strong consistency)
public function save(Customer $customer): void
{
    $this->dm->persist($customer);
    $this->dm->flush();

    // Explicit invalidation overrides SWR
    $this->cache->invalidateTags(["customer.{$customer->id()}"]);

    $this->logger->info('Customer saved and cache invalidated', [
        'customer_id' => $customer->id(),
    ]);
}
```

**Result**:
- **Reads**: Fast (SWR), with background refresh
- **Writes**: Immediate cache invalidation (strong consistency)

---

## Summary

**SWR Pattern Checklist**:
- ✅ Declare SWR in cache policy
- ✅ Set appropriate TTL and SWR window
- ✅ Implement background refresh mechanism
- ✅ Test stale data serving
- ✅ Test background refresh triggers
- ✅ Monitor cache hit/stale/miss rates
- ✅ Log SWR events for debugging
- ✅ Combine with explicit invalidation for writes

**When to Use SWR**:
- High traffic (>100 req/sec)
- Tolerate brief staleness
- Expensive queries
- Read-heavy operations

**When NOT to Use SWR**:
- Financial/critical data
- Real-time requirements
- Low traffic
- Security-sensitive operations

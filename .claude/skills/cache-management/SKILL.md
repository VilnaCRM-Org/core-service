---
name: cache-management
description: Implement production-grade caching with cache keys/TTLs/consistency classes per query, SWR (stale-while-revalidate), explicit invalidation, and comprehensive testing for stale reads and cache warmup. Use when adding caching to queries, implementing cache invalidation, or ensuring cache consistency and performance.
---

# Cache Management Skill

## Context (Input)

Use this skill when:

- Adding caching to expensive queries or API endpoints
- Implementing cache invalidation strategies
- Defining cache keys, TTLs, and consistency requirements
- Implementing stale-while-revalidate (SWR) pattern
- Testing cache behavior (stale reads, cold start warmup)
- Optimizing read-heavy operations
- Reducing database load with caching
- Debugging cache-related issues (stale data, cache misses)

## Task (Function)

Implement production-ready caching with proper key design, TTL management, consistency guarantees, invalidation strategies, and comprehensive testing.

**Success Criteria**:

- Cache policy declared for each query (key, TTL, consistency class)
- Read-through caching implemented with Symfony Cache
- Explicit invalidation on all write operations (create, update, delete)
- Cache tags configured for batch invalidation
- Comprehensive tests added (stale reads, cold start, TTL expiration)
- Cache observability added (logs, metrics)
- `make ci` outputs "✅ CI checks successfully passed!"

---

## ⚠️ CRITICAL CACHE POLICY

```
╔═══════════════════════════════════════════════════════════════╗
║  ALWAYS use Decorator Pattern for caching (wrap repositories) ║
║  ALWAYS use CacheKeyBuilder service (prevent key drift)       ║
║  ALWAYS invalidate via Domain Events (decouple from business) ║
║  ALWAYS use TagAwareCacheInterface for cache tags             ║
║  ALWAYS wrap invalidation in try/catch (best-effort)          ║
║                                                               ║
║  ❌ FORBIDDEN: Caching in repository, implicit invalidation   ║
║  ✅ REQUIRED:  Decorator pattern, event-driven invalidation   ║
╚═══════════════════════════════════════════════════════════════╝
```

**Non-negotiable requirements**:

- Use Decorator Pattern: `CachedXxxRepository` wraps `MongoXxxRepository`
- Use centralized `CacheKeyBuilder` service (in `Shared/Infrastructure/Cache`)
- Invalidate via Domain Event Subscribers (not in repository)
- Wrap cache operations in try/catch (never fail business operations)
- Use `TagAwareCacheInterface` (not `CacheInterface`) for tag support
- Configure test cache pools with `tags: true` in `config/packages/test/cache.yaml`
- Log cache operations for observability

---

## TL;DR - Cache Management Checklist

**Before Implementing Cache:**

- [ ] Identified slow query worth caching (use query-performance-analysis)
- [ ] Cache policy declared (key pattern, TTL, consistency class)
- [ ] Cache tags defined for invalidation strategy
- [ ] Domain events defined for cache invalidation triggers

**Architecture Setup:**

- [ ] Created `CachedXxxRepository` decorator class
- [ ] Created `CacheKeyBuilder` service (or extended existing one)
- [ ] Created cache invalidation event subscribers (one per event)
- [ ] Configured services.yaml with explicit cache pool injection

**During Implementation:**

- [ ] Decorator wraps inner repository (not extends)
- [ ] CacheKeyBuilder used for all cache keys (prevents drift)
- [ ] Cache operations wrapped in try/catch (best-effort)
- [ ] Event subscribers use same CacheKeyBuilder for tags
- [ ] Logging added for cache hits/misses/errors
- [ ] Repository uses `TagAwareCacheInterface` (required for tags)

**Testing:**

- [ ] Test cache pool configured with `tags: true`
- [ ] Unit tests for cache invalidation subscribers
- [ ] Integration tests for stale reads after writes
- [ ] Test: Cache error fallback to database works

**Before Merge:**

- [ ] All cache tests pass
- [ ] Cache observability verified (logs present)
- [ ] CI checks pass (`make ci`)
- [ ] No cache-related stale data issues

---

## Quick Start: Cache in 7 Steps

### Step 1: Declare Cache Policy

**Before writing code, declare the complete policy:**

```php
/**
 * Cache Policy for Customer By ID Query
 *
 * Key Pattern: customer.{id}
 * TTL: 600s (10 minutes)
 * Consistency: Stale-While-Revalidate
 * Invalidation: Via domain events (CustomerCreated/Updated/Deleted)
 * Tags: [customer, customer.{id}]
 * Notes: Read-heavy operation, tolerates brief staleness
 */
```

### Step 2: Create CacheKeyBuilder Service

**Location**: `src/Shared/Infrastructure/Cache/CacheKeyBuilder.php`

```php
final readonly class CacheKeyBuilder
{
    public function buildCustomerKey(string $customerId): string
    {
        return $this->build('customer', $customerId);
    }

    public function buildCustomerEmailKey(string $email): string
    {
        return $this->build('customer', 'email', $this->hashEmail($email));
    }

    public function hashEmail(string $email): string
    {
        return hash('sha256', strtolower($email));
    }

    private function build(string $namespace, string ...$parts): string
    {
        return $namespace . '.' . implode('.', $parts);
    }
}
```

### Step 3: Create Cached Repository Decorator

**Location**: `src/Core/{Entity}/Infrastructure/Repository/Cached{Entity}Repository.php`

```php
final class CachedCustomerRepository implements CustomerRepositoryInterface
{
    public function __construct(
        private CustomerRepositoryInterface $inner,  // Wraps MongoCustomerRepository
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger
    ) {}

    public function find(mixed $id, ...): ?Customer
    {
        $cacheKey = $this->cacheKeyBuilder->buildCustomerKey((string) $id);

        try {
            return $this->cache->get(
                $cacheKey,
                fn (ItemInterface $item) => $this->loadFromDb($id, $item),
                beta: 1.0  // Enable SWR
            );
        } catch (\Throwable $e) {
            $this->logCacheError($cacheKey, $e);
            return $this->inner->find($id, ...);  // Graceful fallback
        }
    }
}
```

### Step 4: Create Event Subscribers for Invalidation

**Location**: `src/Core/{Entity}/Application/EventSubscriber/{Event}CacheInvalidationSubscriber.php`

```php
final readonly class CustomerUpdatedCacheInvalidationSubscriber implements DomainEventSubscriberInterface
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private CacheKeyBuilder $cacheKeyBuilder,
        private LoggerInterface $logger
    ) {}

    public function __invoke(CustomerUpdatedEvent $event): void
    {
        // Best-effort: don't fail business operation if cache is down
        try {
            $this->cache->invalidateTags([
                'customer.' . $event->customerId(),
                'customer.email.' . $this->cacheKeyBuilder->hashEmail($event->currentEmail()),
                'customer.collection',
            ]);
            $this->logger->info('Cache invalidated after customer update', [...]);
        } catch (\Throwable $e) {
            $this->logger->error('Cache invalidation failed', [...]);
        }
    }
}
```

### Step 5: Configure services.yaml

```yaml
# Base repository - used by API Platform
App\Core\Customer\Infrastructure\Repository\MongoCustomerRepository:
  public: true

# Cached decorator - wraps base repository
App\Core\Customer\Infrastructure\Repository\CachedCustomerRepository:
  arguments:
    $inner: '@App\Core\Customer\Infrastructure\Repository\MongoCustomerRepository'
    $cache: '@cache.customer'

# Alias interface to cached repository
App\Core\Customer\Domain\Repository\CustomerRepositoryInterface:
  alias: App\Core\Customer\Infrastructure\Repository\CachedCustomerRepository

# Event subscribers - explicit cache pool injection
App\Core\Customer\Application\EventSubscriber\CustomerUpdatedCacheInvalidationSubscriber:
  arguments:
    $cache: '@cache.customer'
```

### Step 6: Configure Test Cache Pool

**CRITICAL**: Test cache pools MUST have `tags: true` for TagAwareCacheInterface!

**Location**: `config/packages/test/cache.yaml`

```yaml
framework:
  cache:
    pools:
      cache.customer:
        adapter: cache.adapter.array
        tags: true  # REQUIRED for TagAwareCacheInterface in tests
```

### Step 7: Verify with CI

```bash
make ci
```

---

## The Three Pillars of Cache Management

### 1. Cache Policies (Keys, TTLs, Consistency)

**What**: Declare cache configuration before implementation

**Key Elements**:

- Cache key pattern (namespace + identifier)
- TTL (based on data freshness requirements)
- Consistency class (Strong, Eventual, SWR)
- Cache tags (for invalidation)

**Example Policy Decision Matrix**:

| Data Type       | TTL      | Consistency | Invalidation      |
| --------------- | -------- | ----------- | ----------------- |
| User profile    | 5-10 min | SWR         | On update/delete  |
| Product catalog | 1 hour   | SWR         | On product change |
| Configuration   | 1 day    | Strong      | Manual/deployment |
| Search results  | 1 min    | Eventual    | Time-based only   |

**See**: [reference/cache-policies.md](reference/cache-policies.md) for complete guide

### 2. Invalidation Strategies (Explicit, Never Implicit)

**What**: Explicit cache clearing on write operations

**Strategies**:

- **Write-through**: Invalidate immediately after writes
- **Tag-based**: Batch invalidation using cache tags
- **Event-driven**: Invalidate via domain events
- **Time-based**: TTL-only (for static data)

**Critical Rule**: ALWAYS invalidate explicitly on create/update/delete

```php
// ✅ CORRECT
$this->repository->save($customer);
$this->cache->invalidateTags(["customer.{$id}"]);

// ❌ WRONG - Missing invalidation
$this->repository->save($customer);
// Cache now stale until TTL expires!
```

**See**: [reference/invalidation-strategies.md](reference/invalidation-strategies.md)

### 3. Testing (Stale Reads, Cold Start, Invalidation)

**What**: Comprehensive test coverage for all cache behaviors

**Required Tests**:

- ✅ Stale reads after writes
- ✅ Cache warmup on cold start
- ✅ TTL expiration behavior
- ✅ Tag-based invalidation
- ✅ SWR background refresh (if applicable)

**See**: [examples/cache-testing.md](examples/cache-testing.md)

---

## Core Workflow

### Workflow: Adding Cache to Repository

**Step 1: Identify Query to Cache**

Use [query-performance-analysis](../query-performance-analysis/SKILL.md) to identify slow queries

**Step 2: Declare Cache Policy**

Document key pattern, TTL, consistency class, invalidation strategy, and tags

**Step 3: Inject TagAwareCacheInterface**

```php
use Symfony\Contracts\Cache\TagAwareCacheInterface;

public function __construct(
    private DocumentManager $dm,
    private TagAwareCacheInterface $cache,
    private LoggerInterface $logger
) {}
```

**CRITICAL**: You **MUST** use `TagAwareCacheInterface` (not `CacheInterface`) when using:

- `$item->tag([...])` - Tagging cache items
- `$cache->invalidateTags([...])` - Batch invalidation by tags

Plain `CacheInterface` does not provide these methods.

**Step 4: Implement Read-Through Caching**

Use `$cache->get($key, $callback)` pattern with TTL and tags

**Step 5: Add Explicit Invalidation**

Invalidate on all write operations (save, delete)

**Step 6: Add Cache Key Builder**

```php
private function buildCacheKey(string $prefix, string ...$parts): string
{
    return $prefix . '.' . implode('.', $parts);
}
```

**Step 7: Add Observability**

Log cache hits/misses, track metrics (hit rate, latency)

**Step 8: Implement Tests**

Test stale reads, cold start, TTL expiration, tag invalidation

**Step 9: Run CI**

```bash
make ci
```

---

## Stale-While-Revalidate (SWR) Pattern

**When to use**: High-traffic queries that tolerate brief staleness

**How it works**:

1. Serve cached data immediately (even if stale)
2. Refresh cache in background
3. Return fresh data on next request

**Implementation**:

```php
public function findById(string $id): ?Customer
{
    return $this->cache->get(
        "customer.{$id}",
        fn($item) => $this->loadFromDatabase($id, $item),
        beta: 1.0  // Enable probabilistic early expiration
    );
}
```

**See**: [reference/swr-pattern.md](reference/swr-pattern.md) for complete implementation with background refresh

---

## Integration with Hexagonal Architecture

### Domain Layer

- **NO caching** - Pure business logic
- Domain entities are cache-agnostic

### Application Layer (Command Handlers)

- **Invalidate cache** after successful commands
- Use domain events to trigger invalidation

### Infrastructure Layer (Repositories)

- **Implement caching** in repository methods
- Read-through cache pattern
- Explicit invalidation on writes

**Example**:

```php
// Application Layer
final readonly class UpdateCustomerCommandHandler
{
    public function __invoke(UpdateCustomerCommand $command): void
    {
        $customer = $this->repository->findById($command->id);
        $customer->updateName($command->name);
        $this->repository->save($customer);  // Invalidates cache
    }
}

// Infrastructure Layer
final class CustomerRepository
{
    public function __construct(
        private DocumentManager $dm,
        private TagAwareCacheInterface $cache  // MUST use TagAwareCacheInterface for tag() and invalidateTags()
    ) {}

    public function findById(string $id): ?Customer
    {
        return $this->cache->get(...);  // Read-through caching
    }

    public function save(Customer $customer): void
    {
        $this->dm->flush();
        $this->cache->invalidateTags(["customer.{$customer->id()}"]);
    }
}
```

---

## Cache Observability

**Log cache operations**:

```php
$this->logger->info('Cache miss - loading from database', [
    'cache_key' => $cacheKey,
    'customer_id' => $id,
    'operation' => 'cache.miss',
]);
```

**Track metrics**:

- Cache hit rate: `cache.hit.total / (cache.hit.total + cache.miss.total)`
- Cache miss rate: `cache.miss.total / total_requests`
- Cache operation latency: `cache.operation.duration_ms`
- Invalidation frequency: `cache.invalidation.total`

**See**: [observability-instrumentation](../observability-instrumentation/SKILL.md) for complete instrumentation patterns

---

## Common Pitfalls

### ❌ DON'T

- Don't cache without declaring policy first
- Don't cache without TTL
- Don't cache in Domain layer
- Don't use implicit invalidation
- Don't share cache keys between different queries
- Don't cache sensitive data (PII, passwords, tokens)
- Don't cache without testing stale reads
- Don't forget to log cache operations

### ✅ DO

- Declare complete cache policy before coding
- Use cache tags for flexible invalidation
- Test invalidation explicitly
- Use SWR for read-heavy, stale-tolerant data
- Invalidate on all writes (create, update, delete)
- Log all cache operations
- Monitor cache hit rate in production
- Add observability (logs, metrics)

---

## Integration with Other Skills

**Identify queries to cache**:

- [query-performance-analysis](../query-performance-analysis/SKILL.md) - Find slow queries

**Add observability**:

- [observability-instrumentation](../observability-instrumentation/SKILL.md) - Cache metrics and logs

**Test cache behavior**:

- [testing-workflow](../testing-workflow/SKILL.md) - Test framework guidance

**Architecture placement**:

- [implementing-ddd-architecture](../implementing-ddd-architecture/SKILL.md) - Layer separation

---

## Quick Reference

| Pattern                | Code Example                                    |
| ---------------------- | ----------------------------------------------- |
| **Read-through cache** | `$cache->get($key, fn($item) => $loadFromDb())` |
| **Set TTL**            | `$item->expiresAfter(300)` (seconds)            |
| **Set cache tag**      | `$item->tag(['entity', 'entity.id'])`           |
| **Invalidate by tag**  | `$cache->invalidateTags(['entity.id'])`         |
| **Clear all cache**    | `$cache->clear()`                               |
| **Build cache key**    | `"{prefix}.{id}"` (namespace + identifier)      |
| **Enable SWR**         | `$cache->get($key, $callback, beta: 1.0)`       |

---

## Additional Resources

### Reference Documentation

- **[Cache Policies](reference/cache-policies.md)** - TTL selection, consistency classes, policy matrix
- **[Invalidation Strategies](reference/invalidation-strategies.md)** - Write-through, tag-based, event-driven patterns
- **[SWR Pattern](reference/swr-pattern.md)** - Complete stale-while-revalidate implementation

### Complete Examples

- **[Cache Implementation](examples/cache-implementation.md)** - Full repository with caching, invalidation, observability
- **[Cache Testing](examples/cache-testing.md)** - Complete test suite for all cache behaviors

---

**For detailed implementation patterns, invalidation strategies, and test patterns → See supporting files in `reference/` and `examples/` directories.**

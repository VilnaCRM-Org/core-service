# Cache Invalidation Strategies

Complete guide for implementing explicit cache invalidation: write-through, event-driven, tag-based, and time-based strategies.

## Core Principle: Explicit Over Implicit

**ALWAYS invalidate cache explicitly on write operations.** Never rely on TTL alone for data that changes via write commands.

```php
// ✅ CORRECT: Explicit invalidation
public function save(Customer $customer): void
{
    $this->dm->persist($customer);
    $this->dm->flush();

    // Explicit invalidation after write
    $this->invalidateCustomerCache($customer->id());
}

// ❌ WRONG: Relying only on TTL
public function save(Customer $customer): void
{
    $this->dm->persist($customer);
    $this->dm->flush();
    // Missing invalidation - stale data until TTL expires!
}
```

---

## Invalidation Strategy Matrix

| Strategy           | When to Use                         | Complexity | Consistency    |
| ------------------ | ----------------------------------- | ---------- | -------------- |
| **Write-through**  | Single entity CRUD operations       | Low        | Strong         |
| **Tag-based**      | Batch invalidation, related data    | Low        | Strong         |
| **Event-driven**   | Complex domain events, decoupling   | Medium     | Strong         |
| **Time-based**     | Static data, aggregations           | Low        | Eventual       |
| **Manual**         | One-off operations, bulk imports    | Low        | User-triggered |
| **Lazy (TTL)**     | Acceptable staleness, low churn     | Very Low   | Eventual       |

---

## 1. Write-Through Invalidation

**Pattern**: Invalidate immediately after write operation

**Use when**:
- Creating, updating, or deleting entities
- Single entity operations
- Strong consistency required

### Implementation

```php
final class CustomerRepository
{
    public function __construct(
        private DocumentManager $dm,
        private TagAwareCacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    public function save(Customer $customer): void
    {
        $customerId = $customer->id();

        // Write to database
        $this->dm->persist($customer);
        $this->dm->flush();

        // Invalidate cache immediately
        $this->invalidateCustomerCache($customerId);

        $this->logger->info('Customer saved and cache invalidated', [
            'customer_id' => $customerId,
        ]);
    }

    public function delete(Customer $customer): void
    {
        $customerId = $customer->id();

        // Remove from database
        $this->dm->remove($customer);
        $this->dm->flush();

        // Invalidate cache
        $this->invalidateCustomerCache($customerId);

        $this->logger->info('Customer deleted and cache invalidated', [
            'customer_id' => $customerId,
        ]);
    }

    private function invalidateCustomerCache(string $customerId): void
    {
        $cacheKey = "customer.{$customerId}";

        $this->cache->delete($cacheKey);

        $this->logger->debug('Cache invalidated', [
            'cache_key' => $cacheKey,
        ]);
    }
}
```

**Advantages**:
- Simple and predictable
- Strong consistency guaranteed
- Easy to test

**Disadvantages**:
- Must invalidate every cache key manually
- Doesn't handle related entity caches

---

## 2. Tag-Based Invalidation

**Pattern**: Use cache tags to invalidate multiple related cache entries at once

**Use when**:
- Invalidating multiple cache entries
- Clearing all caches for an entity type
- Invalidating related data

### Implementation

**Set tags when caching**:

```php
public function findById(string $id): ?Customer
{
    return $this->cache->get(
        "customer.{$id}",
        function (ItemInterface $item) use ($id) {
            $item->expiresAfter(300);

            // Set multiple tags for flexible invalidation
            $item->tag([
                'customer',           // All customers
                "customer.{$id}",     // Specific customer
            ]);

            return $this->dm->find(Customer::class, $id);
        }
    );
}

public function findActiveCustomers(): array
{
    return $this->cache->get(
        'customer.list.active',
        function (ItemInterface $item) {
            $item->expiresAfter(600);

            // Tag with both general and specific tags
            $item->tag([
                'customer',           // All customers
                'customer.list',      // All customer lists
            ]);

            return $this->queryActiveCustomers();
        }
    );
}
```

**Invalidate by tags**:

```php
// Invalidate specific customer (only that customer's cache)
$this->cache->invalidateTags(["customer.{$customerId}"]);

// Invalidate all customer caches (individual + lists)
$this->cache->invalidateTags(['customer']);

// Invalidate only customer lists (not individual customers)
$this->cache->invalidateTags(['customer.list']);
```

### Tag Hierarchy Pattern

**Use hierarchical tags for granular control**:

```php
// Entity cache
$item->tag([
    'customer',                    // Level 1: All customers
    "customer.{$id}",              // Level 2: Specific customer
    "customer.{$id}.profile",      // Level 3: Customer's profile
]);

// List cache
$item->tag([
    'customer',                    // Level 1: All customers
    'customer.list',               // Level 2: All lists
    'customer.list.active',        // Level 3: Specific list type
    "customer.list.page.{$page}",  // Level 4: Specific page
]);

// Related entity cache
$item->tag([
    'order',                       // Primary entity
    "order.{$orderId}",            // Specific order
    "customer.{$customerId}",      // Related customer (cross-entity tag)
]);
```

**Invalidation scenarios**:

```php
// Scenario 1: Customer profile updated
// Invalidate only that customer's caches
$this->cache->invalidateTags(["customer.{$id}"]);

// Scenario 2: Customer data structure changed (migration)
// Invalidate ALL customer caches
$this->cache->invalidateTags(['customer']);

// Scenario 3: Customer list filters changed
// Invalidate all lists but keep individual customer caches
$this->cache->invalidateTags(['customer.list']);

// Scenario 4: Order created for customer
// Invalidate order cache and customer's order list
$this->cache->invalidateTags([
    "order.{$orderId}",
    "customer.{$customerId}.orders",
]);
```

---

## 3. Event-Driven Invalidation

**Pattern**: Invalidate cache in response to domain events

**Use when**:
- Decoupling cache invalidation from business logic
- Complex domain events with multiple side effects
- Invalidating across bounded contexts

### Implementation

**Step 1: Define domain event**:

```php
final readonly class CustomerUpdatedEvent
{
    public function __construct(
        public string $customerId,
        public array $changedFields,
    ) {}
}
```

**Step 2: Emit event in domain/application layer**:

```php
final readonly class UpdateCustomerCommandHandler
{
    public function __construct(
        private CustomerRepository $repository,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(UpdateCustomerCommand $command): void
    {
        $customer = $this->repository->findById($command->id);
        $customer->updateName($command->name);

        $this->repository->save($customer);

        // Emit domain event
        $this->eventDispatcher->dispatch(
            new CustomerUpdatedEvent(
                customerId: $customer->id(),
                changedFields: ['name']
            )
        );
    }
}
```

**Step 3: Create cache invalidation subscriber**:

```php
final readonly class CustomerCacheInvalidationSubscriber
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CustomerCreatedEvent::class => 'onCustomerCreated',
            CustomerUpdatedEvent::class => 'onCustomerUpdated',
            CustomerDeletedEvent::class => 'onCustomerDeleted',
        ];
    }

    public function onCustomerCreated(CustomerCreatedEvent $event): void
    {
        // Invalidate customer lists (new customer added)
        $this->cache->invalidateTags(['customer.list']);

        $this->logger->info('Cache invalidated after customer creation', [
            'customer_id' => $event->customerId,
        ]);
    }

    public function onCustomerUpdated(CustomerUpdatedEvent $event): void
    {
        // Invalidate specific customer
        $this->cache->invalidateTags(["customer.{$event->customerId}"]);

        // If certain fields changed, also invalidate lists
        if ($this->affectsListing($event->changedFields)) {
            $this->cache->invalidateTags(['customer.list']);
        }

        $this->logger->info('Cache invalidated after customer update', [
            'customer_id' => $event->customerId,
            'changed_fields' => $event->changedFields,
        ]);
    }

    public function onCustomerDeleted(CustomerDeletedEvent $event): void
    {
        // Invalidate customer and lists
        $this->cache->invalidateTags([
            "customer.{$event->customerId}",
            'customer.list',
        ]);

        $this->logger->info('Cache invalidated after customer deletion', [
            'customer_id' => $event->customerId,
        ]);
    }

    private function affectsListing(array $changedFields): bool
    {
        // Fields that affect list queries
        $listFields = ['status', 'category', 'name'];

        return !empty(array_intersect($changedFields, $listFields));
    }
}
```

**Advantages**:
- Decouples cache invalidation from business logic
- Easy to add new invalidation logic
- Supports complex invalidation rules
- Can invalidate across multiple repositories

**Disadvantages**:
- More complex than direct invalidation
- Harder to trace invalidation flow
- Event bus overhead

---

## 4. Time-Based Invalidation (TTL Only)

**Pattern**: Rely solely on TTL for cache expiration

**Use when**:
- Data changes infrequently
- Staleness is acceptable
- No write operations in your control
- Aggregated/computed data

### Implementation

```php
/**
 * Cache Policy:
 * Invalidation: Time-based ONLY (no explicit invalidation)
 * TTL: 3600s (1 hour)
 * Reason: Data changes externally (via import) or very rarely
 */
public function getDailyStatistics(string $date): array
{
    $cacheKey = "stats.daily.{$date}";

    return $this->cache->get($cacheKey, function (ItemInterface $item) use ($date) {
        // Historical data never changes - long TTL
        $isPastDate = (new \DateTimeImmutable($date)) < new \DateTimeImmutable('today');
        $ttl = $isPastDate ? 86400 * 7 : 3600; // 7 days for past, 1 hour for today

        $item->expiresAfter($ttl);
        $item->tag(['stats']);

        return $this->calculateStatistics($date);
    });
}
```

**Advantages**:
- Simple implementation
- No invalidation logic needed

**Disadvantages**:
- Stale data until TTL expires
- Can't force refresh
- Not suitable for frequently changing data

**When acceptable**:
- External data imports (invalidate manually when import completes)
- Historical/archival data (never changes)
- Aggregated statistics (recomputed on schedule)

---

## 5. Manual Invalidation

**Pattern**: Provide manual cache clearing commands/endpoints

**Use when**:
- Bulk imports or migrations
- Data structure changes
- Emergency cache clearing
- Development/debugging

### Implementation

**Console command**:

```php
#[AsCommand(
    name: 'cache:invalidate',
    description: 'Manually invalidate cache by tags'
)]
final class InvalidateCacheCommand extends Command
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('tags', InputArgument::IS_ARRAY, 'Cache tags to invalidate');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tags = $input->getArgument('tags');

        if (empty($tags)) {
            $output->writeln('<error>No tags provided</error>');
            return Command::FAILURE;
        }

        $this->cache->invalidateTags($tags);

        $this->logger->warning('Manual cache invalidation executed', [
            'tags' => $tags,
        ]);

        $output->writeln("<info>Invalidated cache tags: " . implode(', ', $tags) . "</info>");

        return Command::SUCCESS;
    }
}
```

**Usage**:

```bash
# Invalidate specific entity
php bin/console cache:invalidate customer.abc123

# Invalidate all customers
php bin/console cache:invalidate customer

# Invalidate multiple tags
php bin/console cache:invalidate customer order product
```

**Admin API endpoint** (use with caution):

```php
#[Route('/api/admin/cache/invalidate', methods: ['POST'])]
final class InvalidateCacheAction
{
    public function __construct(
        private TagAwareCacheInterface $cache,
        private LoggerInterface $logger
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $tags = $request->request->all('tags');

        if (empty($tags)) {
            return new JsonResponse(['error' => 'No tags provided'], 400);
        }

        $this->cache->invalidateTags($tags);

        $this->logger->warning('Cache invalidated via API', [
            'tags' => $tags,
            'user' => $this->getUser()?->getUsername(),
        ]);

        return new JsonResponse([
            'success' => true,
            'invalidated_tags' => $tags,
        ]);
    }
}
```

---

## Invalidation Anti-Patterns

### ❌ Implicit Invalidation

**Wrong**:

```php
// Bad: Clearing cache inside business logic without explicit reason
public function updateCustomer(Customer $customer): void
{
    $this->dm->flush();
    $this->cache->clear(); // Clears EVERYTHING!
}
```

**Right**:

```php
// Good: Explicit invalidation of specific cache entries
public function updateCustomer(Customer $customer): void
{
    $this->dm->flush();
    $this->cache->invalidateTags(["customer.{$customer->id()}"]);
}
```

### ❌ Over-Invalidation

**Wrong**:

```php
// Bad: Invalidating more than necessary
public function updateCustomerEmail(Customer $customer): void
{
    $this->dm->flush();
    $this->cache->invalidateTags(['customer']); // Clears ALL customers!
}
```

**Right**:

```php
// Good: Invalidate only affected cache
public function updateCustomerEmail(Customer $customer): void
{
    $this->dm->flush();
    $this->cache->invalidateTags(["customer.{$customer->id()}"]);
}
```

### ❌ Forgetting Related Caches

**Wrong**:

```php
// Bad: Invalidating entity but forgetting related lists
public function createCustomer(Customer $customer): void
{
    $this->dm->persist($customer);
    $this->dm->flush();
    $this->cache->invalidateTags(["customer.{$customer->id()}"]);
    // Missing: customer.list invalidation!
}
```

**Right**:

```php
// Good: Invalidate entity AND related lists
public function createCustomer(Customer $customer): void
{
    $this->dm->persist($customer);
    $this->dm->flush();
    $this->cache->invalidateTags([
        "customer.{$customer->id()}",
        'customer.list', // Lists need refresh
    ]);
}
```

---

## Invalidation Testing

**Test that invalidation happens correctly**:

```php
public function testCacheInvalidatedAfterUpdate(): void
{
    $customer = $this->createTestCustomer();

    // Cache customer
    $result1 = $this->repository->findById($customer->id());
    self::assertSame('John Doe', $result1->name());

    // Update customer
    $customer->updateName('Jane Doe');
    $this->repository->save($customer); // Should invalidate cache

    // Verify cache was invalidated
    $result2 = $this->repository->findById($customer->id());
    self::assertSame('Jane Doe', $result2->name());
}

public function testRelatedCachesInvalidated(): void
{
    // Cache customer list
    $list1 = $this->repository->findActiveCustomers();
    self::assertCount(5, $list1);

    // Create new customer
    $newCustomer = $this->createTestCustomer();
    $this->repository->save($newCustomer); // Should invalidate list cache

    // Verify list cache was invalidated
    $list2 = $this->repository->findActiveCustomers();
    self::assertCount(6, $list2); // New customer appears
}
```

---

## Recommended Strategy by Use Case

| Use Case                        | Recommended Strategy          | Rationale                        |
| ------------------------------- | ----------------------------- | -------------------------------- |
| Single entity CRUD              | Write-through                 | Simple, predictable              |
| Entity with related data        | Tag-based                     | Invalidate multiple caches       |
| Complex domain events           | Event-driven                  | Decouple logic, flexibility      |
| External data imports           | Manual + Time-based           | Control when refresh happens     |
| Historical/archival data        | Time-based only               | Data never changes               |
| High-frequency writes           | SWR (see swr-pattern.md)      | Reduce invalidation overhead     |
| Multi-tenant isolation          | Tag-based (with tenant tags)  | Isolate cache by tenant          |
| Cross-service invalidation      | Event-driven (message bus)    | Distributed invalidation         |

---

## Summary

**Key Principles**:

1. **Always invalidate explicitly** on write operations
2. **Use cache tags** for flexible batch invalidation
3. **Consider related caches** when invalidating
4. **Test invalidation behavior** thoroughly
5. **Log invalidation events** for debugging
6. **Document invalidation strategy** in cache policy

**Invalidation Checklist**:
- ✅ Invalidate on create/update/delete
- ✅ Use cache tags for batch operations
- ✅ Invalidate related caches (lists, aggregations)
- ✅ Log invalidation events
- ✅ Test stale read scenarios
- ✅ Document invalidation triggers in cache policy

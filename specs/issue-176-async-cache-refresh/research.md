# Research: Issue 176 Abstract Async Endpoint Cache Refresh

## Issue Scope

GitHub issue #176 asks for endpoint-grade cache consistency. The planned solution should create reusable layered invalidation plus an async cache refresh foundation, with Customer as the first adopter. The requested outcome is not just invalidation: writes, exposed domain events, and custom repository paths must invalidate affected cache tags and enqueue dedicated cache refresh work that runs in Symfony Messenger workers backed by AWS SQS, while LocalStack remains the local transport.

## Current State

The service already has a Redis-backed tag-aware customer cache:

- `config/packages/cache.yaml` defines `cache.customer` with Redis, default lifetime `600`, and tags enabled.
- `config/packages/test/cache.yaml` mirrors `cache.customer` with an array adapter and tags enabled.
- `src/Core/Customer/Infrastructure/Repository/CachedCustomerRepository.php` decorates `MongoCustomerRepository` and caches `find()` and `findByEmail()`.
- `src/Shared/Infrastructure/Cache/CacheKeyBuilder.php` centralizes customer ID, email, and collection cache key construction.
- `src/Core/Customer/Infrastructure/Collection/CustomerCacheTagCollection.php` and `src/Core/Customer/Infrastructure/Resolver/CustomerCacheTagResolver.php` centralize deletion tag resolution.

The current cache model is still method-local and partly hardcoded:

- Customer detail TTL is hardcoded at 600 seconds in `CachedCustomerRepository::loadCustomerFromDb()`.
- Customer email lookup TTL is hardcoded at 300 seconds in `CachedCustomerRepository::loadCustomerByEmail()`.
- Policy comments live near methods instead of first-class endpoint/query policy classes.
- `buildCustomerCollectionKey()` exists, but collection endpoint caching is not implemented in the repository decorator.
- Customer status/type reference endpoints are not cached in dedicated repository decorators.

The service already has async domain event delivery:

- `config/packages/messenger.yaml` routes `DomainEventEnvelope` to the `domain-events` transport and failed envelopes to `failed-domain-events`.
- `.env` configures SQS DSNs against LocalStack on port `4566`.
- `src/Shared/Infrastructure/Bus/Event/Async/ResilientAsyncEventDispatcher.php` dispatches events through Messenger and isolates SQS dispatch failures from write requests.
- `src/Shared/Infrastructure/Bus/Event/Async/DomainEventMessageHandler.php` invokes tagged domain event subscribers in workers, logs subscriber failures, emits metrics, and continues.
- `config/services_test.yaml` swaps the event bus back to synchronous in-memory processing for integration tests.

Customer cache invalidation currently happens in domain event subscribers:

- `CustomerCreatedCacheInvalidationSubscriber` invalidates customer ID, customer email, and collection tags.
- `CustomerUpdatedCacheInvalidationSubscriber` invalidates customer ID, current email, previous email when changed, and collection tags.
- `CustomerDeletedCacheInvalidationSubscriber` invalidates customer ID, email, and collection tags.

Those subscribers only invalidate tags. They do not enqueue dedicated refresh work or warm cache entries in background workers.

The current Customer events are sufficient for invalidation but not sufficient for event-only refresh:

- `CustomerCreatedEvent` carries customer ID and email.
- `CustomerUpdatedEvent` carries customer ID, current email, and previous email.
- `CustomerDeletedEvent` carries customer ID and email.
- `CachedCustomerRepository` caches full `Customer` objects for `find()` and `findByEmail()`.
- A cacheable Customer object includes fields beyond event identifiers, such as initials, phone, lead source, type, status, confirmation state, ULID, and timestamps.

Therefore the first implementation should refresh from persisted state through a context adapter. Event-snapshot refresh can be added later only if events carry complete, versioned, serialization-stable payloads and stale-overwrite guards.

Repository writes generally flow through `BaseRepository::save()` and `BaseRepository::delete()`, but some infrastructure repositories also define custom delete methods. `MongoCustomerRepository::deleteByEmail()` and `deleteById()` load managed Customer documents and delegate deletion. `MongoTypeRepository::deleteByValue()` and `MongoStatusRepository::deleteByValue()` remove managed documents and flush. A generic Doctrine MongoDB ODM listener can inspect scheduled insertions, updates, deletions, and change sets at flush time, then invalidate tags after a successful flush. This is a better shared invalidation point than adding one cache repository per entity or adding cache invalidation methods to Domain repository interfaces.

The design still needs a repository fallback for custom methods that do not produce ODM UnitOfWork change sets, such as future bulk update/delete query builder operations or external database writes. Those methods should call the shared invalidation command after successful write completion. This keeps all invalidation sources on the same rule/tag resolver surface:

- domain events
- ODM document change sets
- repository fallback calls for unobservable custom writes

Observability already uses typed EMF business metrics:

- Application metric classes extend `BusinessMetric`.
- Factories create metrics instead of hardcoding arrays.
- `BusinessMetricsEmitterInterface` emits typed metrics.
- Existing async failure metrics include `SqsDispatchFailureMetric` and `EventSubscriberFailureMetric`.
- Tests use `BusinessMetricsEmitterSpy`.

Load and cache tests already exist:

- `make cache-performance-tests` runs `tests/Integration/Customer/Infrastructure/Repository/CachePerformanceTest.php`.
- `make cache-performance-load-tests` runs the K6 `rest-api/cachePerformance` smoke scenario.
- `tests/Load/scripts/rest-api/cachePerformance.js` warms customer reads and reports heuristic cache hit indicators.
- Existing integration tests cover cache population, invalidation, SWR-style cache population, and delete cleanup.

## Architecture Constraints

- Follow the repository skills: cache-management, implementing-ddd-architecture, observability-instrumentation, load-testing, documentation-sync, testing-workflow, and ci-workflow.
- Use Makefile targets or Docker container access only for PHP checks; do not run PHP directly on the host.
- Keep Domain free of Symfony, Doctrine, API Platform, cache, Messenger, and logging dependencies.
- Use typed classes and collections instead of array-shaped policies or metric payloads.
- Use factories for complex object construction where needed.
- Use `TagAwareCacheInterface` for tagged cache operations.
- Cache operations must be best effort and must not break business writes.
- Event subscribers may live in Application and can depend on Infrastructure per the current deptrac rules.
- Async cache refresh should reuse the repo's CQRS command directories: `Application/Command` and `Application/CommandHandler`.
- Shared metrics should reuse `Shared/Application/Observability/Metric`.
- Cache policy structure should reuse existing type directories such as `DTO`, `Factory`, `Collection`, and `Resolver`.
- Automatic CRUD invalidation should live in `Shared/Infrastructure/EventListener` and use existing `Collection` and `Resolver` directory types for mapping.
- Domain repository interfaces should remain cache-free. Invalidation rules belong to infrastructure/application adapters.
- Do not introduce new context `Infrastructure/Cache`, `ReadModel`, `Policy`, `Registry`, `Scheduler`, `Message`, or `MessageHandler` directories for this feature unless the implementation first proves the current source tree and `deptrac.yaml` already support that directory type.

## Implementation Surface

### Shared Reusable Orchestration

Likely shared production code changes:

- Add a generic `CacheRefreshCommand` under `Shared/Application/Command` with scalar context, family, target identifiers, strategy, and event metadata.
- Add a generic `CacheRefreshCommandHandler` under `Shared/Application/CommandHandler` as the single Messenger worker entrypoint.
- Add `AbstractCacheInvalidationSubscriber`, `AbstractCacheRefreshCommandFactory`, and `AbstractCacheRefreshCommandHandler` for reusable event-to-refresh orchestration and context handler behavior.
- Add shared cache policy/target/result DTOs, resolver interfaces, handler resolver, handler collection, policy collection, and target resolver collection.
- Add shared cache refresh lifecycle metrics under `Shared/Application/Observability/Metric`.
- Add a generic `CacheInvalidationCommand` and handler used by domain event subscribers, the ODM listener, and repository fallbacks.
- Add `CacheInvalidationDoctrineEventListener` under `Shared/Infrastructure/EventListener` to collect ODM change-set driven invalidation and scheduling work.
- Add shared invalidation rule collection and tag resolver classes under existing Shared Infrastructure `Collection` and `Resolver` directories.
- Add generic cache key helpers to `CacheKeyBuilder` if needed.
- Add `cache-refresh` and `failed-cache-refresh` transports while keeping the existing `domain-events` transport unchanged.

### Customer Adopter Changes

Likely Customer production code changes:

- Add Customer cache policy collection/resolver and refresh target resolver under existing Infrastructure directories.
- Add Customer cache invalidation rule collection/resolver under existing Infrastructure directories.
- Add `CustomerCacheRefreshCommandFactory` under `Application/Factory`.
- Add `CustomerCacheRefreshCommandHandler` under `Application/CommandHandler` as a registered adapter for the shared worker.
- Add customer endpoint cache policy configuration in `config/services.yaml` with environment-overridable TTLs and jitter.
- Split cache pools in `config/packages/cache.yaml` and `config/packages/test/cache.yaml`, likely:
  - `cache.customer.detail`
  - `cache.customer.lookup`
  - `cache.customer.collection`
  - `cache.customer.reference`
- Update `CachedCustomerRepository` to use declared policies for customer detail and email lookup instead of method-local TTL constants.
- Update create/update/delete invalidation so normal ODM writes are covered by the shared listener, exposed Customer domain events are covered by subscribers, and any custom repository method that bypasses ODM observation calls the shared invalidation command as a fallback.
- Update docs for shared refresh orchestration, Customer policies, TTL defaults, LocalStack/SQS refresh routing, and operational metrics.

Potential test changes:

- Unit tests for shared command serialization, handler delegation, handler failure isolation, subscriber/factory behavior, policy construction, resolver behavior, TTL jitter bounds, and metric dimensions.
- Unit tests for ODM listener insert/update/delete handling, old/new tag resolution, post-flush invalidation, and best-effort failure behavior.
- Unit tests for domain-event subscriber invalidation through the shared invalidation command.
- Unit tests for repository fallback invalidation for any custom write method that bypasses ODM observation.
- Unit tests for Customer refresh command creation from create/update/delete subscribers.
- Unit tests for Customer invalidation rules and automatic old/new email tag resolution.
- Unit tests for Customer refresh handler success, missing entity/negative cache handling, and failure metric emission.
- Integration tests that perform Customer create/update/delete writes and verify cache entries are invalidated and repopulated by the shared worker plus Customer handler after invalidation.
- Existing cache performance tests should continue to pass.
- Existing K6 cache performance smoke scenario can be used as load evidence.

## Key Risks

- A generic shared design may still carry Customer-only payload assumptions. The payload must use feature-neutral fields and leave Customer-specific mapping in the Customer adapter.
- A generic ODM listener can become too implicit if rules are not explicit. Keep document-class, operation, and field mappings in typed collection/resolver classes with focused tests.
- Bulk writes or direct database operations that bypass ODM UnitOfWork change sets may bypass automatic invalidation. The first implementation should classify custom repository methods and require repository fallback calls for any path that cannot be observed by ODM.
- Domain-event invalidation and ODM invalidation may duplicate each other. This should be safe because tag invalidation is idempotent; refresh scheduling needs deterministic dedupe keys.
- Event-snapshot refresh can overwrite good cache with incomplete or stale data if events are not complete and versioned. Current Customer events should use invalidation plus `repository_refresh`, not `event_snapshot`.
- API Platform collection caching is not currently repository-level, so adding full collection refresh for arbitrary filters may exceed a safe first implementation. Forward-safe policy classes can declare collection policies while the first worker refreshes known detail/email workloads and invalidates collection tags.
- Customer type/status operations do not publish domain events today. Cache invalidation can still be covered through ODM listener rules for managed document changes; domain events are a follow-up only if business semantics or cross-context reactions require them.
- Symfony cache `get()` does not expose simple hit/miss hooks; hit/miss metrics may require a wrapper service or conservative instrumentation around callback execution.
- Messenger routing must keep the current domain event queue working and add a separate cache refresh route without breaking LocalStack.
- The repo requires `make ci`; that includes mutation testing and can be slow.

## Planning Assumptions

- The first implementation should deliver the shared refresh foundation plus Customer adoption for currently cached detail and email lookup families.
- Automatic invalidation should be layered through domain-event subscribers, a shared ODM listener, and repository fallback calls for custom writes that bypass ODM observation.
- Normal and custom repository methods that persist/remove managed documents and flush should rely on ODM listener coverage rather than per-entity cache repositories.
- The default refresh source should be `repository_refresh`; `event_snapshot` is future-only unless events contain complete, versioned cache snapshots.
- Customer collection and customer reference-data policies should be declared and documented, with collection tags still invalidated. Full arbitrary collection materialization can remain policy-ready unless the codebase exposes a stable cacheable query abstraction during implementation.
- Cache refresh failures should be logged and measured but must not rethrow into business writes or domain event processing.
- Tests should prefer direct worker and handler invocation for deterministic refresh verification, while Messenger routing config proves SQS/local transport wiring.

## Commands Run During Research

- `bmalph -C ${REPO_ROOT} doctor --json`
- `bmalph -C ${REPO_ROOT} status --json`
- `gh issue view 176 --repo VilnaCRM-Org/core-service --comments --json ...`
- `rg -n "Cache|Refresh|BusinessMetric|Messenger|SQS|TagAware|cache" src config tests docs tests/Load`
- `find src/Core/Customer -maxdepth 4 -type f`
- `find src/Shared -maxdepth 5 -type f`
- `sed -n ...` on the cache, messenger, observability, test, and documentation files listed above.

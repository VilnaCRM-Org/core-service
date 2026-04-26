# Research: Issue 176 Async Endpoint Cache Refresh

## Issue Scope

GitHub issue #176 asks for endpoint-grade cache consistency for customer-facing cached query shapes. The requested outcome is not just invalidation: domain events must invalidate affected cache tags and enqueue dedicated cache refresh work that runs in Symfony Messenger workers backed by AWS SQS, while LocalStack remains the local transport.

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
- Cache policy structure should reuse existing type directories such as `Application/DTO`, `Application/Factory`, `Infrastructure/Collection`, and `Infrastructure/Resolver`.
- Do not introduce new Customer `Infrastructure/Cache`, `ReadModel`, `Policy`, `Registry`, `Scheduler`, `Message`, or `MessageHandler` directories for this feature unless the implementation first proves the current source tree and `deptrac.yaml` already support that directory type.

## Implementation Surface

Likely production code changes:

- Add endpoint cache policy DTO/factory/collection/resolver classes under the existing Customer Application and Infrastructure directories.
- Add customer endpoint cache policy configuration in `config/services.yaml` with environment-overridable TTLs and jitter.
- Split cache pools in `config/packages/cache.yaml` and `config/packages/test/cache.yaml`, likely:
  - `cache.customer.detail`
  - `cache.customer.lookup`
  - `cache.customer.collection`
  - `cache.customer.reference`
- Update `CachedCustomerRepository` to use declared policies for customer detail and email lookup instead of method-local TTL constants.
- Add `RefreshCustomerCacheCommand` and `RefreshCustomerCacheCommandHandler` routed through Messenger to SQS in non-test environments.
- Add a command factory and target resolver that event subscribers use after invalidation.
- Update create/update/delete invalidation subscribers to enqueue same-entity refresh workloads for affected families.
- Add typed cache refresh lifecycle metrics for scheduled, succeeded, failed, stale served, and cache lookup hit/miss where feasible.
- Update docs for cache policies, TTL defaults, LocalStack/SQS refresh routing, and operational metrics.

Potential test changes:

- Unit tests for cache policy construction, collection lookup, resolver behavior, TTL jitter bounds, and key/tag schema.
- Unit tests for refresh command creation from create/update/delete subscribers.
- Unit tests for refresh command handler success, missing entity/negative cache handling, and failure metric emission.
- Integration tests that publish customer domain events and verify cache entries are repopulated by the refresh handler after invalidation.
- Existing cache performance tests should continue to pass.
- Existing K6 cache performance smoke scenario can be used as load evidence.

## Key Risks

- API Platform collection caching is not currently repository-level, so adding full collection refresh for arbitrary filters may exceed a safe first implementation. Forward-safe policy classes can declare collection policies while the first worker refreshes known detail/email workloads and invalidates collection tags.
- Customer type/status operations do not publish domain events today, so reference-data cache refresh should either be limited to declared policy plus current invalidation behavior or be split into a follow-up if events are missing.
- Symfony cache `get()` does not expose simple hit/miss hooks; hit/miss metrics may require a wrapper service or conservative instrumentation around callback execution.
- Messenger routing must keep the current domain event queue working and add a separate cache refresh route without breaking LocalStack.
- The repo requires `make ci`; that includes mutation testing and can be slow.

## Planning Assumptions

- The first PR should implement cache policy DTO/factory/collection/resolver classes, split pools, and async refresh for the currently cached customer detail and email lookup families.
- Customer collection and customer reference-data policies should be declared and documented, with collection tags still invalidated. Full arbitrary collection materialization can remain policy-ready unless the codebase exposes a stable cacheable query abstraction during implementation.
- Cache refresh failures should be logged and measured but must not rethrow into business writes or domain event processing.
- Tests should prefer direct handler invocation for deterministic refresh verification, while Messenger routing config proves SQS/local transport wiring.

## Commands Run During Research

- `bmalph -C ${REPO_ROOT} doctor --json`
- `bmalph -C ${REPO_ROOT} status --json`
- `gh issue view 176 --repo VilnaCRM-Org/core-service --comments --json ...`
- `rg -n "Cache|Refresh|BusinessMetric|Messenger|SQS|TagAware|cache" src config tests docs tests/Load`
- `find src/Core/Customer -maxdepth 4 -type f`
- `find src/Shared -maxdepth 5 -type f`
- `sed -n ...` on the cache, messenger, observability, test, and documentation files listed above.

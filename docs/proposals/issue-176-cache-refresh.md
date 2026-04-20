# Issue 176: Endpoint Cache Invalidation and Async Refresh

## Summary

`#176` formalizes cache consistency as an application capability instead of a few hardcoded repository rules. The goal is to keep write paths fast, keep stale reads bounded, and move cache recalculation off the request path into background workers dispatched through Symfony Messenger on AWS SQS.

## Current State

The repository already has the right primitives, but not the full mechanism:

- Redis-backed cache pools exist, but customer caching is concentrated in a single `cache.customer` pool with pool-level defaults and per-method TTL overrides.
- `CachedCustomerRepository` hardcodes TTLs per method and only covers `find()` and `findByEmail()` today, with `find()` set to `600` seconds and `findByEmail()` set to `300` seconds.
- customer domain-event subscribers invalidate tags asynchronously, but they do not repopulate endpoint caches after invalidation.
- the async event pipeline already runs through Messenger + SQS, so the missing piece is refresh scheduling and refresh handlers, not a new transport stack.

Relevant files:

- `config/packages/cache.yaml`
- `config/packages/messenger.yaml`
- `config/services.yaml`
- `src/Core/Customer/Infrastructure/Repository/CachedCustomerRepository.php`
- `src/Core/Customer/Application/EventSubscriber/*CacheInvalidationSubscriber.php`

## Problem Statement

Tag invalidation alone is not enough for endpoint-grade cache behavior:

- the next read after a write still pays the full cache miss cost
- TTL policy is not centralized, so new caches will drift
- collection endpoints and reference endpoints do not have an explicit cache-policy model
- there is no worker-owned cache refresh path that can rebuild expensive keys before the next user request hits them

For a CRM, this creates the wrong tradeoff. Operators expect writes to complete quickly, but they also expect list screens, detail screens, and reference datasets to converge quickly after changes.

## Sources and TTL Guidance

The proposed TTL defaults below are an inference from the official sources, not a copied vendor table:

- AWS cache validity guidance says TTL should be chosen from the rate of change of the underlying data and the risk of serving stale data, and recommends TTL jitter to avoid synchronized expiry spikes.
- The AWS Builders Library recommends soft TTL and hard TTL, metrics on cache hits and misses, and avoiding arbitrary TTLs that are never revisited.
- Symfony recommends using cache tags for dependency invalidation and expiration for time-based freshness.

References:

- AWS Cache Validity: <https://docs.aws.amazon.com/whitepapers/latest/database-caching-strategies-using-redis/cache-validity.html>
- AWS Builders Library, caching challenges and strategies: <https://d1.awsstatic.com/builderslibrary/pdfs/caching-challenges-and-strategies.pdf>
- Symfony cache invalidation: <https://symfony.com/doc/current/components/cache/cache_invalidation.html>

## Proposed Architecture

### 1. Endpoint Cache Policy Registry

Add a registry that declares cache behavior per endpoint or query family. A policy must define:

- cache namespace
- key builder
- tags
- consistency class
- soft TTL
- hard TTL
- jitter range
- refresh strategy
- owning refresh handler

Suggested location:

- `src/Shared/Infrastructure/Cache/EndpointCachePolicyRegistry.php`

### 2. Worker-Owned Refresh Messages

Introduce explicit refresh messages rather than hiding refresh logic inside invalidation subscribers.

Suggested messages and handlers:

- `src/Shared/Application/Cache/Message/RefreshCacheEntryMessage.php`
- `src/Shared/Application/Cache/MessageHandler/RefreshCacheEntryMessageHandler.php`
- `src/Core/Customer/Application/Cache/*` for customer-specific refresh planners

The refresh message should carry only the data needed to rebuild a cache family, for example:

- policy id
- resource id or normalized filter hash
- triggering event id
- causation metadata for logs and metrics
- deduplication key, stable for the cache family, target resource, and triggering event
- event occurrence timestamp for stale-message detection
- entity version or another monotonic sequence token for ordering checks

Transport note:

- Prefer SQS FIFO queues for cache-refresh messages when native ordering and five-minute deduplication are required from the transport itself.
- If SQS Standard is retained, the handler must enforce the same deduplication window and monotonic ordering checks in application code by using the deduplication key, event timestamp, and entity version fields above.

Handlers must be idempotent. They must ignore SQS retries with the same deduplication key inside the configured deduplication window, and they must drop stale refreshes when ordering data shows the message has already been superseded by a newer event for the same cache target. The minimum rule is:

- drop when `entity_version <= last_applied_version` for the same cache target
- if no version exists, drop when `event_occurred_at` is older than the last applied refresh timestamp
- record a `refresh skipped as stale` metric whenever this happens

### 3. Domain Events Drive Both Invalidation and Refresh Scheduling

Customer-created, updated, and deleted events should map to affected cache families. For each family:

1. Invalidate the relevant tags immediately.
2. Dispatch a refresh message for the keys that should become warm again.

This keeps writes non-blocking while ensuring warm caches are restored by workers instead of the next user request.

`TagAwareCacheInterface::invalidateTags()` must stay best-effort and non-blocking on the write path:

- catch and log `Psr\Cache\InvalidArgumentException` and any other thrown exception
- treat a `false` return value as a warning and emit logs and metrics for it
- never fail the originating write command because cache-tag invalidation could not complete

### 4. Keep Query Logic Canonical

Refresh handlers should recompute cache entries by calling the same canonical query services or repositories that production requests use. They must not duplicate business logic in ad hoc warmers.

### 5. Observability

Add metrics and logs for:

- cache hit
- cache miss
- stale served
- refresh scheduled
- refresh completed
- refresh failed
- refresh skipped because newer event already superseded it
- queue lag and retry count

Logging and privacy rule:

- do not log raw refresh payloads, customer emails, or other PII
- log only policy ids, event ids, hashed or truncated cache target identifiers, and bounded error metadata
- metrics labels must use sanitized identifiers only; no raw payload fields may be emitted

## Default TTL Matrix

These defaults are proposed starting points for CRM traffic and should be tuned from production metrics:

| Cache family                      | Fresh TTL | Hard TTL | Jitter  | Rationale                                                                       |
| --------------------------------- | --------- | -------- | ------- | ------------------------------------------------------------------------------- |
| Customer detail by id             | 5 min     | 30 min   | +/- 15% | Frequently read, moderate stale tolerance, should converge quickly after writes |
| Customer detail by email          | 5 min     | 30 min   | +/- 15% | Same volatility as detail lookups, often used in lookup and auth-style reads    |
| Filtered customer collections     | 60 sec    | 5 min    | +/- 10% | List screens must reflect recent writes quickly                                 |
| Reference data: customer types    | 30 min    | 6 h      | +/- 20% | Rare admin changes, high read rate, explicit event invalidation available       |
| Reference data: customer statuses | 30 min    | 6 h      | +/- 20% | Same characteristics as customer types                                          |
| Negative lookups                  | 15 sec    | 60 sec   | +/- 10% | Prevent thundering herd without hiding new writes for long                      |

Interpretation:

- `fresh TTL` is the normal serve-from-cache window.
- `hard TTL` is the maximum stale window during refresh failures or dependency brownouts.
- `jitter` is applied at write time to avoid synchronized expiry bursts.

## Event-to-Cache Mapping

Initial customer bounded-context mapping:

- `CustomerCreatedEvent`
  - invalidate and refresh customer collections
  - invalidate and refresh detail lookups if the created resource is directly readable by id/email
- `CustomerUpdatedEvent`
  - invalidate and refresh customer detail by id
  - invalidate and refresh customer detail by current email
  - invalidate previous email key if email changed
  - invalidate and refresh affected customer collections
- `CustomerDeletedEvent`
  - invalidate detail and collection caches
  - do not eagerly repopulate deleted detail keys; use short negative-cache strategy instead

If customer types and statuses later get domain events, the same planner pattern should apply to their reference-data caches.

## Proposed Wiring

### Cache

- expand `config/packages/cache.yaml` from one customer pool to named pools or policy-backed namespaces
- keep Redis tag support enabled
- add policy-owned default TTLs instead of repository-owned constants

### Messenger

- keep `domain-events` as the event transport
- add a dedicated `cache-refresh` transport or route refresh messages through the same SQS transport with explicit routing keys
- configure worker commands for refresh processing and retries

### Services

- bind policy registry, refresh planners, and handlers in `config/services.yaml`
- keep cache operations best-effort on writes
- keep worker failures observable and retryable without failing the originating business command

## Rollout Plan

1. Introduce the policy registry and TTL model.
2. Refactor customer caches to read policy from the registry.
3. Add refresh messages and handlers.
4. Convert customer invalidation subscribers into invalidate-plus-schedule subscribers.
5. Add reference-data caches for customer types and statuses using the same model.
6. Add worker and load-test evidence.

## Acceptance Scope for the Future Implementation

The implementation that closes `#176` should prove:

- every cached endpoint family has an explicit policy
- domain events invalidate and schedule refresh asynchronously
- cache rebuild does not happen on the write path
- local SQS-backed workers continue to operate through the current LocalStack setup until the emulator migration lands
- tests cover stale fallback, refresh, jitter, and failure handling
- load/performance evidence shows the cache stays beneficial after the refresh mechanism is added

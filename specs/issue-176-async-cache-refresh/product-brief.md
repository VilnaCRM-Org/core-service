# Product Brief: Abstract Async Endpoint Cache Refresh

## Problem

Customer reads are cached, but cache consistency is still request-path driven. Domain events invalidate cache tags, and the next user read may pay the cost to rebuild the affected cache entry. Cache policy is also spread across repository methods and Symfony cache pool defaults instead of being declared per endpoint/query family.

The current domain events identify affected cache entries, but they do not carry complete Customer snapshots. Rebuilding cache entries directly from those events would couple correctness to incomplete payloads. The safer shared design is automatic CRUD invalidation from ODM write/change-set data plus async refresh from persisted state.

Solving this only with Customer-specific commands and workers would create a second product risk: every future bounded context would need to copy the same refresh queue, worker, metrics, and failure behavior.

This creates three product risks:

- Related read endpoints can go cold after every write.
- TTL choices are hardcoded and hard to justify or tune by data volatility.
- Operators and developers cannot see enough reusable cache refresh lifecycle signals to tell whether async consistency is healthy across features.

## Stakeholders

- API consumers who expect reads to become fresh shortly after writes.
- Operators who need visibility into refresh scheduling, success, failure, and stale reads.
- Platform developers who need one reusable cache refresh pattern.
- Future feature teams that need to adopt cache refresh without designing their own queue or worker.
- Customer feature developers who need the first concrete adopter for detail and email lookup refresh.

## Goals

- Add one reusable cache refresh orchestration that can be adopted by any bounded context.
- Add one reusable automatic CRUD invalidation path that can invalidate cache tags for any mapped ODM document.
- Keep the existing domain-event worker as the ingress for any domain event.
- Add one generic refresh command payload, one shared `cache-refresh` queue, and one shared worker.
- Keep context-specific mapping in bounded-context adapters.
- Implement Customer detail and email lookup refresh as the first adoption.
- Declare Customer collection and reference policies now, while keeping arbitrary proactive collection warmup out of scope until a deterministic query abstraction exists.
- Emit shared typed EMF metrics for cache refresh lifecycle and cache read behavior where detectable.
- Add unit, integration, and performance evidence for invalidation, async refresh, TTL jitter, and post-write cache freshness.

## Non-Goals

- Do not introduce a new external queue technology.
- Do not move Domain entities or repositories into framework-dependent code.
- Do not implement arbitrary API Platform collection materialization beyond stable query shapes. A stable query shape has a deterministic key and parameter set, such as a customer list filtered by status; arbitrary materialization means warming ad-hoc API Platform filter combinations without a repository query contract. See [architecture.md](architecture.md) for the collection caching strategy.
- Do not block writes on refresh success.
- Do not create per-domain cache-refresh queues in the first implementation.
- Do not add cache invalidation methods to Domain repository interfaces.
- Do not use event-only cache refresh for Customer until events carry complete, versioned cache snapshots.
- Do not introduce `ReadModel`, `Message`, `MessageHandler`, `Scheduler`, `Registry`, `Policy`, or context-level `Cache` directories.
- Do not lower quality, coverage, mutation, architecture, or style thresholds.

## Success Metrics

- One shared refresh command, queue, and worker path can refresh cache entries for any registered bounded-context adapter.
- One shared ODM listener can invalidate mapped cache tags after successful create/update/delete flushes.
- Customer adopts the shared path for currently cached detail and email lookup families.
- Hardcoded Customer detail/email TTLs are replaced by resolved policy objects.
- Automatic CRUD invalidation and domain events enqueue refresh work after invalidation without blocking writes.
- Refresh failures are logged and measured but do not fail domain-event processing.
- Local and CI Messenger routing works with in-memory test transport, SQS runtime transport, and LocalStack-backed local transport.
- `make ci` passes.
- Cache performance smoke evidence is captured with `make cache-performance-tests` and `make cache-performance-load-tests` where runtime services allow.

## Key Requirements

- Add shared cache-refresh DTOs with context, family, target identifiers, strategy, and event metadata.
- Add shared invalidation DTOs/resolvers/collections for document-class, operation, and change-set driven tag resolution.
- Add a shared Doctrine MongoDB ODM invalidation listener that invalidates after successful flush and can schedule `repository_refresh` work.
- Add a generic `CacheRefreshCommand` and shared `CacheRefreshCommandHandler`.
- Add abstract subscriber, factory, and context handler classes so bounded contexts only implement event mapping and warmup logic.
- Add Customer policy collection/resolver/target resolver/handler adapters through existing directories.
- Add Customer invalidation rule/tag adapters through existing Infrastructure collection/resolver directories.
- Keep Customer cache invalidation subscribers narrow, using them only where domain-event scheduling is still needed beyond automatic CRUD invalidation.
- Use environment-overridable TTL/jitter parameters in Symfony service config.
- Add shared typed metrics for refresh scheduled, success, failure, stale served, hit, and miss.
- Keep cache, queue, and metric failures best effort.
- Update documentation for shared orchestration, Customer first adoption, TTL defaults, and operations.

## Assumptions

- First implementation focuses background refresh on currently cached Customer detail and email lookup entries.
- Same-entity refresh workloads mean refresh jobs for the specific cache keys affected by the write or domain event, such as Customer detail by ID and lookup by email. They do not include arbitrary collection query refreshes.
- Detail and email lookup refresh use `repository_refresh`, meaning the worker reloads current persisted state through a context adapter.
- `event_snapshot` remains a future refresh source for events that carry complete, versioned, serialization-stable cache payloads.
- Collection and reference-data policies are declared and tags are invalidated immediately. Async refresh for collections remains a follow-up unless a deterministic query-shape abstraction exists; until then, collection entries stay request-path cached after invalidation instead of being warmed proactively.
- Integration tests can invoke the shared worker and Customer handler directly for deterministic proof while unit tests cover Messenger routing and command dispatch.

## Open Questions

- Should Customer type/status create/update/delete operations publish domain events in a follow-up so reference-data refresh can be fully event-driven?
- Should collection endpoint caching be implemented through an API Platform provider decorator in a later issue?
- Are there any production write paths that bypass ODM UnitOfWork change sets and need explicit repository-level invalidation fallback?

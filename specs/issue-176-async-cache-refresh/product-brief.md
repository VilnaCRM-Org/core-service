# Product Brief: Async Endpoint Cache Refresh

## Problem

Customer reads are cached, but cache consistency is still request-path driven. Domain events invalidate cache tags, and the next user read may pay the cost to rebuild the affected cache entry. Cache policy is also spread across repository methods and Symfony cache pool defaults instead of being declared per endpoint/query family.

This creates three product risks:

- Write endpoints stay available, but related read endpoints can go cold after every write.
- TTL choices are hardcoded and hard to justify or tune by data volatility.
- Operators cannot see enough cache refresh lifecycle signals to tell whether async consistency is healthy.

## Stakeholders

- API consumers who expect customer reads to become fresh shortly after writes.
- Operators who need visibility into cache refresh scheduling, success, failure, and stale reads.
- Developers who need a clear cache policy contract for every cached customer query family.

## Goals

- Declare cache policy centrally for every customer cached endpoint/query family.
- Split customer cache behavior by family: detail, lookup, collection, and reference data.
- Keep business writes fast by invalidating cache and scheduling refresh work asynchronously.
- Route refresh work through Symfony Messenger using SQS in non-test environments and LocalStack for local/test parity.
- Emit typed EMF metrics for cache refresh lifecycle and cache read behavior.
- Add unit/integration/load evidence for invalidation, async refresh, TTL jitter, and post-write cache freshness.

## Non-Goals

- Do not introduce a new external queue technology.
- Do not move Domain entities or repositories into framework-dependent code.
- Do not implement arbitrary API Platform collection materialization beyond stable query shapes. A stable query shape has a deterministic key and parameter set, such as a customer list filtered by status; arbitrary materialization means warming ad-hoc API Platform filter combinations without a repository query contract. See [architecture.md](architecture.md) for the collection caching strategy.
- Do not block writes on refresh success.
- Do not lower quality, coverage, mutation, architecture, or style thresholds.

## Success Metrics

- All currently cached customer query families use declared policy objects instead of hardcoded TTL constants.
- Domain events enqueue refresh work after invalidation.
- Cache refresh handler warms affected customer detail and email lookup entries without a user request.
- Refresh failures are logged and measured but do not fail domain-event processing.
- Local and CI Messenger routing works with in-memory test transport, SQS runtime transport, and LocalStack-backed local transport.
- `make ci` passes.
- Cache performance smoke evidence is captured with `make cache-performance-tests` and `make cache-performance-load-tests` where runtime services allow.

## Key Requirements

- Add typed cache policy registry with namespace, tags, soft TTL, hard TTL, jitter, consistency class, and refresh strategy.
- Use environment-overridable TTL/jitter parameters in Symfony service config.
- Add dedicated cache refresh message and Messenger handler.
- Update customer cache invalidation subscribers to invalidate and schedule same-entity refresh workloads.
- Add typed metrics for refresh scheduled, success, failure, stale served, hit, and miss.
- Keep cache and metric failures best effort.
- Update documentation for TTL defaults and operations.

## Assumptions

- First implementation focuses background refresh on currently cached customer detail and email lookup entries.
- Same-entity refresh workloads mean refresh jobs for the specific cache keys affected by the domain event, such as customer detail by ID and lookup by email. They do not include arbitrary collection query refreshes.
- Collection and reference-data policies are declared and tags are invalidated immediately. Async refresh for collections remains a follow-up unless a deterministic query-shape abstraction exists; until then, collection entries stay request-path cached after invalidation instead of being warmed proactively.
- Integration tests can invoke the refresh handler directly for deterministic proof while unit tests cover Messenger routing and scheduler dispatch.

## Open Questions

- Should customer type/status create/update/delete operations publish domain events in a follow-up so reference-data refresh can be fully event-driven?
- Should collection endpoint caching be implemented through an API Platform provider decorator in a later issue?

# PRD: Abstract Async Endpoint Cache Refresh

## Objective

Implement endpoint-grade cache consistency through a reusable domain-event-driven cache refresh pattern. Customer cached families are the first implementation scope, but the shared command payload, queue, worker, abstract subscriber, abstract factory, handler base, policy DTOs, and metrics must be reusable by future bounded contexts.

## Functional Requirements

1. Provide shared typed cache-refresh policy and target DTOs for cached query families across bounded contexts.
2. Provide shared orchestration classes for:
   - domain-event cache invalidation subscribers
   - cache refresh command factories
   - the generic refresh cache command
   - the generic refresh cache command worker
   - reusable context refresh command handler behavior
3. Add a single shared cache-refresh Messenger transport backed by SQS in non-test environments and LocalStack locally.
4. Keep the existing domain-event worker path intact so any domain event can trigger cache invalidation and refresh scheduling through tagged subscribers.
5. Allow each bounded context to declare cache families and refresh adapters through existing class-type directories, including:
   - policy collection
   - policy resolver
   - target resolver
   - cached repository decorator
   - concrete context refresh command handler
6. Implement Customer as the first adapter for:
   - customer detail by ID
   - customer lookup by email
   - customer collection/search policy declaration
   - customer reference-data policy declaration
   - negative lookup policy declaration
7. Replace hardcoded Customer detail/email TTLs in `CachedCustomerRepository` with resolved cache-refresh policies.
8. Update Customer create/update/delete cache subscribers so they:
   - invalidate affected tags
   - enqueue same-entity refresh workloads through the shared subscriber path
   - never break business writes because refresh scheduling fails
9. Add shared typed EMF metrics for:
   - cache refresh scheduled
   - cache refresh succeeded
   - cache refresh failed
   - cache hit
   - cache miss
   - stale served where the implementation can detect it
10. Document TTL defaults and justify them by data volatility and stale-read risk.

## Non-Functional Requirements

- Domain layer remains framework-free.
- Cache, queue, and metric failures are best effort.
- SQS routing uses existing AWS/LocalStack environment conventions.
- Implementation uses existing directory names and deptrac-collected class types.
- No new bounded-context `Cache`, `ReadModel`, `Policy`, `Registry`, `Scheduler`, `Message`, or `MessageHandler` directories are introduced.
- Tests use Makefile targets or Docker container execution.
- CI must pass without lowered thresholds.

## Acceptance Criteria Mapping

- Reusable orchestration: shared abstract subscriber, abstract factory, generic `CacheRefreshCommand`, generic `CacheRefreshCommandHandler`, reusable context handler base, policy DTOs, target DTOs, resolvers, collections, and metrics exist with unit tests.
- Domain-event invalidation plus async recalculation: existing domain-event worker invokes context subscribers, which schedule cache-refresh commands after invalidation.
- Queue reuse: one shared `cache-refresh` transport and one `failed-cache-refresh` transport handle the generic refresh command for all contexts.
- Customer first adapter: Customer detail and email lookup refresh through the shared orchestration; collection/reference policies are declared and tag-invalidated.
- LocalStack compatibility: `.env` and `config/packages/messenger.yaml` add cache refresh DSN/transport mirroring existing SQS conventions.
- Writes do not block on rebuild: subscribers only schedule work; handlers run separately.
- TTL defaults documented: docs update with policy table and rationale.
- Observability: shared typed EMF metrics and tests.
- Tests: unit coverage for shared orchestration, Customer adapter, policy resolution, command dispatch, handler behavior, subscribers, metrics, and integration coverage for post-event warmup.

## Out of Scope

- New reference-data domain events.
- Full arbitrary collection result materialization for every API Platform filter combination.
- Per-domain cache-refresh queues.
- New infrastructure dashboards or CloudWatch alarms.
- Introducing read models or projections for issue #176.

## Release Notes

This is a backwards-compatible internal behavior change. Existing API contracts remain unchanged.

# PRD: Abstract Async Endpoint Cache Refresh

## Objective

Implement endpoint-grade cache consistency through layered automatic invalidation plus async cache refresh. Customer cached families are the first implementation scope, but the shared invalidation command, ODM invalidation listener, domain-event subscribers, repository fallback path, refresh command payload, queue, worker, abstract factory, handler base, policy DTOs, and metrics must be reusable by future bounded contexts.

## Functional Requirements

1. Provide shared typed cache-refresh policy and target DTOs for cached query families across bounded contexts.
2. Provide shared orchestration classes for:
   - a generic idempotent cache invalidation command and handler
   - automatic CRUD cache invalidation from Doctrine MongoDB ODM flush/change-set data
   - invalidation rule and tag-set DTOs
   - domain-event cache invalidation subscribers
   - repository fallback invalidation for custom writes that bypass ODM change sets
   - cache refresh command factories
   - the generic refresh cache command
   - the generic refresh cache command worker
   - reusable context refresh command handler behavior
3. Add a single shared cache-refresh Messenger transport backed by SQS in non-test environments and LocalStack locally.
4. Keep the existing domain-event worker path intact so any exposed domain event can automatically trigger cache invalidation and refresh scheduling through tagged subscribers.
5. Allow each bounded context to declare cache families and refresh adapters through existing class-type directories, including:
   - invalidation rule collection
   - invalidation tag resolver
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
8. Add Customer automatic CRUD invalidation rules so create/update/delete writes:
   - invalidate affected ID, email, old-email, and collection tags after successful ODM flush
   - enqueue same-entity refresh workloads when the cache policy uses `repository_refresh`
   - never break business writes because cache invalidation or refresh scheduling fails
9. Review custom repository methods so:
   - methods that persist/remove managed documents and flush are covered by the ODM listener
   - methods that bypass ODM UnitOfWork change sets call the shared invalidation command after successful writes
   - repository fallback invalidation remains outside Domain repository interfaces
10. Add deterministic dedupe keys so domain-event, ODM, and repository-fallback invalidation signals can overlap without unbounded duplicate refresh jobs.
11. Add shared typed EMF metrics for cache refresh scheduled, cache refresh succeeded, cache refresh failed, cache hit, cache miss, and stale served where the implementation can detect it.
12. Document TTL defaults and justify them by data volatility and stale-read risk.
13. Model refresh source explicitly:
    - `repository_refresh` as the issue #176 default
    - `event_snapshot` as a future option only for complete versioned event payloads
    - `invalidate_only` for deletes and unsupported proactive warmup

## Non-Functional Requirements

- Domain layer remains framework-free.
- Cache, queue, and metric failures are best effort.
- SQS routing uses existing AWS/LocalStack environment conventions.
- Implementation uses existing directory names and deptrac-collected class types.
- No new bounded-context `Cache`, `ReadModel`, `Policy`, `Registry`, `Scheduler`, `Message`, or `MessageHandler` directories are introduced.
- No cache invalidation methods are added to Domain repository interfaces.
- Direct writes outside this service cannot be detected automatically; they must expose an integration/domain event or invoke an operational cache-clear command.
- Tests use Makefile targets or Docker container execution.
- CI must pass without lowered thresholds.

## Acceptance Criteria Mapping

- Reusable orchestration: shared invalidation command/handler, ODM invalidation listener, abstract subscriber, abstract factory, generic `CacheRefreshCommand`, generic `CacheRefreshCommandHandler`, reusable context handler base, policy DTOs, target DTOs, resolvers, collections, and metrics exist with unit tests.
- Automatic CRUD invalidation: ODM create/update/delete flushes invalidate resolved cache tags after successful writes, including old and new indexed values where relevant.
- Domain-event invalidation plus async recalculation: existing domain-event worker invokes context subscribers for every exposed event that declares cache impact, independent of ODM invalidation.
- Custom repository fallback: write paths that bypass ODM change-set observation call the shared invalidation command after successful write completion.
- Dedupe: duplicate invalidation signals from domain events, ODM changes, and repository fallback paths are idempotent and do not produce unbounded duplicate refresh jobs.
- Queue reuse: one shared `cache-refresh` transport and one `failed-cache-refresh` transport handle the generic refresh command for all contexts.
- Customer first adapter: Customer detail and email lookup refresh through the shared orchestration; collection/reference policies are declared and tag-invalidated.
- Refresh source: Customer uses `repository_refresh`; event-only cache refresh is documented as future-safe only when events carry complete, versioned snapshots.
- LocalStack compatibility: `.env` and `config/packages/messenger.yaml` add cache refresh DSN/transport mirroring existing SQS conventions.
- Writes do not block on rebuild: invalidation listeners and subscribers only schedule work; handlers run separately.
- TTL defaults documented: docs update with policy table and rationale.
- Observability: shared typed EMF metrics and tests.
- Tests: unit coverage for shared orchestration, automatic invalidation, Customer adapter, policy resolution, command dispatch, handler behavior, subscribers, metrics, and integration coverage for post-write warmup.

## Out of Scope

- New reference-data domain events, because ODM listener rules can cover managed reference document invalidation for this issue.
- Event-only cache refresh from current Customer events.
- Full arbitrary collection result materialization for every API Platform filter combination.
- Per-domain cache-refresh queues.
- New infrastructure dashboards or CloudWatch alarms.
- Introducing read models or projections for issue #176.

## Release Notes

This is a backwards-compatible internal behavior change. Existing API contracts remain unchanged.

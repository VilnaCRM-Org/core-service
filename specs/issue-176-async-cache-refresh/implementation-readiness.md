# Implementation Readiness

## Readiness Result

Ready to implement with scoped constraints.

## Strengths

- The existing repository already has the core primitives: Redis tag-aware cache, cache key builder, cached repository decorator, SQS-backed Messenger event bus, LocalStack configuration, EMF metric infrastructure, and cache performance tests.
- The issue can be delivered without API contract changes.
- The reusable foundation can reuse existing class-type directories and deptrac-collected namespaces.
- Automatic CRUD invalidation can use ODM UnitOfWork/change-set data, which avoids adding cache methods to Domain repository interfaces.
- The ODM listener can handle old and new indexed values, so email-change invalidation can be generic and correct.
- The existing domain-event worker already gives a second automatic invalidation signal when business code exposes events.
- Current Customer custom delete methods still operate on managed ODM documents, so they should be covered by the ODM listener without per-entity cache repositories.
- Tests can be deterministic by invoking the shared worker and Customer handler paths directly while keeping Messenger routing under unit/config coverage.

## Gaps and Mitigations

- Generic adopter contract needs to stay feature-neutral.
  - Mitigation: `CacheRefreshCommand` uses context, family, target identifiers, strategy, refresh source, and event metadata only; Customer-specific meaning stays in Customer factory/resolver/handler classes.
- Automatic CRUD invalidation must not become Customer-specific.
  - Mitigation: put the ODM listener in Shared Infrastructure and put Customer mapping in `CustomerCacheInvalidationRuleCollection` and `CustomerCacheInvalidationTagResolver`.
- ODM lifecycle listeners may not observe direct bulk update/delete operations if they bypass UnitOfWork document change sets.
  - Mitigation: classify every custom repository write path. Managed document writes rely on the ODM listener; bulk/direct writes must call the shared invalidation command as a repository fallback after successful write completion.
- Domain-event and ODM signals may overlap for the same write.
  - Mitigation: make tag invalidation idempotent and add deterministic refresh dedupe keys based on context, family, target identifiers, and source event/write operation.
- Generic collection cache warmup is not currently represented by a stable repository query object.
  - Mitigation: declare collection policy and continue invalidating collection tags; implement same-entity refresh for detail/email families in this PR.
- Reference-data mutations do not appear to publish domain events.
  - Mitigation: use ODM listener rules for managed Customer type/status document changes; add domain events later only for business semantics or cross-context reactions.
- Event-only cache refresh from current Customer events is unsafe because those events carry identifiers and emails, not complete Customer snapshots.
  - Mitigation: use `repository_refresh` as the default. Reserve `event_snapshot` for future complete, versioned event payloads with stale-overwrite protection.
- Hit/miss/stale-served metrics may not be fully observable through Symfony cache APIs.
  - Mitigation: emit miss metrics inside cache callbacks and success metrics after returned cache reads; stale-served can be declared and emitted only where detectable.

## Required Validation

- Unit tests:
  - shared command serialization and scalar payload contract
  - shared invalidation command/handler behavior
  - shared worker delegation and failure isolation
  - shared ODM invalidation listener insert/update/delete behavior
  - domain-event subscriber invalidation through the shared command
  - repository fallback invalidation for custom writes that bypass ODM observation
  - invalidation rule and tag resolver behavior
  - shared abstract subscriber/factory/context handler behavior
  - policy DTO/factory/collection/resolver
  - refresh source handling for `repository_refresh`, `event_snapshot`, and `invalidate_only`
  - jitter bounds
  - repository policy usage
  - Customer refresh command creation
  - Customer invalidation rules and old/new email tag resolution
  - Customer custom repository method coverage classification
  - Customer refresh command handler
  - updated Customer subscribers
  - metrics
- Integration tests:
  - automatic CRUD invalidation plus shared worker and Customer handler refresh repopulates detail/email caches
  - exposed domain events invalidate affected tags even when no ODM change-set is available
  - custom repository write paths either trigger ODM invalidation or invoke repository fallback
  - update with email change invalidates previous and current email lookup tags
  - delete invalidates without warming deleted entities
  - refresh command dispatch failure does not break subscriber execution
- Performance/load evidence:
  - `make cache-performance-tests`
  - `make cache-performance-load-tests` if Docker/LocalStack services are available
- Final validation:
  - `make ci`

## Decision

Proceed with implementation. Deliver the shared reusable refresh foundation, domain-event invalidation, automatic ODM CRUD invalidation, repository fallback coverage for custom writes that bypass ODM observation, and Customer as the first adopter. Keep collection/reference full warmup and event-snapshot refresh as explicit follow-ups unless implementation reveals safe existing abstractions and complete event payloads.

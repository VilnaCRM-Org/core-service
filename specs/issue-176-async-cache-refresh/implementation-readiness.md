# Implementation Readiness

## Readiness Result

Ready to implement with scoped constraints.

## Strengths

- The existing repository already has the core primitives: Redis tag-aware cache, cache key builder, cached repository decorator, SQS-backed Messenger event bus, LocalStack configuration, EMF metric infrastructure, and cache performance tests.
- The issue can be delivered without API contract changes.
- The reusable foundation can reuse existing class-type directories and deptrac-collected namespaces.
- Tests can be deterministic by invoking the shared worker and Customer handler paths directly while keeping Messenger routing under unit/config coverage.

## Gaps and Mitigations

- Generic adopter contract needs to stay feature-neutral.
  - Mitigation: `RefreshCacheCommand` uses context, family, target identifiers, strategy, and event metadata only; Customer-specific meaning stays in Customer factory/resolver/handler classes.
- Generic collection cache warmup is not currently represented by a stable repository query object.
  - Mitigation: declare collection policy and continue invalidating collection tags; implement same-entity refresh for detail/email families in this PR.
- Reference-data mutations do not appear to publish domain events.
  - Mitigation: declare reference policy and document the gap; avoid speculative reference refresh triggers unless events are added.
- Hit/miss/stale-served metrics may not be fully observable through Symfony cache APIs.
  - Mitigation: emit miss metrics inside cache callbacks and success metrics after returned cache reads; stale-served can be declared and emitted only where detectable.

## Required Validation

- Unit tests:
  - shared command serialization and scalar payload contract
  - shared worker delegation and failure isolation
  - shared abstract subscriber/factory/context handler behavior
  - policy DTO/factory/collection/resolver
  - jitter bounds
  - repository policy usage
  - Customer refresh command creation
  - Customer refresh command handler
  - updated Customer subscribers
  - metrics
- Integration tests:
  - event invalidation plus shared worker and Customer handler refresh repopulates detail/email caches
  - refresh command dispatch failure does not break subscriber execution
- Performance/load evidence:
  - `make cache-performance-tests`
  - `make cache-performance-load-tests` if Docker/LocalStack services are available
- Final validation:
  - `make ci`

## Decision

Proceed with implementation. Deliver the shared reusable refresh foundation and Customer as the first adopter. Keep collection/reference full warmup as explicit follow-up unless implementation reveals a safe existing abstraction.

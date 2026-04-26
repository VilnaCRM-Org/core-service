# PRD: Async Endpoint Cache Refresh

## Objective

Implement endpoint-grade cache consistency for customer cached query families so writes remain fast, stale reads are bounded by declared policy, and affected cache entries can be warmed by background workers instead of the next user request.

## Functional Requirements

1. Provide typed customer cache policies for cached query families:
   - customer detail by ID
   - customer lookup by email
   - customer collection/search
   - customer reference data
   - negative lookup cache
2. Each policy must expose:
   - family identifier
   - key namespace
   - base tags
   - soft TTL
   - hard TTL
   - jitter
   - consistency class
   - refresh strategy
3. Replace hardcoded customer detail/email TTLs in `CachedCustomerRepository` with resolved cache policies.
4. Split cache pools by customer query family in Symfony cache configuration, while preserving a compatibility alias if existing tests or fixtures still use `cache.customer`.
5. Add `RefreshCustomerCacheCommand` routed through Symfony Messenger to an SQS-backed transport in non-test environments.
6. Add a command handler that refreshes same-entity customer detail and email lookup entries from the inner repository.
7. Update customer create/update/delete cache subscribers so they:
   - invalidate affected tags
   - enqueue same-entity refresh workloads for affected customer detail/email families when useful
   - never break business writes because refresh scheduling fails
8. Add typed EMF metrics for:
   - cache refresh scheduled
   - cache refresh succeeded
   - cache refresh failed
   - cache hit
   - cache miss
   - stale served where the implementation can detect it
9. Document TTL defaults and justify them by CRM data volatility and stale-read risk.

## Non-Functional Requirements

- Domain layer remains framework-free.
- Cache and metric failures are best effort.
- SQS routing uses existing AWS/LocalStack environment conventions.
- Tests use Makefile targets or Docker container execution.
- CI must pass without lowered thresholds.

## Acceptance Criteria Mapping

- Declared policy per cached family: policy DTO/factory/collection/resolver and unit tests.
- Domain-event invalidation plus async recalculation: updated subscribers, refresh command/handler, Messenger routing.
- LocalStack compatibility: `.env` and `config/packages/messenger.yaml` add cache refresh DSN/transport mirroring domain events.
- Writes do not block on rebuild: subscribers only schedule work; handler runs separately.
- TTL defaults documented: docs update with policy table and rationale.
- Observability: typed EMF metrics and tests.
- Tests: unit coverage for policies, command dispatch, command handler, subscribers, metrics, integration coverage for post-event warmup, cache performance target rerun.

## Out of Scope

- New reference-data domain events.
- Full arbitrary collection result materialization for every API Platform filter combination.
- New infrastructure dashboards or CloudWatch alarms.

## Release Notes

This is a backwards-compatible internal behavior change. Existing API contracts remain unchanged.

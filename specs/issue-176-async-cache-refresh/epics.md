# Epics and Stories

## Epic 1: Create Shared Cache Refresh Contract

Story 1.1: Add shared cache-refresh DTOs, resolver interfaces, collections, and metrics.

Acceptance:

- `CacheRefreshPolicy`, `CacheRefreshTarget`, and `CacheRefreshResult` are shared DTOs.
- Policy and target resolver interfaces are shared and context-agnostic.
- Shared metric classes cover scheduled, succeeded, failed, hit, miss, and stale-served lifecycle events.
- Unit tests cover construction, validation, and metric dimensions.

Story 1.2: Add generic command and abstract orchestration classes.

Acceptance:

- Abstract subscriber invalidates tags, resolves targets, dispatches refresh commands, and emits metrics.
- Abstract factory creates scalar, Messenger-safe `CacheRefreshCommand` payloads.
- Generic `CacheRefreshCommandHandler` is the single worker entrypoint for the shared queue.
- Abstract context handler resolves policy and target, refreshes cache through context callbacks, and returns a result.
- Unit tests prove the shared classes are reusable without Customer-specific types.

## Epic 2: Add Shared Queue and Worker Path

Story 2.1: Add cache refresh Messenger routing.

Acceptance:

- Non-test config routes refresh commands to one SQS-backed `cache-refresh` transport.
- Failed jobs route to `failed-cache-refresh`.
- Test config uses deterministic in-memory routing.
- Existing `domain-events` routing remains unchanged.

Story 2.2: Document operational worker usage.

Acceptance:

- Documentation explains the difference between domain-event workers and cache-refresh workers.
- LocalStack variables and runtime variables are documented.
- Per-domain queues are explicitly deferred until metrics justify them.

## Epic 3: Add Customer as the First Adapter

Story 3.1: Declare Customer policies and target resolution using existing directories.

Acceptance:

- Customer uses existing `Application/Factory`, `Infrastructure/Collection`, and `Infrastructure/Resolver` directories.
- Customer detail, lookup, collection, reference, and negative lookup policies are declared.
- `CachedCustomerRepository` uses resolved policies instead of hardcoded TTL literals.

Story 3.2: Connect Customer events to shared refresh orchestration.

Acceptance:

- Customer create/update/delete subscribers extend or delegate to the abstract subscriber.
- Create/update events schedule detail and email refresh work.
- Update with email change handles previous and current email families correctly.
- Delete invalidates and avoids warming deleted entities.
- Scheduling failure is best effort.

Story 3.3: Add Customer refresh command handler adapter.

Acceptance:

- `CustomerCacheRefreshCommandFactory` creates the shared `CacheRefreshCommand` payload without Customer-specific fields in the payload contract.
- `CustomerCacheRefreshCommandHandler` extends or composes the shared abstract context handler.
- The handler warms Customer detail and email lookup entries from persisted state.
- Context-specific logic is limited to target mapping and repository loading.

## Epic 4: Observability and Evidence

Story 4.1: Add shared cache lifecycle metrics.

Acceptance:

- Shared metric classes support context and family dimensions.
- Customer adapter emits the shared metrics with `customer` context.
- Unit tests cover names, units, and dimensions.

Story 4.2: Add integration and performance proof.

Acceptance:

- Integration tests prove post-event cache warmup without a user read doing the expensive refresh.
- Existing cache performance integration and K6 smoke targets are run or a blocker is documented.

## Epic 5: Documentation and CI

Story 5.1: Document reusable cache-refresh architecture.

Acceptance:

- Docs describe shared classes, context adapter responsibilities, TTL defaults, stale-read risk, SQS refresh transport, LocalStack, and metrics.
- Source-tree documentation shows new shared files and Customer adapter files.

Story 5.2: Validate the PR.

Acceptance:

- `make ci` passes or a local environment blocker is documented and GitHub CI is green.
- GitHub PR is updated, CI is green, and review comments are addressed.

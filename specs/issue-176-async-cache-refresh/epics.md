# Epics and Stories

## Epic 1: Create Shared Cache Refresh Contract

Story 1.1: Add shared cache-refresh DTOs, resolver interfaces, collections, and metrics.

Acceptance:

- `CacheRefreshPolicy`, `CacheRefreshTarget`, `CacheRefreshResult`, `CacheInvalidationRule`, and `CacheInvalidationTagSet` are shared DTOs.
- Policy and target resolver interfaces are shared and context-agnostic.
- Invalidation rule and tag resolution contracts are shared and context-agnostic.
- Shared metric classes cover scheduled, succeeded, failed, hit, miss, and stale-served lifecycle events.
- Unit tests cover construction, validation, and metric dimensions.

Story 1.2: Add generic command and abstract orchestration classes.

Acceptance:

- `CacheInvalidationCommand` and `CacheInvalidationCommandHandler` provide one idempotent tag-clearing entrypoint for domain events, ODM changes, and repository fallbacks.
- Abstract subscriber maps exposed domain events to invalidation commands, resolves targets, dispatches refresh commands, and emits metrics.
- Abstract factory creates scalar, Messenger-safe `CacheRefreshCommand` payloads.
- Generic `CacheRefreshCommandHandler` is the single worker entrypoint for the shared queue.
- Abstract context handler resolves policy and target, executes `repository_refresh`, `event_snapshot`, or `invalidate_only`, and returns a result.
- Unit tests prove the shared classes are reusable without Customer-specific types.

Story 1.3: Add shared automatic CRUD invalidation infrastructure.

Acceptance:

- `CacheInvalidationDoctrineEventListener` observes ODM create/update/delete flushes and invalidates after a successful flush.
- The listener uses shared invalidation rule and tag resolver classes rather than per-entity cache repositories.
- Change-set handling supports old and new indexed values, including previous and current email-style tags.
- Listener failures are logged and measured but do not roll back completed writes.
- Unit tests cover insert, update, delete, no-op, failure, and refresh scheduling cases.

Story 1.4: Add repository fallback coverage for custom writes.

Acceptance:

- Custom repository methods are classified as ODM-observed or repository-fallback required.
- Methods that load managed documents and flush are documented as covered by the ODM listener.
- Methods that perform bulk or direct database writes call the shared invalidation command after successful write completion.
- Repository fallback failures are best effort and do not roll back completed writes.
- Unit tests cover at least one fallback path and prove fallback uses the same rules/resolvers as ODM and domain-event invalidation.

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
- Customer policies declare refresh source, with detail and lookup using `repository_refresh`.
- `CachedCustomerRepository` uses resolved policies instead of hardcoded TTL literals.

Story 3.2: Connect Customer CRUD writes to automatic invalidation.

Acceptance:

- `CustomerCacheInvalidationRuleCollection` and `CustomerCacheInvalidationTagResolver` live in existing Infrastructure collection/resolver directories.
- Create/update/delete ODM flushes invalidate ID, email, old-email, and collection tags through the shared listener.
- Create/update writes schedule detail and email refresh work where policy uses `repository_refresh`.
- Update with email change handles previous and current email families correctly from change-set data.
- Delete invalidates and uses `invalidate_only` behavior instead of warming deleted entities.
- Scheduling failure is best effort.

Story 3.2a: Verify Customer custom repository coverage.

Acceptance:

- `MongoCustomerRepository::deleteByEmail()` and `deleteById()` are covered by ODM listener behavior because they load managed documents and delegate deletion.
- `MongoTypeRepository::deleteByValue()` and `MongoStatusRepository::deleteByValue()` are covered by ODM listener behavior because they remove managed documents and flush.
- If any Customer repository method bypasses ODM managed document change sets during implementation, it calls the shared invalidation command as a fallback.
- Tests prove Customer custom delete methods clear the same tags as normal save/delete paths.

Story 3.3: Add Customer refresh command handler adapter.

Acceptance:

- `CustomerCacheRefreshCommandFactory` creates the shared `CacheRefreshCommand` payload without Customer-specific fields in the payload contract.
- `CustomerCacheRefreshCommandHandler` extends or composes the shared abstract context handler.
- The handler warms Customer detail and email lookup entries from persisted state.
- Context-specific logic is limited to target mapping and repository loading.
- Current Customer events are not used for event-only refresh because they do not carry complete Customer cache snapshots.

Story 3.4: Keep Customer event subscribers as automatic domain-event invalidation.

Acceptance:

- Existing Customer create/update/delete subscribers route exposed domain events to the shared invalidation command.
- Subscribers may overlap with ODM invalidation, and that overlap is safe because invalidation is idempotent.
- Refresh scheduling uses deterministic dedupe keys so overlapping domain-event and ODM signals do not create unbounded duplicate jobs.
- Subscriber failures remain isolated from domain-event processing.

## Epic 4: Observability and Evidence

Story 4.1: Add shared cache lifecycle metrics.

Acceptance:

- Shared metric classes support context and family dimensions.
- Customer adapter emits the shared metrics with `customer` context.
- Unit tests cover names, units, and dimensions.

Story 4.2: Add integration and performance proof.

Acceptance:

- Integration tests prove post-write cache warmup without a user read doing the expensive refresh.
- Integration tests prove automatic CRUD invalidation after create/update/delete without a per-entity cache repository.
- Integration tests prove exposed domain events invalidate cache even when the event is the only available signal.
- Integration tests or unit tests prove custom repository fallback coverage for any write path not observed by ODM.
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

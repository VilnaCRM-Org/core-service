# Implementation Readiness

## Readiness Checklist

- [x] Issue requirements reviewed.
- [x] Current `main` event architecture inspected.
- [x] Missing handlers identified.
- [x] Event bus registration behavior checked.
- [x] Test strategy selected.
- [x] Worktree isolated from unrelated local changes.

## Implementation Plan

1. Add status/type created and updated domain events.
2. Inject `EventBusInterface` into the four missing handlers.
3. Publish one event after each save.
4. Add a reference cache invalidation subscriber for the four events.
5. Add reference-domain-event rules to cache invalidation metadata.
6. Update handler tests and add event/subscriber tests.
7. Run focused and integration verification.

## Risks

- Unregistered events can fail under `InMemorySymfonyEventBus`.
- Introducing a factory layer would diverge from the merged customer-event pattern.
- Duplicate cache invalidation can happen because ODM change-set invalidation also exists for reference documents.

## Mitigations

- Register `CustomerReferenceCacheInvalidationSubscriber`.
- Follow the direct event construction style already merged on `main`.
- Use broad invalidate-only tags for reference caches, which is idempotent and matches current reference-data policy.

## Ready For Implementation

Yes. The implementation can proceed without schema changes, migrations, or API contract changes.

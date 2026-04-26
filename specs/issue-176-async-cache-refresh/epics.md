# Epics and Stories

## Epic 1: Declare Cache Policy Contract

Story 1.1: Add typed customer cache policy DTO, factory, collection, and resolver.

Acceptance:

- Policies expose key namespace, tags, soft TTL, hard TTL, jitter, consistency class, and refresh strategy.
- TTL jitter is deterministic enough to test and bounded by policy.
- Unit tests cover policy construction, collection lookup, and resolver behavior.

Story 1.2: Wire policy collection and resolver through Symfony services.

Acceptance:

- TTL/jitter defaults are configurable through parameters/env-backed service arguments.
- Customer detail/email repository cache uses resolved policy values instead of literals.

## Epic 2: Add Async Refresh Pipeline

Story 2.1: Add cache refresh command, command factory, target resolver, and command handler.

Acceptance:

- Subscribers dispatch dedicated refresh commands through Messenger or the configured command path.
- Command handler warms customer detail and email lookup entries from persisted state.
- Command handler logs and emits failure metrics without throwing into business behavior.

Story 2.2: Add SQS runtime and LocalStack local routing.

Acceptance:

- Non-test config routes refresh commands to an SQS-backed transport.
- Test config uses in-memory transport.
- Existing domain event routing remains unchanged.

## Epic 3: Connect Domain Events to Refresh Work

Story 3.1: Update customer create/update/delete cache subscribers.

Acceptance:

- Subscribers invalidate affected tags.
- Create/update events schedule detail and email refresh work.
- Update with email change handles previous and current email families correctly.
- Delete avoids warming deleted entities and keeps invalidation behavior.
- Scheduling failure is best effort.

## Epic 4: Observability and Evidence

Story 4.1: Add typed cache lifecycle metrics.

Acceptance:

- Scheduled, success, failure, hit, miss, and stale-served metrics exist as typed classes.
- Unit tests cover names, units, and dimensions.

Story 4.2: Add integration and performance proof.

Acceptance:

- Integration tests prove post-event cache warmup without a user read doing the expensive refresh.
- Existing cache performance integration and K6 smoke targets are run or a blocker is documented.

## Epic 5: Documentation and CI

Story 5.1: Document cache policies and operations.

Acceptance:

- Docs describe TTL defaults, stale-read risk, SQS refresh transport, LocalStack, and metrics.

Story 5.2: Validate the PR.

Acceptance:

- `make ci` passes.
- GitHub PR is opened, CI is green, and review comments are addressed.

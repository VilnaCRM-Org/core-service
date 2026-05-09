# Epics

## Epic 1: Reference Domain Events

Add domain event classes for customer status/type create and update actions.

Stories:

- Add created events with ID/value payloads.
- Add updated events with ID/current/previous payloads.
- Cover serialization, restoration, and change detection.

## Epic 2: Handler Publication

Publish one domain event from each remaining status/type command handler.

Stories:

- Add `EventBusInterface` to status/type create handlers.
- Add `EventBusInterface` to status/type update handlers.
- Capture previous values for update events.
- Assert `publish()` calls in unit tests.

## Epic 3: Event Consumption

Register a real subscriber for the new reference events.

Stories:

- Add cache invalidation subscriber for reference events.
- Invalidate collection/reference tags.
- Document reference event rules in cache invalidation metadata.

## Epic 4: Verification And PR Readiness

Prove the change is safe enough for review.

Stories:

- Run focused syntax checks.
- Run focused handler/event/subscriber tests.
- Run the full Customer unit suite.
- Lint the Symfony test container.
- Run status/type API integration tests with a disposable compose stack.

# PRD: Command Handler Events For Status And Type

## Objective

Finish issue #32 by adding command-handler domain events for customer status and customer type create/update flows.

## Functional Requirements

1. `CreateStatusCommandHandler` must publish `CustomerStatusCreatedEvent` after saving.
2. `CreateTypeCommandHandler` must publish `CustomerTypeCreatedEvent` after saving.
3. `UpdateStatusCommandHandler` must publish `CustomerStatusUpdatedEvent` after updating and saving.
4. `UpdateTypeCommandHandler` must publish `CustomerTypeUpdatedEvent` after updating and saving.
5. Update events must include the previous value only when it changed.
6. New events must support `eventName()`, `toPrimitives()`, `fromPrimitives()`, event ID, occurred-on timestamp, and typed getters.
7. The event bus must have a registered subscriber for the new events in the test container.
8. Cache invalidation rules must document the new reference-data domain event rules.

## Non-Functional Requirements

- Follow existing domain event implementation style from customer created/updated/deleted events.
- Keep event publishing after repository persistence.
- Avoid new infrastructure dependencies unless required by existing architecture.
- Maintain API integration behavior for status/type endpoints.

## Acceptance Mapping

- Constructor signatures: the four missing handlers now require `EventBusInterface`.
- Events: four new domain event classes cover the missing status/type pairs.
- Publish calls: each updated handler publishes exactly one event.
- Unit tests: handler tests assert `publish()` and validate event payloads.
- Integration safety: status/type API tests exercise the real command bus and sync event bus.

## Intentional Deviation From Original Issue Text

The issue suggested per-event factories and a UUID factory. Main now has merged customer-event handlers that construct domain events directly. This PR completes the missing handler behavior using that established architecture rather than refactoring accepted customer paths into a new pattern.

# Architecture: Command Handler Events

## Existing Pattern

Customer command handlers publish concrete `DomainEvent` subclasses directly after persistence. Event IDs are generated inside event objects, matching `CustomerCreatedEvent`, `CustomerUpdatedEvent`, and `CustomerDeletedEvent`.

## New Domain Events

Created events:

- `CustomerStatusCreatedEvent`
- `CustomerTypeCreatedEvent`

Updated events:

- `CustomerStatusUpdatedEvent`
- `CustomerTypeUpdatedEvent`

Created payloads contain ID and value. Updated payloads contain ID, current value, and nullable previous value. The previous value is omitted by using `null` when there is no actual change.

## Handler Flow

Create handlers:

1. Read entity from command.
2. Save entity through repository.
3. Publish one created event with ID and value.

Update handlers:

1. Capture previous value.
2. Apply update.
3. Save entity through repository.
4. Capture current value.
5. Publish one updated event with previous value only if changed.

## Subscriber

`CustomerReferenceCacheInvalidationSubscriber` subscribes to the four new events and invalidates:

- `customer.collection`
- `customer.reference`

It uses the shared `AbstractCacheInvalidationSubscriber` path, so failures are logged and do not break domain-event processing.

## Cache Rules

`CustomerCacheInvalidationRuleCollection` now exposes reference-data domain-event rules for status/type create/update. These rules use the collection/reference families and invalidate-only refresh source.

## Risk Controls

- Unit tests pin event publication and payload shape.
- The test container is linted for autowiring.
- Status/type API integration tests confirm the real command bus and sync event bus do not fail on unregistered events.

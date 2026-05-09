# Product Brief Distillate

Issue #32 is best completed by filling the remaining status/type event gaps in the command layer, because customer command events already exist on `main`.

The PR adds four reference-data events:

- `CustomerStatusCreatedEvent`
- `CustomerStatusUpdatedEvent`
- `CustomerTypeCreatedEvent`
- `CustomerTypeUpdatedEvent`

Each relevant command handler persists the entity, then publishes exactly one event. A reference-cache subscriber consumes those events and invalidates `customer.collection` plus `customer.reference`, which also prevents sync event bus failures in the test environment.

This keeps the implementation aligned with the merged customer-event architecture instead of introducing a second event factory pattern.

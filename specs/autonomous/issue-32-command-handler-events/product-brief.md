# Product Brief: Command Handler Domain Events

## Problem

Customer status and type command handlers save changes but do not publish domain events. Downstream consumers cannot react to reference-data changes through the event bus.

## Users

- Backend developers adding audit logs, projections, notifications, and integrations.
- QA engineers asserting side effects through published events.
- Operators relying on cache invalidation consistency after reference-data writes.

## Goals

- Publish a command-level domain event after each status/type create or update.
- Preserve the existing customer-event style already merged on `main`.
- Keep the write path synchronous behavior unchanged except for event publication.
- Ensure the sync event bus used in tests has registered subscribers for the new events.

## Non-Goals

- Rebuild all customer event publication around factory classes.
- Introduce a new UUID/event-id service.
- Add delete events for status/type handlers, because issue #32 names only create/update command handlers for those resources.

## Success Criteria

- Four missing handlers publish exactly one reference-data event.
- Event payloads expose resource ID and value changes.
- Reference cache tags are invalidated when these events are consumed.
- Focused unit, customer unit, container lint, and status/type API integration checks pass.

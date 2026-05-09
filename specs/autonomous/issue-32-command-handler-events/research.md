# Research: Issue 32 Command Handler Events

## Source

- GitHub issue: VilnaCRM-Org/core-service#32, "Feature: Add events in CommandHandlers"
- Base branch: `origin/main` at `3f3fed0393e7ccd7be9fba85eeb499237226715c`
- Worktree: `/home/kravtsov/Projects/core-service-issue-32`
- Branch: `feat/issue-32-command-handler-events`

## Current State On Main

Main already contains event publication for the customer aggregate path:

- `CreateCustomerCommandHandler` publishes `CustomerCreatedEvent`.
- `UpdateCustomerCommandHandler` publishes `CustomerUpdatedEvent`.
- `DeleteCustomerCommandHandler` publishes `CustomerDeletedEvent`.
- Customer event subscribers invalidate customer caches and emit metrics.
- Domain event classes create event IDs internally with the existing `DomainEvent` model.

The issue body requested event factory classes and a UUID factory. That pattern is not present in the merged customer-event implementation. The safest implementation for this PR is to complete the missing status/type handler coverage using the event style that is already on `main`.

## Gap

The following handlers were still persistence-only:

- `CreateStatusCommandHandler`
- `CreateTypeCommandHandler`
- `UpdateStatusCommandHandler`
- `UpdateTypeCommandHandler`

Without status/type domain events, subscribers for audit logs, projections, notifications, and reference-cache maintenance have no command-level event source for reference-data changes.

## Important Constraint

`InMemorySymfonyEventBus` throws `EventNotRegisteredException` when publishing an event with no subscriber. The test environment aliases `EventBusInterface` to that sync event bus. New reference events therefore need at least one real subscriber, otherwise API integration tests that create or update status/type records can fail after command publication.

## Decision

Complete issue #32 by adding status/type create/update domain events, publishing exactly one event from each remaining handler, and adding a reference-cache invalidation subscriber for the new events.

The PR intentionally does not refactor existing customer events into factory classes. That would rewrite merged behavior outside the missing-handler gap and add parallel patterns to a code path already accepted by `main`.

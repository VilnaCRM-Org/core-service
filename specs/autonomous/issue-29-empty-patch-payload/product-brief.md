# Product Brief: Issue 29

## BMALPH Stage

- Command surface: `create-brief`

## Objective

Prevent clients from receiving a successful PATCH response when the request did
not contain any mutable field.

## Users

API clients integrating with customer, customer type, and customer status
resources.

## Desired Outcome

An empty PATCH request fails quickly with a clear problem detail:

`PATCH payload must contain at least one supported field.`

## Non-Goals

- Changing PUT validation.
- Changing field-level validators for blank strings or invalid values.
- Reporting no-op diffs when a supplied value equals the current value.

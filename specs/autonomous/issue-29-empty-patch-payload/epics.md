# Epics And Stories: Issue 29

## BMALPH Stage

- Command surface: `create-epics-stories`

## Epic

Reject empty PATCH payloads for customer resources.

## Stories

1. As an API client, when I PATCH a customer with `{}`, I receive `400 Bad Request`
   and a clear explanation instead of a misleading success response.
2. As an API client, when I PATCH a customer status or type with `{}`, I receive
   the same empty payload error.
3. As a maintainer, I can enforce this behavior through one shared guard rather
   than duplicating ad hoc checks in each processor.

## Test Plan

- Unit test the shared guard.
- Unit test each processor rejects a DTO without actionable fields.
- Integration test the three REST PATCH endpoints with `{}` payloads.

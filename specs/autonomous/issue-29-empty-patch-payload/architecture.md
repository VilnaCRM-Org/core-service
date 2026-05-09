# Architecture: Issue 29

## BMALPH Stage

- Command surface: `create-architecture`

## Design

Add a small shared PATCH payload guard in the application validator layer. Each
PATCH processor passes the list of fields that count as actionable for its DTO.

## Components

- `PatchPayloadGuard`
  - Checks whether at least one configured property is non-null.
  - Throws `BadRequestHttpException` with a stable message when no actionable
    field is present.
- `CustomerPatchProcessor`
  - Guards customer update fields before loading or dispatching.
- `CustomerStatusPatchProcessor`
  - Guards `value` before resolving the status.
- `CustomerTypePatchProcessor`
  - Guards `value` before loading or dispatching.

## Tradeoffs

The DTOs do not distinguish an omitted field from an explicit JSON `null`.
Treating all-null PATCH input as empty is acceptable for this bug because those
payloads also provide no actionable update under the current processors.

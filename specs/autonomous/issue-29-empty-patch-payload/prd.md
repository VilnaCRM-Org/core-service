# PRD: Issue 29

## BMALPH Stage

- Command surface: `create-prd`

## Requirement

PATCH processors must reject payloads that do not contain any mutable field.

## Acceptance Criteria

1. `PATCH /api/customers/{ulid}` with `{}` returns `400 Bad Request`.
2. `PATCH /api/customer_statuses/{ulid}` with `{}` returns `400 Bad Request`.
3. `PATCH /api/customer_types/{ulid}` with `{}` returns `400 Bad Request`.
4. The error detail contains `PATCH payload must contain at least one supported field.`
5. Existing successful partial PATCH behavior still works.
6. Existing blank string behavior for status/type remains unchanged.

## Constraints

- Keep the change local to PATCH request validation.
- Avoid a large serializer or DTO redesign for omitted-vs-null distinction.

# Architecture: Issue 29

## BMALPH Stage

- Command surface: `create-architecture`

## Design

Add a small shared PATCH payload guard in the application validator layer. The
customer PATCH request listener decodes REST PATCH payloads before processors
run and passes the list of fields that count as actionable for each resource.

## Components

- `PatchPayloadGuard`
  - Checks whether at least one configured property has a meaningful value.
  - Treats `null` and blank strings as non-actionable by default.
  - Allows resource-specific blank strings only when validation must produce the
    field-level error, such as customer `initials`.
  - Throws `BadRequestHttpException` with a stable message when no actionable
    field is present.
- `CustomerPatchPayloadListener`
  - Enforces the guard at the kernel request layer before processors run.
  - Applies the supported-field and blank-string rules for customer, status, and
    type PATCH requests.
- `CustomerPatchPayloadDenormalizer`
  - Filters unsupported or malformed PATCH keys before DTO denormalization when
    API Platform allows extra attributes.
- `CustomerPatchProcessor`
  - Applies customer updates after request-level payload validation.
- `CustomerStatusPatchProcessor`
  - Applies status updates after request-level payload validation.
- `CustomerTypePatchProcessor`
  - Applies type updates after request-level payload validation.

## Tradeoffs

The DTOs do not distinguish an omitted field from an explicit JSON `null`.
Treating all-null PATCH input as empty is acceptable for this bug because those
payloads also provide no actionable update under the current processors.

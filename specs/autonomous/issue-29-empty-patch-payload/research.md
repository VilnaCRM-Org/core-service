# Research: Issue 29

## BMALPH Stage

- Command surface: `analyst`
- Issue: <https://github.com/VilnaCRM-Org/core-service/issues/29>

## Problem

`PATCH /api/customers/{ulid}` accepts an empty JSON object and returns `200 OK`
while no resource state changes. The issue asks for either an explicit rejection
or a response that clearly reports that no update occurred.

## Current Behavior

- `CustomerPatchProcessor` always resolves the current customer, builds a
  `CustomerUpdate`, and dispatches an update command.
- `CustomerStatusPatchProcessor` and `CustomerTypePatchProcessor` also accept
  payloads with no actionable field. They return the existing resource when the
  `value` field is missing.
- API Platform already exposes `400 Bad Request` problem responses for malformed
  requests, so empty mutable PATCH payloads fit that status code.

## Decision

Use issue option A: reject empty PATCH payloads with `400 Bad Request`.

## Scope

- Customer PATCH rejects when none of `initials`, `email`, `phone`,
  `leadSource`, `type`, `status`, or `confirmed` is present.
- Customer status and type PATCH reject when `value` is absent or null.
- Empty strings keep their existing behavior and validation paths; this change
  targets missing actionable fields, not field-level value validation.

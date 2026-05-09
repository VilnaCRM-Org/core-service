# Product Brief: Infinite Retry Safeguards

## Problem

The failed-domain-events retry path can retry poison messages forever. This protects availability for transient failures, but it also hides permanent message defects and can consume worker capacity indefinitely.

## Users

- Backend developers diagnosing failed async domain events.
- Operators monitoring queue health and dead-letter volume.
- QA engineers validating retry behavior for permanent and transient failures.

## Goals

- Stop retrying deterministic permanent failures in the infinite retry strategy.
- Continue retrying transient and unknown failures indefinitely.
- Route permanent failures from `failed-domain-events` to a terminal DLQ.
- Emit business metrics for retry attempts and DLQ routings.
- Document the retry taxonomy and queue flow.

## Non-Goals

- Replace Messenger's retry system.
- Add a full circuit breaker implementation in this PR.
- Change the bounded retry policy on the primary `domain-events` transport.
- Change cache refresh retry behavior.

## Success Criteria

- `InfiniteRetryStrategy::isRetryable()` returns `false` for permanent failures.
- `InfiniteRetryStrategy::isRetryable()` returns `true` for transient and unknown failures.
- Permanent failures on `failed-domain-events` route to `dead-domain-events`.
- Retry and DLQ metric objects expose stable CloudWatch dimensions.
- Focused unit tests, container lint, Psalm, PHPMD, and configuration validation pass.

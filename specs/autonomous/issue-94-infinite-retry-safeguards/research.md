# Research: Issue 94 Infinite Retry Safeguards

## Source

- GitHub issue: VilnaCRM-Org/core-service#94, "Feature Request: InfiniteRetryStrategy Safeguards"
- Base branch: `origin/main` at `3f3fed0393e7ccd7be9fba85eeb499237226715c`
- Worktree: local development worktree (path omitted)
- Branch: `fix/issue-94-infinite-retry-safeguards`

## Current State On Main

`InfiniteRetryStrategy` is configured for the `failed-domain-events` Messenger transport. It currently returns `true` for every throwable and therefore retries every failed message forever with a fixed delay.

Main already has a two-stage domain event queue flow:

- `domain-events` has bounded retries and routes exhausted messages to `failed-domain-events`.
- `failed-domain-events` uses `InfiniteRetryStrategy`.
- `failed-domain-events` had no terminal failure transport.

The observability model is already present through `BusinessMetric`, `BusinessMetricsEmitterInterface`, and existing EMF infrastructure. That makes retry/DLQ metrics a local extension rather than a new observability stack.

## Gap

The current implementation cannot distinguish transient failures from permanent poison messages. Validation, decoding, schema, and type errors can retry forever even though retrying them is unlikely to make progress.

The queue topology also lacks a terminal DLQ for failures that the infinite retry strategy decides are unsafe to retry.

## Exception Taxonomy

Permanent failures should include exceptions that represent deterministic payload, schema, programmer, or validation defects:

- `DomainException`
- `InvalidArgumentException`
- `JsonException`
- `LogicException`
- Messenger message decoding failures
- Messenger validation failures
- Symfony serializer failures
- Symfony validator failures
- `TypeError`
- `ValueError`

Transient or unknown failures should remain retryable to preserve AP behavior for infrastructure outages, transport failures, and temporary downstream unavailability.

## Decision

Keep the existing strategy service and add selective retry logic inside it. This preserves the current Messenger integration point while adding a permanent-failure branch that emits a DLQ metric and returns `false`.

Add a terminal `dead-domain-events` transport as the failure transport for `failed-domain-events`. Messenger can then route non-retryable poison messages to a queue instead of dropping them.

Metric emission is best effort. Retry decisions must not depend on the metrics backend.

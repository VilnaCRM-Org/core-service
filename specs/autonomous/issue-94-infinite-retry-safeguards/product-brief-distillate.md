# Product Brief Distillate: Infinite Retry Safeguards

## Core Outcome

Preserve AP behavior for recoverable async domain-event failures while preventing deterministic poison messages from retrying forever.

## Minimal Viable Change

- Classify permanent failures in `InfiniteRetryStrategy`.
- Return `false` for permanent failures and `true` otherwise.
- Add `dead-domain-events` as the terminal DLQ after `failed-domain-events`.
- Emit one metric for retry attempts and one metric for DLQ routing.
- Document the taxonomy next to the strategy.

## Risk Controls

- Unknown exceptions remain retryable by default.
- Metrics failures are swallowed so observability cannot alter delivery behavior.
- Tests cover direct and wrapped permanent failures.
- The existing service ID remains unchanged for Messenger wiring.

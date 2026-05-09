# Architecture: Infinite Retry Safeguards

## Components

### `InfiniteRetryStrategy`

The existing Messenger retry strategy remains the integration point for `failed-domain-events`.

Responsibilities added:

- Walk the throwable chain.
- Match throwable classes against the permanent failure taxonomy.
- Emit retry or DLQ metrics.
- Return the Messenger retry decision.

### Metrics

New business metrics use the existing observability model:

- `RetryAttemptMetric`
- `DlqRoutingMetric`
- `RetryStrategyMetricDimensions`

The metrics are intentionally small value objects. They do not depend on Messenger infrastructure and can be tested independently.

### Messenger Transports

Queue flow after the change:

1. `domain-events`
2. `failed-domain-events`
3. `dead-domain-events`

`dead-domain-events` is terminal. It exists for manual inspection, replay tooling, or operational remediation outside this PR.

## Error Handling

Metric emission is wrapped in a broad `Throwable` catch. Observability is useful but must not become part of the delivery contract for retry decisions.

## CAP/AP Position

The AP behavior is preserved for unknown and transient failures because they remain retryable indefinitely. The new permanent-failure path is only for deterministic failures where repeated retries cannot restore availability.

## Extension Points

Future work can add:

- A dedicated exception marker interface for permanent message failures.
- Replay tooling for `dead-domain-events`.
- Circuit breaker controls around repeated transient failures.
- Dashboards and alerts on `MessengerDlqRoutings`.

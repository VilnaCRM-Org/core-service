# Retry Strategy

`InfiniteRetryStrategy` is used on the `failed-domain-events` transport. It preserves availability for transient failures while preventing permanent poison messages from cycling forever.

## Transport Flow

1. `domain-events` handles async domain events.
2. After the normal retry policy is exhausted, messages move to `failed-domain-events`.
3. `failed-domain-events` uses `InfiniteRetryStrategy`.
4. If the failure is classified as permanent, the message is not retried and Messenger routes it to `dead-domain-events`.

## Transient Failures

Transient failures continue to retry indefinitely with the configured delay. Examples:

- SQS or network transport failures
- Service unavailable responses
- Timeout-like infrastructure failures
- Unknown runtime exceptions without a permanent root cause

Unknown exceptions are treated as retryable by default to preserve AP behavior.

## Permanent Failures

Permanent failures are not retried. They are routed to the terminal DLQ because retrying them is not expected to make progress.

Current permanent taxonomy:

- `DomainException`
- `InvalidArgumentException`
- `JsonException`
- `LogicException`
- `MessageDecodingFailedException`
- Messenger validation failures
- Symfony serializer exceptions
- Symfony validator failures
- `TypeError`
- `ValueError`

Wrapped failures are inspected through the exception chain, so a `HandlerFailedException` caused by a `TypeError` is treated as permanent.

## Observability

The strategy emits business metrics through `BusinessMetricsEmitterInterface`:

- `MessengerRetryAttempts` for retryable failures
- `MessengerDlqRoutings` for permanent failures routed to DLQ

Metric dimensions:

- `Endpoint=Messenger`
- `Operation=retry` or `Operation=dlq`
- `MessageType`
- `ExceptionType`

Metric emission is best effort. A metrics backend failure never changes the retry decision.

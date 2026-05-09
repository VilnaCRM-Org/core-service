# PRD: Infinite Retry Strategy Safeguards

## Functional Requirements

1. The retry strategy must classify permanent failures.
2. Permanent failures must return `false` from `isRetryable()`.
3. Transient and unknown failures must return `true` from `isRetryable()`.
4. Wrapped permanent failures must be detected through the exception chain.
5. The failed domain-event transport must have a terminal DLQ.
6. Retryable decisions must emit a retry-attempt metric.
7. Permanent-failure decisions must emit a DLQ-routing metric.
8. Metrics emission failures must not change retry decisions.
9. The taxonomy and transport flow must be documented.

## Permanent Failure Taxonomy

- Domain rule failures
- Invalid input and argument failures
- Invalid JSON or message decoding failures
- Logic/programmer errors
- Messenger and Symfony validation failures
- Serializer failures
- PHP type/value errors

## Availability Requirement

Unknown failures must remain retryable. The implementation must avoid turning a newly introduced transient exception into message loss.

## Configuration Requirements

- Add `DEAD_DOMAIN_EVENTS_TRANSPORT_DSN`.
- Add `DEAD_DOMAIN_EVENTS_QUEUE_NAME` for local SQS-style configuration.
- Configure `failed-domain-events.failure_transport` to `dead-domain-events`.
- Configure the test environment with an in-memory `dead-domain-events` transport.

## Observability Requirements

`MessengerRetryAttempts` dimensions:

- `Endpoint=Messenger`
- `Operation=retry`
- `MessageType`
- `ExceptionType`

`MessengerDlqRoutings` dimensions:

- `Endpoint=Messenger`
- `Operation=dlq`
- `MessageType`
- `ExceptionType`

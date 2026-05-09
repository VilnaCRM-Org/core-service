# Epics: Infinite Retry Safeguards

## Epic 1: Retry Classification

- Add permanent-failure taxonomy.
- Inspect wrapped exceptions.
- Preserve retry behavior for unknown failures.

## Epic 2: Terminal DLQ

- Add dead-domain-events transport.
- Wire failed-domain-events to the terminal DLQ.
- Add environment variables for local and test environments.

## Epic 3: Observability

- Add retry-attempt metric.
- Add DLQ-routing metric.
- Add dimensions for endpoint, operation, message type, and exception type.
- Keep metrics best-effort.

## Epic 4: Documentation And Verification

- Document retry strategy behavior and taxonomy.
- Add unit coverage for retryable, permanent, wrapped, and metrics-failure paths.
- Add metric value-object tests.
- Run focused tests and static checks.

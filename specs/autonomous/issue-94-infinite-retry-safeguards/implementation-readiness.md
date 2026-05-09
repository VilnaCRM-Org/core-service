# Implementation Readiness: Infinite Retry Safeguards

## Readiness

Ready for implementation.

## Dependencies

- Symfony Messenger retry strategy interface already exists in the application.
- Business metrics infrastructure already exists.
- Messenger transports are configured in `config/packages/messenger.yaml`.

## Known Constraints

- Unknown exceptions must stay retryable to preserve AP behavior.
- The strategy service ID must stay stable because Messenger references it directly.
- Metrics must not make retry decisions fail.
- Test environment must define all new transport DSNs.

## Test Plan

- Unit test `InfiniteRetryStrategy` for retryable unknown exceptions.
- Unit test transient transport exceptions.
- Unit test permanent exception taxonomy.
- Unit test wrapped permanent exceptions.
- Unit test metrics-emitter failure handling.
- Unit test retry and DLQ metric dimensions.
- Lint Symfony container to verify constructor and transport config.
- Run Psalm and PHPMD.
- Run configuration validation.

## Rollout Notes

Deployments must provide `DEAD_DOMAIN_EVENTS_TRANSPORT_DSN`. Local development uses the new `DEAD_DOMAIN_EVENTS_QUEUE_NAME` value from `.env`.

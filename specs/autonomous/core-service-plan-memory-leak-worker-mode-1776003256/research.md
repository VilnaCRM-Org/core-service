# Research: Memory-Leak Regression Baseline for Worker-Mode Readiness

## Goal

Define the research baseline for adding memory-leak regression tests before a future FrankenPHP worker-mode rollout. This document stays at research stage only. It does not define final implementation, CI policy, or hard thresholds.

## Current Evidence

- The current runtime is `php-fpm` on PHP 8.4 Alpine with a separate Caddy image; the container entrypoint still ends with `CMD ["php-fpm"]`, so the service is not running FrankenPHP today.
- The service uses DDD, CQRS, and event-driven patterns, so long-lived-process risk is not limited to HTTP request handling.
- Two relevant harnesses already exist: PHPUnit in CI and K6 for REST load scenarios.
- `DomainEventMessageHandler` and the `CustomerCreated*Subscriber` classes explicitly describe Symfony Messenger worker execution, making them the closest existing proxy for long-lived service reuse.
- Current unit coverage for `DomainEventMessageHandler` validates behavior, continuation on failure, and logging/metrics paths, but it does not validate repeat-invocation memory stability.

## Why This Matters Before FrankenPHP

FrankenPHP worker mode changes the memory model: application services and userland state can survive across requests. Current `php-fpm` request loops are only a partial proxy because they do not preserve the Symfony kernel/service lifecycle in the same way. Existing Messenger workers are a better near-term proxy because they already reuse process state across many messages.

## Candidate Leak-Risk Surfaces

1. Repeated async event handling in `DomainEventMessageHandler`, especially when the same handler and subscriber instances process many envelopes.
2. Event subscribers that interact with shared services such as cache, logging, and metrics emission, including `CustomerCreatedCacheInvalidationSubscriber` and `CustomerCreatedMetricsSubscriber`.
3. Repeated HTTP request handling under a future persistent worker runtime.
4. Failure paths that allocate exceptions, logging context arrays, or metric objects on every iteration.

## Baseline Research Position

- PHPUnit should be the primary first-generation leak-regression harness because it can run deterministic loops in one PHP process and measure memory directly.
- K6 should be the secondary harness for request-shaped workloads, but only when paired with external worker or container memory sampling; K6 alone is not a leak detector.
- The first regression candidates should target async worker-style execution before HTTP worker-mode execution, because that path already exists today and is closer to long-lived application-state reuse than `php-fpm` request execution.

## Proposed Research Matrix

| Scenario | Harness | Signal | Research value |
| --- | --- | --- | --- |
| Repeated happy-path async event handling | PHPUnit | heap delta, post-warmup slope, peak memory | Highest-fidelity current proxy for long-lived Symfony services |
| Repeated async failure path | PHPUnit | growth under repeated exceptions and metric-failure handling | Validates resilience paths do not accumulate leaked state |
| Low-noise request loop such as `/api/health` | K6 + external sampler | worker or container RSS trend | Establishes black-box HTTP baseline with minimal domain noise |
| Mixed request loop using existing customer scenarios | K6 + external sampler | RSS trend under serializer, cache, and logging churn | Reuses current workload assets for future FrankenPHP comparison |

## Measurement Principles

- Separate warm-up from measurement; first-use allocations, autoloading, and cache priming must not be treated as leaks.
- Prefer steady-state criteria over a single absolute memory number.
- Record memory in batches, not only at the end: initial, post-warmup, every N iterations, final, and peak.
- For PHPUnit research, use in-process measures such as `memory_get_usage(true)` and `memory_get_peak_usage(true)` and force periodic GC during calibration to distinguish retained memory from collector lag.
- For K6 research, measure PHP worker or container RSS externally; latency and throughput are supporting context, not pass or fail criteria.

## Initial Research Hypotheses

1. The async domain-event path is the best first leak-regression target because it already runs in long-lived Symfony Messenger workers.
2. Cache invalidation and metrics emission subscribers are representative starter cases because they exercise shared services rather than pure domain logic.
3. Current `php-fpm` request-loop measurements can provide a useful baseline, but they are insufficient to prove readiness for FrankenPHP worker mode on their own.
4. Leak thresholds should be calibrated empirically on stable runners before becoming blocking CI gates.

## Known Gaps From Current Evidence

- The architecture docs appear incomplete for current domain-event usage; code shows `CustomerCreatedEvent` subscribers while the design doc only lists a health-check event.
- The K6 README documents workload execution, not worker-memory capture or leak-specific acceptance criteria.
- Current GitHub workflows show PHPUnit and Symfony checks, but no leak-specific job or load-test job.

## Recommended Output for the Next Phase

The next artifact set should decide:

- which exact async scenarios become the first PHPUnit leak baselines,
- how memory growth is sampled and normalized,
- whether early K6 memory runs are informational or gating,
- and what runner or environment contract is required before CI enforcement is credible.

## Out of Scope

- Implementing tests
- Adopting FrankenPHP
- Defining final thresholds
- General performance benchmarking unrelated to memory retention

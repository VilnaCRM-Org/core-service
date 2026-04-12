# Architecture: Memory-Leak Regression Coverage for Worker-Mode Readiness

## Architecture Goal

Design a repository-native testing strategy that detects memory-retention regressions in long-lived execution paths before FrankenPHP worker mode is enabled, while staying compatible with the current Symfony and `php-fpm` architecture.

## Current-State Alignment

- Runtime today: `php-fpm` + Caddy, not FrankenPHP.
- Highest-fidelity current proxy for long-lived state reuse: async domain-event processing in Symfony Messenger worker-style execution.
- Existing automation surfaces: PHPUnit, Behat, Bats, K6, GitHub Actions, and `make ci`.
- Existing risk hotspots already visible in code: `DomainEventMessageHandler`, customer event subscribers, and their shared-service interactions for cache invalidation, logging, and metrics emission.

## Key Architectural Decisions

### 1. Use a Two-Layer Testing Strategy

- **Layer A: deterministic async-worker memory regression**
  This becomes the first blocking signal because it exercises a real long-lived execution path that already exists in the repository.
- **Layer B: HTTP memory evidence for future worker mode**
  This remains informational until FrankenPHP worker mode exists locally and in CI.

### 2. Keep the First Blocking Signal Inside PHPUnit

The first regression suite should live in the repository's PHP test stack so it can:

- run in a single process,
- sample memory directly with low orchestration overhead,
- reuse existing test fixtures and stubs,
- and integrate with current CI entrypoints without introducing a new critical dependency.

### 3. Treat K6 as a Workload Driver, Not a Leak Detector

K6 is valuable for repeated request workloads, but it cannot prove PHP worker memory retention by itself. It should be paired with external worker or container memory sampling and should begin as supporting evidence rather than the primary blocker.

### 4. Calibrate Before Enforcing

The implementation must separate:

- scenario construction,
- measurement mechanics,
- baseline calibration,
- and final blocking thresholds.

That avoids brittle CI and lets the team validate noise levels before converting evidence into a gate.

## Proposed Solution Components

### A. Memory Sampling Utilities

Introduce a small test-support layer responsible for:

- collecting `memory_get_usage(true)` and `memory_get_peak_usage(true)`,
- recording per-batch checkpoints,
- running controlled GC checkpoints during calibration,
- and formatting assertion failures with readable, redacted diagnostics that exclude business payloads and customer PII.

**Likely future location**

- `tests/Integration/Memory/Support/` or a similarly isolated integration-test support namespace.

### B. Async Worker-Path Regression Scenarios

Implement deterministic loop-based scenarios around `DomainEventMessageHandler` with representative subscribers.

**Initial coverage set**

1. Happy-path event processing with cache invalidation and metrics emission.
2. Failure-path subscriber execution that triggers logging and metric-failure handling.
3. Metric-emission failure path to ensure resilience logic does not accumulate retained state.

### C. HTTP-Oriented Memory Evidence

Reuse existing request workloads later through:

- a low-noise endpoint such as health check for baseline sampling,
- and one mixed customer workload for serializer, cache, and logging churn.

These scenarios should run only once a reliable worker or container memory sampler is defined for the target runtime path.

### D. Makefile and CI Integration

The future implementation should expose:

- a dedicated Make target for the blocking async-worker memory suite,
- an optional Make target for informational HTTP memory evidence,
- and a GitHub Actions job strategy that keeps blocking and informational signals separate.

## Scenario Design

### Blocking Scenario Group 1: Async Happy Path

- Repeatedly invoke `DomainEventMessageHandler` with representative event envelopes.
- Use stable fixtures and identical iteration counts.
- Assert that post-warmup retained memory growth stays within calibrated limits.

### Blocking Scenario Group 2: Async Failure Path

- Repeatedly execute scenarios that throw inside subscribers.
- Repeatedly execute scenarios where metric emission fails after subscriber failure.
- Validate that failure-path allocations do not produce monotonic retained growth.

### Informational Scenario Group 3: HTTP Baseline

- Drive repeated health-check requests.
- Later add repeated mixed customer request sequences from existing K6 assets.
- Sample worker or container RSS externally and compare against the async baseline and future FrankenPHP runs.

## Measurement Model

Every scenario should define:

- warm-up iterations,
- measured iterations,
- sample interval,
- memory metrics captured,
- and assertion rule.

### Recommended Signal Shape

- Baseline memory before warm-up
- Memory after warm-up
- Periodic retained-memory checkpoints
- Final retained memory delta
- Peak memory during run

### Recommended Assertion Style

Prefer rules such as:

- retained delta after warm-up stays below a calibrated ceiling,
- no sustained upward slope beyond a calibrated tolerance,
- and peak memory does not exceed a scenario-specific budget without returning to steady state.

## Candidate Repository Touchpoints

- `tests/Integration/` for the blocking memory-regression harness
- `tests/Load/` for informational HTTP memory evidence reuse
- `Makefile` for execution entrypoints
- `.github/workflows/` for CI integration
- `docs/` for developer execution and rollout guidance

## Risks and Mitigations

- **Risk:** CI flakiness from noisy absolute thresholds
  **Mitigation:** calibrate first; prefer steady-state growth rules over one-shot numbers.

- **Risk:** over-scoping the first implementation
  **Mitigation:** make async-worker regression the only initial blocker and keep HTTP evidence informational.

- **Risk:** false confidence from `php-fpm` request loops
  **Mitigation:** explicitly document that HTTP baselines are comparison evidence, not sufficient proof of worker-mode readiness.

- **Risk:** documentation drift around event-driven architecture
  **Mitigation:** treat implementation code and tests as the source of truth during execution and update docs alongside the future implementation.

## Recommended First Implementation Boundary

The first implementation should end when the repository has:

1. a blocking async-worker memory-regression suite,
2. a documented measurement policy and calibration approach,
3. Make and CI entrypoints for the blocking suite,
4. and a defined informational path for future HTTP and FrankenPHP comparison.

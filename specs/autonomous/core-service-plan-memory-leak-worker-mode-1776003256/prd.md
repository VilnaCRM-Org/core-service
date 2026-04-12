# Product Requirements Document: Memory-Leak Regression Testing for Worker-Mode Readiness

## Overview

This PRD defines the requirements for adding memory-leak regression coverage to core-service before FrankenPHP worker mode is enabled. The solution must create a reliable readiness signal for long-lived execution paths while fitting the repository's current Symfony, API Platform, DDD, CQRS, and CI conventions.

## Background

Core-service currently runs on `php-fpm`, which resets request state between requests. FrankenPHP worker mode will preserve application state across requests and can expose memory retention issues that current correctness tests do not detect. The repository already contains a worker-style execution path in async domain-event handling, making it the best current proxy for long-lived service reuse.

## Goals

- Detect memory-retention regressions before worker mode is introduced.
- Start with deterministic worker-style scenarios that already exist in the codebase.
- Define a staged path toward HTTP-oriented worker-mode evidence without requiring the runtime migration in the same change.
- Make the future implementation runnable locally and automatable in CI.

## Functional Requirements

- `FR1`: The repository must provide a repeatable memory-regression harness that can execute many iterations in a single PHP process.
- `FR2`: The first implementation must cover at least one happy-path async domain-event scenario using `DomainEventMessageHandler`.
- `FR3`: The first implementation must cover at least one failure-path async domain-event scenario where subscriber execution or metric emission fails repeatedly.
- `FR4`: The harness must distinguish warm-up behavior from measurement behavior and must not treat first-use allocations as leaks.
- `FR5`: The harness must capture memory checkpoints across the run rather than only a final number.
- `FR6`: The test output must clearly report scenario name, iteration counts, baseline memory, post-warmup samples, final memory, peak memory, and failure reason.
- `FR7`: The repository must define a path for HTTP-oriented memory evidence so that future FrankenPHP worker-mode behavior can be compared against the baseline.
- `FR8`: The repository must expose the future implementation through documented Makefile entrypoints and CI wiring.
- `FR9`: The implementation plan must define which checks are blocking and which are informational during the initial rollout.

## Non-Functional Requirements

- `NFR1`: The first blocking suite must be deterministic enough to run in CI with low flake risk.
- `NFR2`: Measurements must be reproducible on Linux-based Docker and GitHub Actions environments used by the repository.
- `NFR3`: The memory-regression signal must favor steady-state growth analysis over a single absolute value.
- `NFR4`: Thresholds must be calibrated on representative runners before becoming hard merge blockers.
- `NFR5`: Test runtime must remain bounded so the suite can be adopted without materially destabilizing existing CI duration.
- `NFR6`: Logs and test artifacts must avoid leaking business payloads or other sensitive data.
- `NFR7`: The implementation must preserve existing architecture and quality standards and must not lower any CI thresholds.
- `NFR8`: The design should prefer the simplest repository-native tools available before introducing new external tooling.

## Assumptions

- The team intends to enable FrankenPHP worker mode later, but not in this delivery.
- Messenger worker-style execution is the best currently available proxy for long-lived process reuse.
- Existing K6 assets are still useful, but only when paired with direct worker or container memory sampling.
- The first implementation can phase black-box HTTP memory evidence after the deterministic async-worker suite is in place.

## Dependencies

- Existing PHPUnit infrastructure and repository test conventions.
- Existing async domain-event path and customer event subscribers.
- Future agreement on where HTTP-oriented memory sampling belongs in CI.

## Out of Scope

- Switching the runtime from `php-fpm` to FrankenPHP.
- Tuning business logic or infrastructure solely for performance.
- Reworking the general K6 load-test strategy beyond what memory evidence requires.

## Acceptance Criteria

1. A future implementation built from this PRD can add a blocking async-worker memory-regression suite without depending on FrankenPHP.
2. The plan explicitly defines at least one future informational HTTP memory evidence path for later worker-mode comparison.
3. The repository has a documented rollout policy that separates calibration from enforcement.
4. The plan identifies concrete current code paths, harnesses, and CI integration points instead of describing leak testing generically.

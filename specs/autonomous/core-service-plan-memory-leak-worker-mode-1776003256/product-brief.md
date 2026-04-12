# Product Brief: Memory-Leak Regression Coverage Before FrankenPHP Worker Mode

## Opportunity

The core-service team wants to enable FrankenPHP worker mode in the future, but worker mode changes request lifecycle assumptions and increases the risk that retained state, caches, or service references leak memory across iterations. The repository already validates correctness through PHPUnit, Behat, and K6, yet it has no regression coverage for memory stability in long-lived execution paths.

## Problem Statement

Today, the service still runs on `php-fpm`, so memory is naturally reset between requests. That makes current HTTP correctness tests insufficient as a readiness signal for a persistent worker runtime. The repository does contain an existing long-lived execution proxy in Symfony Messenger workers, particularly in `DomainEventMessageHandler` and customer event subscribers, but the current tests only prove behavior and resilience, not steady-state memory usage.

Without explicit regression coverage, worker mode could be enabled with hidden memory-retention defects that only appear under sustained traffic, increasing restart frequency, degrading throughput, and making rollback decisions reactive instead of evidence-driven.

## Target Audience

- Backend maintainers who will implement and own worker-mode readiness.
- Platform and release owners who need a merge and rollout gate before enabling FrankenPHP worker mode.
- Reviewers and operators who need credible, repeatable evidence that memory behavior is stable enough to proceed.

## Desired Outcomes

1. Establish a repository-native way to detect memory-retention regressions in long-lived execution paths.
2. Cover the highest-risk current proxy path first: repeated async domain-event processing in worker-style execution.
3. Prepare an HTTP-oriented comparison path that can later be used against FrankenPHP worker mode without requiring the migration in the first implementation.
4. Define how the memory-regression evidence becomes runnable locally, visible in CI, and usable as a worker-mode rollout gate.

## In Scope

- Planning the deterministic test harnesses and scenarios needed for memory-regression coverage.
- Defining measurement rules for warm-up, sampling cadence, and leak-detection assertions.
- Selecting the first gating scenarios from current repository behavior.
- Defining Makefile, CI, and reporting expectations for the future implementation.

## Out of Scope

- Enabling FrankenPHP worker mode.
- Replacing the current `php-fpm` runtime.
- General performance optimization unrelated to memory retention.
- Broad load-testing redesign outside the needs of memory-regression evidence.

## Success Metrics

- The repository has a defined, repeatable memory-regression test strategy for long-lived execution paths before worker mode is introduced.
- The first implementation plan covers at least one happy-path async scenario and one failure-path async scenario.
- The team can run the same memory-regression suite locally and in CI with consistent measurement rules.
- Future FrankenPHP rollout decisions can rely on an explicit evidence bundle instead of ad hoc debugging.

## Constraints

- The current repo does not yet run FrankenPHP, so the first gating signal must come from a high-fidelity proxy rather than the target runtime itself.
- CI duration and flake budget must remain reasonable; heavyweight black-box memory testing cannot become a blind extension of the default test pipeline.
- The project enforces strict CI and test-quality standards, so the future implementation must integrate without weakening thresholds.
- Existing architecture docs lag behind code in some event-driven areas, so the plan should treat the running code and current tests as the primary source of truth.

## Risks

- Choosing only `php-fpm` request loops as the first signal would underrepresent worker-mode risk.
- Treating K6 alone as a leak detector would produce noisy evidence without direct memory sampling.
- Hard-coding thresholds before calibration would create brittle CI and false positives.
- Adding a memory test plan that is too broad initially could delay adoption and reduce trust in the signal.

## Product Decision

The plan should intentionally ship in two layers:

1. A deterministic, PHPUnit-based memory-regression harness for the existing async worker-style path as the first blocking safety net.
2. An HTTP-oriented memory evidence path for future FrankenPHP comparison, initially informational until the target runtime exists in the repository.

## Why Now

Adding the regression coverage first de-risks the eventual worker-mode rollout and creates a stable baseline while the service is still on `php-fpm`. That makes the future runtime migration a controlled change with evidence, rather than a combined runtime-and-testing leap.

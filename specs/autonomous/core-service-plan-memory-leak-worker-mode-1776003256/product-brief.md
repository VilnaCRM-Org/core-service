# Product Brief: Move Core Service From PHP-FPM to FrankenPHP Worker Mode Safely

## Opportunity

Core Service can reduce bootstrap overhead and improve request efficiency by
migrating from `php-fpm` to FrankenPHP worker mode. The value proposition is
clear: keep the Symfony application booted, reuse the container, and stop paying
full bootstrap cost on every request.

The risk is equally clear: a reused Symfony kernel turns the application into a
long-running PHP process. Mutable service state, Doctrine ODM references,
serializer caches, log context, or third-party client state can leak across
requests or grow without bound unless the migration is designed explicitly for
worker semantics.

## Problem Statement

The current runtime model assumes request isolation because the application runs
behind `php-fpm`. That assumption breaks under FrankenPHP worker mode.

We therefore have two linked problems to solve in the same plan:

1. adopt FrankenPHP worker mode as the target runtime model, and
2. add endpoint-wide memory-safety coverage so request-to-request state
   contamination and memory growth are caught before rollout.

Correctness tests alone are not enough. Traditional request-isolated PHP
assumptions are no longer sufficient.

## Goals

- Safely adopt FrankenPHP worker mode for this Symfony application.
- Prevent request-to-request state contamination in long-lived workers.
- Detect memory leaks and retained objects early in CI and staging.
- Cover the full documented REST and GraphQL endpoint inventory with
  repeated-request memory-safety tests.
- Define operational safeguards for imperfect legacy code and third-party
  dependencies.
- Keep the design compatible with the repository's current Symfony, PHPUnit,
  Docker, Make, and GitHub Actions workflows.

## Non-Goals

- Guarantee mathematically perfect zero memory growth in every dependency.
- Rewrite the whole application before the migration.
- Treat MAX_REQUESTS as the only solution to memory leaks.
- Rely only on ad hoc manual profiling.
- Make `roave/no-leaks` the primary worker-mode safety strategy.

## Target Audience

- Backend maintainers implementing the runtime migration and reset strategy
- Platform and release owners deciding when worker mode is safe to enable
- Reviewers who need concrete acceptance criteria before approving rollout
- Operators who need observability and rollback criteria during staging and
  production rollout

## In Scope

- Rewriting the existing BMAD planning bundle so FrankenPHP worker mode is the
  target architecture.
- Specify the worker request lifecycle, cleanup requirements, and restart fuse.
- Audit mutable services and define `ResetInterface` requirements.
- Establish an endpoint-wide memory-safety test layer for all current REST and
  GraphQL operations.
- Add supporting `KernelTestCase` leak checks for high-risk shared-service and
  async flows.
- Define CI, staging soak, rollout, and rollback policy for the migration.

## Out of Scope

- Implementing the runtime migration in this change set
- Rewriting unrelated application areas that are not needed for worker safety
- Locking numeric leak thresholds before baseline measurements exist
- Broad performance tuning unrelated to long-running-worker safety

## Success Metrics

- FrankenPHP worker mode remains blocked until the BMAD bundle defines it as a
  first-class architectural constraint, not a future afterthought.
- Worker mode remains blocked until every documented REST endpoint and GraphQL
  operation is covered by same-kernel repeated-request tests, and those tests
  pass using project-specific thresholds derived from baseline calibration
  rather than invented values.
- Worker mode remains blocked until supporting high-risk leak checks for shared
  services and async-style execution paths, including
  `DomainEventMessageHandler` happy and failure flows when present, are defined
  as co-blocking signals and pass in CI.
- Worker mode remains blocked until mutable services have a concrete reset
  strategy, and the audit defines how that strategy is verified in CI and
  staging.
- Worker mode remains blocked until CI and staging expose green memory-regression
  signals for retained objects, cross-request state bleed, and worker-restart
  stability using baseline-derived thresholds established during calibration.
- The plan gives engineers enough structure to implement the migration without
  inventing the memory-safety strategy from scratch.

## Constraints

- The committed runtime is still `php-fpm` plus Caddy; FrankenPHP bootstrap is
  not yet present.
- The repository enforces strict quality gates, so the migration plan cannot
  lower existing CI standards.
- Endpoint-wide coverage must stay maintainable, which implies matrix-driven
  test generation rather than bespoke one-off tests for every route.
- Some repo-specific worker-mode details are still unresolved, especially the
  concrete authenticated endpoint and the final worker bootstrap shape.

## Risks

- Migrating runtime before leak coverage exists would make production the first
  long-running-process test.
- Adding worker mode without a reset audit could allow cross-request bleed in
  caches, Doctrine state, or serializer-heavy services.
- Using only MAX_REQUESTS could hide real leaks until load increases.
- Using only manual profiling would make regression detection inconsistent and
  reviewer-dependent.
- Treating K6 alone as the leak detector would miss object-retention issues that
  require PHP-level assertions.

## Product Decision

The plan should move in this order:

1. rewrite the specs around FrankenPHP worker mode as the target runtime,
2. audit mutable services and define reset responsibilities,
3. add endpoint-wide same-kernel memory-safety tests using Symfony/PHPUnit,
4. add co-blocking high-risk service-level and async/shared-service leak tests,
5. verify the design in staging with conservative worker restarts,
6. then enable worker mode in production with rollback guardrails.

This is intentionally not a "flip the runtime and hope the tests hold" plan.

## Assumptions / Open Questions

- No committed FrankenPHP bootstrap exists yet.
- The current documented HTTP surface is comprehensive enough to use as the
  initial endpoint matrix.
- The concrete authenticated endpoint still needs confirmation because the repo
  does not obviously commit security firewall configuration.
- The existence of a dedicated soak environment for long-running workers is not
  yet obvious from the repository.
- `shipmonk/memory-scanner` compatibility with the current PHPUnit/Symfony stack
  still needs confirmation during implementation.

## Why Now

Worker mode changes the application's failure modes. The cheapest time to define
cleanup semantics, service reset rules, endpoint-wide repeated-request tests,
and rollout guardrails is before the runtime switch is implemented. Doing this
spec rewrite now turns the future migration into an evidence-driven change
instead of a runtime gamble.

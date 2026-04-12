# Implementation Readiness: FrankenPHP Worker Mode and Memory-Safety Rollout

## Assessed Inputs

- [research.md](./research.md)
- [product-brief.md](./product-brief.md)
- [prd.md](./prd.md)
- [architecture.md](./architecture.md)
- [epics.md](./epics.md)

## Readiness Verdict

Ready to begin implementation planning for the migration track, but not ready to
switch runtime until the service audit, endpoint-wide leak suite, and staging
verification are complete.

## Why It Is Ready

- The bundle now treats FrankenPHP worker mode as the target runtime rather than
  a distant comparison point.
- The runtime contract is explicit about post-request cleanup,
  `gc_collect_cycles()`, and a MAX_REQUESTS-style restart fuse.
- The plan names the full documented REST and GraphQL endpoint inventory as the
  required same-kernel memory-safety matrix.
- The plan gives implementation-level direction for `ResetInterface`,
  `disableReboot()`, `ObjectDeallocationCheckerKernelTestCaseTrait`, and
  `memprof`.
- The rollout path is staged and includes CI, staging, production, and rollback
  guardrails.

## Guardrails Before Runtime Enablement

1. The mutable-service audit must be completed and reviewed.
2. The endpoint-wide repeated-request suite must exist and be green.
3. The authenticated endpoint scenario must be resolved with a real protected
   path or an agreed test-safe substitute.
4. Baseline measurement must be established before any numeric thresholds become
   hard blockers.
5. A staging soak path for long-running workers must be confirmed.

## Traceability Check

- Research grounds the plan in the current `php-fpm` runtime, existing test
  primitives, and the documented endpoint inventory.
- The product brief turns that research into a migration objective centered on
  worker-mode safety rather than generic performance work.
- The PRD converts the objective into concrete runtime, reset, testing, CI, and
  rollout requirements.
- The architecture explains how worker lifecycle rules, reset strategy, same-
  kernel tests, and observability fit together.
- The epics decompose the work into a sequence that can be implemented without
  improvising the memory-safety strategy.

## Gaps and Warnings

- No committed FrankenPHP bootstrap exists yet, so implementation still needs to
  confirm the final worker front-controller and runtime wiring.
- The repo scan did not find committed security firewall configuration, so the
  authenticated scenario remains unresolved.
- Package compatibility for `shipmonk/memory-scanner` with the current
  PHPUnit/Symfony stack still needs confirmation.
- The repo documents load tests but does not obviously document a dedicated
  worker-mode soak environment.

## Recommended First Story

Start with **Epic 1, Story 1.1** to audit the service container for mutable
long-lived state. That story produces the concrete inventory needed to decide
which services need `ResetInterface`, redesign, or special worker-mode tests.

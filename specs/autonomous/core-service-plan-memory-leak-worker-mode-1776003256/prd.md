# Product Requirements Document: FrankenPHP Worker Mode and Endpoint-Wide Memory Safety

## Overview

This PRD defines how core-service should move from `php-fpm` to FrankenPHP
worker mode without introducing request-to-request state bleed or uncontrolled
memory growth. The plan covers runtime design, service reset rules, endpoint-
wide memory-safety tests, CI/staging rollout, and operational safeguards.

## Problem Statement

FrankenPHP worker mode is desirable because it avoids full Symfony bootstrap on
every request, but it changes the application from a request-isolated PHP model
to a long-running process model.

Under worker mode, the application kernel, service container, caches, Doctrine
ODM state, serializer state, and request-derived references may survive across
requests. Traditional `php-fpm` assumptions are therefore no longer sufficient.

The migration must explicitly prevent retained state, detect leaks early, and
bound operational risk when code or third-party dependencies are not perfectly
clean.

## Goals

- Safely adopt FrankenPHP worker mode for this Symfony service.
- Prevent request-to-request state contamination in reused workers.
- Detect memory leaks and retained objects early in CI and staging.
- Cover the full documented REST and GraphQL endpoint inventory with memory-
  safety tests.
- Define a repeatable reset strategy for mutable services.
- Keep the migration compatible with the repository's current Make, Docker,
  PHPUnit, and GitHub Actions workflows.

## Non-Goals

- Guarantee mathematically perfect zero memory growth in every third-party
  dependency.
- Rewrite the whole application before the migration.
- Treat MAX_REQUESTS as a substitute for fixing leaks.
- Rely only on ad hoc manual profiling.
- Position `roave/no-leaks` as the primary migration solution.

## Functional Requirements

- `FR1`: The architecture must define FrankenPHP worker mode as a boot-once,
  handle-many-requests runtime model for core-service.
- `FR2`: The worker-loop design must include explicit post-request cleanup and
  `gc_collect_cycles()` after each handled request.
- `FR3`: The runtime design must include a MAX_REQUESTS-style worker restart
  fuse as a pragmatic safeguard for legacy or third-party leaks.
- `FR4`: The implementation plan must require an audit of services that keep
  mutable properties, arrays, entities, request-derived objects, closures,
  callbacks, or caches on long-lived service instances.
- `FR5`: Services that may retain state between requests must implement
  `ResetInterface` and clear accumulated state in `reset()`, or be redesigned so
  no mutable request state survives.
- `FR6`: The plan must define service design rules that forbid unbounded request
  data in service properties, unbounded static caches, and lingering
  user/session/entity/request objects in singleton services.
- `FR7`: The primary dev dependency for leak detection must be
  `shipmonk/memory-scanner`.
- `FR8`: The primary Symfony/PHPUnit integration path must use `KernelTestCase`
  and `WebTestCase` leak checks with
  `ObjectDeallocationCheckerKernelTestCaseTrait` where applicable.
- `FR9`: The test strategy must include a dedicated memory-safety suite for all
  currently documented REST endpoints.
- `FR10`: The test strategy must include a dedicated memory-safety suite for all
  currently documented GraphQL queries and mutations.
- `FR11`: Endpoint-wide repeated-request tests must use same-kernel behavior via
  `disableReboot()` so they approximate worker-mode reuse instead of reboot-per-
  request behavior.
- `FR12`: The plan must explicitly acknowledge that `disableReboot()` resets
  `kernel.reset` services rather than rebuilding the container, and that test
  environment adjustments may be required for security token storage and
  Doctrine ODM behavior.
- `FR13`: The test strategy must include targeted scenarios for:
  simple read, authenticated flow, Doctrine-heavy write, serializer-heavy
  response, error/exception path, and endpoints using custom caches or shared
  services.
- `FR14`: The plan must keep `DomainEventMessageHandler` and related subscriber
  flows as supporting `KernelTestCase` leak scenarios because they also model
  long-lived shared-service reuse.
- `FR15`: CI must fail on confirmed retained-object leaks in targeted tests.
- `FR16`: The plan must define an optional deep-debug path using
  `arnaud-lb/memprof` for local or staging forensic analysis when CI leak tests
  are inconclusive.
- `FR17`: The plan must define staging rollout, production rollout, rollback,
  and worker-restart observability expectations for the FrankenPHP migration.

## Non-Functional Requirements

- `NFR1`: The blocking memory-safety suite must be deterministic enough for CI.
- `NFR2`: The strategy must work with the repository's Linux/Docker/GitHub
  Actions execution model.
- `NFR3`: Thresholds must not be invented. Baseline measurement must be
  established first on representative runners.
- `NFR4`: Failure diagnostics and artifacts must exclude business payloads and
  customer PII.
- `NFR5`: Endpoint-wide coverage must remain maintainable through a matrix-driven
  test approach rather than ad hoc one-off tests.
- `NFR6`: Existing quality thresholds must remain intact.
- `NFR7`: The design must distinguish normal warm-up behavior from unbounded
  growth.
- `NFR8`: MAX_REQUESTS must be treated as a safety fuse, not as proof of worker
  correctness.
- `NFR9`: Deep `memprof` runs should remain optional/manual unless a later repo
  workflow explicitly promotes them.

## Endpoint Coverage Scope

The initial endpoint matrix is the currently documented public surface:

- REST: 19 routes covering health plus CRUD for customers, customer types, and
  customer statuses
- GraphQL: 15 operations covering read and write flows for the same domains

The implementation may use data providers or generated fixtures to keep the
suite maintainable, but the final matrix must represent the full documented
surface.

## Acceptance Criteria

1. The specs explicitly describe the long-running worker constraints of
   FrankenPHP worker mode.
2. The worker-loop design explicitly requires post-request cleanup,
   `gc_collect_cycles()`, and a MAX_REQUESTS-style restart fuse.
3. The specs define a mandatory audit of risky stateful services and require
   `ResetInterface` or redesign where mutable state survives across requests.
4. The specs define a dedicated memory-safety test suite using
   `shipmonk/memory-scanner` and
   `ObjectDeallocationCheckerKernelTestCaseTrait`.
5. The specs define repeated-request same-kernel tests for the full documented
   REST and GraphQL endpoint inventory.
6. The specs define targeted high-risk scenarios for simple read,
   authenticated flow, Doctrine-heavy write, serializer-heavy response, error
   path, and cache/shared-service flows.
7. The specs explicitly document `disableReboot()` caveats for security token
   storage and Doctrine ODM behavior.
8. The specs document `memprof` as the escalation path for hard leaks.
9. The specs define staged CI, staging, production rollout, and rollback
   guardrails.
10. No numeric leak threshold is claimed without a baseline measurement phase.

## Assumptions / Open Questions

- No committed FrankenPHP bootstrap or worker loop exists in the repo today.
- The repository uses PHPUnit 10.5 and Symfony 7.4; package compatibility for
  `shipmonk/memory-scanner` still needs confirmation during implementation.
- The repo scan did not find committed security firewall config, so the concrete
  authenticated endpoint for repeated-request leak testing still needs human
  confirmation.
- The repo documents load tests but does not obviously document a dedicated
  worker-mode soak environment.
- The initial service-audit results do not exist yet; the hotspots must be
  discovered during implementation.

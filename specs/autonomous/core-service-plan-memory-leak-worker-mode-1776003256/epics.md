# Epics and Stories: FrankenPHP Worker Mode and Memory-Safety Rollout

## Requirements Coverage Map

- `FR1`, `FR2`, `FR3`, `FR4`, `FR5`, `FR6`, `NFR3`, `NFR8` -> Epic 1
- `FR7`, `FR8`, `FR9`, `FR10`, `FR11`, `FR12`, `FR13`, `FR14`, `NFR1`,
  `NFR2`, `NFR4`, `NFR5` -> Epic 2
- `FR15`, `FR16`, `FR17`, `NFR6`, `NFR7`, `NFR9` -> Epic 3

## Epic 1: Lock the Worker-Mode Runtime Contract and Reset Strategy

Goal: define how long-running workers are expected to behave and identify every
service that can retain state between requests.

### Story 1.1: Audit the Service Container for Mutable Long-Lived State

As a maintainer, I want a concrete inventory of mutable long-lived services so
that worker-mode risk is based on real code, not assumptions.

Acceptance criteria:

- The audit identifies services that store arrays, entities, documents,
  request-derived objects, closures, callbacks, or caches on properties.
- The audit explicitly calls out risky categories:
  custom caches, memoizers, serializer-heavy helpers, Doctrine ODM state
  holders, security/context helpers, long-lived SDK clients, and static
  registries.
- The audit output distinguishes per-request state, bounded cache state, and
  leaked state.
- The audit references actual repository services or confirms when a category is
  not present.

### Story 1.2: Classify Risky Services and Define `ResetInterface` Actions

As a developer, I want a reset strategy for mutable services so that
worker-mode behavior is explicit and testable.

Acceptance criteria:

- Each risky service is assigned one of: redesign, bounded cache, or
  `ResetInterface`.
- Services that keep mutable state between requests have an explicit `reset()`
  expectation.
- The plan documents how those services are wired into Symfony reset behavior.
- The plan states which services should not be made resettable and instead must
  be redesigned to stop holding request state.

### Story 1.3: Define the FrankenPHP Worker Loop Contract

As a platform engineer, I want the worker loop behavior documented so that the
runtime migration does not invent cleanup semantics during implementation.

Acceptance criteria:

- The worker lifecycle is documented as boot once, handle many requests, clean
  up, call `gc_collect_cycles()`, and restart on a MAX_REQUESTS-style fuse.
- The design states that MAX_REQUESTS is a safety fuse, not proof that leaks are
  acceptable.
- The design defines restart observability and rollback expectations.
- The design describes how to distinguish normal warm-up from problematic growth.

## Epic 2: Build the Endpoint-Wide Memory-Safety Test Layer

Goal: create a repeatable same-kernel test strategy that covers the full
documented endpoint surface and the highest-risk shared-service flows.

### Story 2.1: Introduce Memory-Scanner Test Support

As a contributor, I want shared test support for leak detection so that memory
assertions follow one repository-standard pattern.

Acceptance criteria:

- `shipmonk/memory-scanner` is the planned primary dev dependency.
- Test support is designed around `ObjectDeallocationCheckerKernelTestCaseTrait`
  for Symfony/PHPUnit usage where applicable.
- Failure diagnostics are redacted and CI-friendly.
- Baseline measurement is explicitly separated from hard threshold enforcement.

### Story 2.2: Add Same-Kernel Repeated-Request Coverage for All REST and GraphQL Endpoints

As a release owner, I want repeated-request coverage for the full endpoint
inventory so that worker-mode safety is not based on a handful of happy paths.

Acceptance criteria:

- The plan defines a matrix-driven `WebTestCase` or `ApiTestCase` suite using
  `disableReboot()`.
- All documented REST endpoints are represented in the repeated-request matrix.
- All documented GraphQL queries and mutations are represented in the repeated-
  request matrix.
- The design remains maintainable through data providers or generated fixtures
  rather than bespoke test classes for every route.

### Story 2.3: Add Targeted High-Risk Scenarios

As a maintainer, I want dedicated high-risk scenarios so that the suite can
catch the most likely worker-mode failures early.

Acceptance criteria:

- The plan includes scenarios for:
  simple read,
  authenticated flow,
  Doctrine-heavy write,
  serializer-heavy response,
  error or exception path,
  endpoint using custom caches or shared services.
- The plan includes supporting `KernelTestCase` leak checks for
  `DomainEventMessageHandler` and related subscriber paths.
- The plan documents `disableReboot()` caveats for security token storage and
  Doctrine ODM instead of assuming reboot-per-request semantics.
- The plan states that repeated requests must not show unexplained retained
  objects for the targeted flows.

## Epic 3: Operationalize the Migration in CI, Staging, and Production

Goal: turn the test strategy into a deployable rollout gate with clear rollback
criteria.

### Story 3.1: Add CI Wiring and Failure-Evidence Policy

As a reviewer, I want CI to surface worker-mode safety regressions
consistently so that rollout decisions are based on repeatable evidence.

Acceptance criteria:

- Memory-safety tests are planned as part of the FrankenPHP migration track.
- CI is expected to fail on confirmed retained-object leaks in targeted tests.
- The plan defines a minimum failure-evidence schema using JSON or NDJSON
  artifacts, optionally gzip-compressed when large.
- The failure-evidence policy is structured and reviewable:
  - required fields:
    timestamp, job name, git commit, worker or process identifier, test case id,
    scenario name, warm-up and measured iteration counts, baseline memory bytes,
    post-warmup memory bytes, final retained memory bytes, peak memory bytes,
    short failure reason, reproduction command, linked logs path, calibration
    policy version
  - optional fields:
    allocator stats, heap snapshot paths, core-dump references
  - retention and safety:
    artifacts are uploaded under a stable prefix such as
    `artifacts/memory-regression/<job>/<scenario>/`,
    remain free of business payloads and customer PII,
    and keep a bounded retention period suitable for review

### Story 3.2: Define the Staging Soak and Deep-Debug Path

As a platform engineer, I want a staging verification path so that worker mode
is proven under repeated requests before production rollout.

Acceptance criteria:

- The plan defines a staging soak phase after CI leak tests are in place.
- The plan requires worker RSS or equivalent trend observation during soak.
- `arnaud-lb/memprof` is documented as the escalation path for difficult leaks or
  native-allocation questions.
- Deep `memprof` runs remain optional/manual unless a later workflow promotes
  them.

### Story 3.3: Define Production Rollout and Rollback Criteria

As a release owner, I want conservative rollout rules so that worker mode can
be reverted quickly if real leak indicators appear.

Acceptance criteria:

- Early staging and production use conservative MAX_REQUESTS settings.
- The plan explicitly blocks rollout on:
  unbounded memory growth,
  cross-request state bleed,
  authentication or Doctrine corruption caused by improper resets,
  repeated worker restarts caused by instability.
- The plan defines rollback to non-worker mode or safer runtime configuration.
- Later tuning is allowed only after evidence from staging and early production.

## Implementation Backlog

1. audit the service container for mutable singleton-like state
2. classify risky services by per-request state, bounded cache, or leaked state
3. introduce `ResetInterface` implementations or redesign services where needed
4. add `shipmonk/memory-scanner`
5. add `KernelTestCase` and `WebTestCase` memory-safety test support
6. add repeated-request endpoint tests using `disableReboot()`
7. document and implement worker-loop cleanup expectations, including
   `gc_collect_cycles()`
8. configure conservative MAX_REQUESTS for staging
9. run soak verification and collect worker RSS/restart evidence
10. review findings, update rollout criteria, and only then enable worker mode

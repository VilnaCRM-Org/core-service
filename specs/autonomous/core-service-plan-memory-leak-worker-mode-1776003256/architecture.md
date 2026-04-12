# Architecture: FrankenPHP Worker Mode With Endpoint-Wide Memory-Safety Coverage

## Architecture Goal

Define a safe migration path from `php-fpm` to FrankenPHP worker mode for this
Symfony service, with memory-safety rules strong enough that long-running
workers do not introduce request-to-request state contamination or unbounded
memory growth.

## Current-State Alignment

- Runtime today: `php-fpm` behind Caddy.
- Test primitives today: PHPUnit 10.5, Symfony `KernelTestCase`,
  API Platform `ApiTestCase`, BrowserKit client reuse with `disableReboot()`,
  Behat, Schemathesis, K6, and GitHub Actions.
- Data layer today: Doctrine MongoDB ODM.
- Risk hotspots already visible in code:
  cache-backed repositories, OpenAPI/serializer helpers, observability emitters,
  `DomainEventMessageHandler`, and customer event subscribers.

## Target Runtime Model

### Worker-Mode Request Lifecycle

FrankenPHP worker mode should be treated as:

1. boot Symfony once,
2. keep the kernel and service container resident,
3. handle many HTTP requests in the same worker,
4. perform explicit post-request cleanup,
5. call `gc_collect_cycles()` after each handled request,
6. increment the handled-request counter,
7. terminate and restart the worker when the MAX_REQUESTS-style fuse is reached
   or when worker health checks fail.

This lifecycle is materially different from `php-fpm`, where process memory is
discarded at the end of the request.

### Worker-Loop Operational Requirements

- The worker must finish request handling cleanly before cleanup runs.
- Cleanup must not rely on process exit.
- `gc_collect_cycles()` must run after each handled request in the worker loop.
- A MAX_REQUESTS-style restart fuse must be configured conservatively for
  staging and early production.
- Worker restarts must be observable so the team can distinguish healthy fuse
  behavior from instability caused by leaks.

## Service Design Rules

Any service that survives across requests must follow these rules:

- no unbounded request data retained in service properties,
- no static caches without strict bounds and a reset strategy,
- no user, session, request, response, entity, or document objects lingering in
  singleton-like services,
- no memoization without both bounds and explicit reset behavior,
- no callbacks or closures that capture request-scoped objects unless their
  lifetime is strictly bounded to the request.

## Symfony-Specific Design Constraints

### Reset Strategy

- Services that may accumulate state between requests must implement
  `ResetInterface`.
- Their `reset()` method must clear all accumulated mutable state.
- They must be wired so Symfony resets them between same-kernel requests through
  the `kernel.reset` mechanism.

### Risk Categories That Must Be Audited

- custom caches and memoizers
- serializer-heavy helpers and normalizers
- Doctrine ODM state holders and helpers around `DocumentManager`
- security and context helpers
- long-lived SDK or infrastructure clients that can capture request-derived data
- static registries
- observability collectors or spies that accumulate arrays on properties

### Required Service-Audit Checklist

For every risky service:

1. identify mutable properties,
2. classify each property as per-request data, bounded cache, or leaked state,
3. redesign the service or implement `reset()`,
4. add leak-focused tests that prove the state is cleared.

## Test Architecture

### Layer A: Endpoint-Wide Same-Kernel HTTP Memory-Safety Suite

This is the primary CI safety net because FrankenPHP worker mode reuses the same
application kernel across requests.

Implementation shape:

- use `WebTestCase`-compatible clients (`ApiTestCase` in this repo),
- call `disableReboot()` so the same kernel handles repeated requests,
- cover the full documented REST and GraphQL inventory through a matrix-driven
  suite,
- assert that targeted flows do not retain unexpected objects or show
  unexplained post-warmup growth.

Required endpoint matrix:

- all documented REST endpoints,
- all documented GraphQL queries,
- all documented GraphQL mutations.

### Layer B: High-Risk Kernel-Level Leak Checks

This layer complements the endpoint matrix with tighter object-deallocation
checks around specific shared-service flows.

Shared memory-test support for Layers A and B must:

- collect `memory_get_usage(true)` and `memory_get_peak_usage(true)` at stable
  checkpoints,
- record warm-up versus measured iteration boundaries,
- format readable diagnostics that stay free of business payloads and customer
  PII, matching the redaction posture already used by
  `DomainEventMessageHandler`.

Primary candidates:

- `DomainEventMessageHandler` repeated happy path
- subscriber failure path
- metrics-emission failure path
- cache-backed repository interactions
- serializer or normalization-heavy helpers

This layer should use `KernelTestCase` together with
`ObjectDeallocationCheckerKernelTestCaseTrait`.

### Layer C: Deep Forensics

`arnaud-lb/memprof` is the escalation path for:

- CI failures that are not well explained by object-retention assertions,
- suspected native allocations,
- staging or local runs where detailed heap forensics are required.

`memprof` is intentionally optional/manual unless the repository later adds a
dedicated workflow for it.

## Endpoint Scenario Design

The endpoint-wide suite must include targeted repeated-request scenarios for:

- simple read endpoint,
- authenticated endpoint,
- Doctrine-heavy write endpoint,
- serializer or normalization-heavy endpoint,
- error or exception path,
- endpoint using custom caches or shared services.

Because the repo does not obviously commit security firewall config today, the
authenticated scenario remains a required implementation item with an explicit
repo-level open question on which protected route should be used.

## `disableReboot()` Design Notes

- `disableReboot()` is required because reboot-per-request tests do not model a
  reused kernel.
- In this mode Symfony resets services tagged with `kernel.reset` instead of
  rebuilding the whole container.
- The test suite must therefore account for:
  security token storage reset behavior,
  Doctrine ODM identity/unit-of-work behavior,
  any service whose correctness currently depends on full kernel reboot.

The design must prefer deliberate test-environment adjustments over naive reuse
of existing functional tests.

## Tooling Choices

- Primary leak-testing package:
  `shipmonk/memory-scanner`
- Primary Symfony/PHPUnit integration:
  `KernelTestCase` and `WebTestCase` plus
  `ObjectDeallocationCheckerKernelTestCaseTrait`
- Optional deep forensic tool:
  `arnaud-lb/memprof`
- Non-primary option:
  `roave/no-leaks` is not the migration anchor

## Observability Requirements

The runtime and rollout plan must observe:

- per-worker RSS or equivalent process memory trend over repeated requests,
- handled request counts per worker,
- worker restart count and restart reason,
- distinction between normal warm-up and unbounded growth.

Interpretation rules:

- warm-up is an initial increase that later plateaus,
- problematic growth is continued upward slope after warm-up,
- repeated early restarts caused by MAX_REQUESTS or instability are rollout
  blockers until explained.

## CI and Rollout Architecture

### CI

- Memory-safety tests are part of the FrankenPHP migration track.
- CI must fail on confirmed retained-object leaks in targeted tests.
- Failure artifacts must be redacted and structured.
- Deep `memprof` runs stay optional/manual unless the repo later promotes them.

### Staged Rollout Path

1. spec rewrite
2. service audit
3. leak-focused test implementation
4. staging soak and repeated-request verification
5. production rollout with conservative MAX_REQUESTS
6. tuning after evidence

### Rollback Path

If leak indicators appear, rollback must allow:

- disabling worker mode,
- falling back to non-worker execution or safer runtime configuration,
- raising conservatism around restart settings only as a temporary safety fuse.

## Risks and Mitigations

- **Risk:** worker mode enabled before reset audit is complete
  **Mitigation:** rollout is blocked until service audit and endpoint-wide leak
  suite exist.

- **Risk:** false confidence from reboot-per-request tests
  **Mitigation:** same-kernel tests must use `disableReboot()`.

- **Risk:** legacy or third-party leaks remain after initial fixes
  **Mitigation:** conservative MAX_REQUESTS fuse plus staged rollout plus
  rollback path.

- **Risk:** diagnostics leak payload or customer data
  **Mitigation:** mirror the existing `DomainEventMessageHandler` policy and keep
  failure evidence redacted.

- **Risk:** numeric limits are invented before stable baselines exist
  **Mitigation:** baseline measurement comes before any hard threshold.

## Recommended Implementation Boundary

The first implementation phase is complete only when the repository has:

1. a documented worker-loop cleanup contract,
2. a mutable-service audit with reset decisions,
3. a matrix-driven endpoint-wide same-kernel memory-safety suite design,
4. supporting `KernelTestCase` leak checks for high-risk flows,
5. CI/staging rollout rules, rollback rules, and restart observability
   requirements.

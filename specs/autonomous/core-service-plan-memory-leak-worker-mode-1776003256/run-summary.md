# Run Summary

## Task Framing

- Bundle directory:
  `specs/autonomous/core-service-plan-memory-leak-worker-mode-1776003256`
- Issue: `#166`
- Working branch: `feat/memory-leak-worker-mode-planning`
- Rewrite goal:
  replace the earlier async-proxy-first planning angle with a worker-mode-first
  BMAD bundle that plans the move from `php-fpm` to FrankenPHP and requires
  memory-safety coverage for the full endpoint inventory
- Scope of this update:
  rewrite the existing BMAD artifacts in place; no production runtime or test
  implementation yet

## Repository Context Used for the Rewrite

- The committed runtime is still `php-fpm` plus Caddy.
- PHPUnit 10.5 and Symfony 7.4 are in use.
- `tests/Integration/ObservabilityBusinessMetricsTest.php` already uses
  `disableReboot()`, which provides a real same-kernel testing primitive.
- `DomainEventMessageHandler` models long-lived shared-service execution and
  already enforces PII-safe logging posture.
- The documented API surface currently includes nineteen REST endpoints and
  fifteen GraphQL operations.
- No committed FrankenPHP bootstrap, app-level `ResetInterface`, or
  `shipmonk/memory-scanner` integration exists yet.

## Major Planning Decisions Rewritten

- FrankenPHP worker mode is now the architectural destination, not a later
  comparison target.
- The plan now requires endpoint-wide same-kernel memory-safety testing across
  the full documented REST and GraphQL inventory.
- The plan requires explicit worker-loop cleanup with `gc_collect_cycles()`.
- The plan requires a MAX_REQUESTS-style fuse for staging and early production.
- The plan requires a service audit and `ResetInterface` strategy for mutable
  long-lived services.
- `shipmonk/memory-scanner` is now positioned as the primary leak-testing
  package.
- `KernelTestCase` and `WebTestCase` with
  `ObjectDeallocationCheckerKernelTestCaseTrait` are now the primary Symfony
  integration path.
- `arnaud-lb/memprof` is documented as the deep-forensics escalation path.
- `roave/no-leaks` is explicitly not the primary migration solution.

## Open Questions, Warnings, and Blockers

- Open question:
  what exact FrankenPHP worker bootstrap and front-controller shape will replace
  the current `php-fpm` setup?
- Open question:
  which concrete authenticated endpoint should anchor the mandatory protected-
  route repeated-request scenario, given the lack of obvious committed firewall
  configuration?
- Open question:
  does the team already have a staging or soak environment suitable for
  long-running worker verification?
- Warning:
  numeric memory thresholds are intentionally not set yet; baseline measurement
  must be established first.
- Warning:
  the migration must not rely on MAX_REQUESTS alone; restart fuses are a safety
  net, not a substitute for leak fixes.

## Recommended Next Step

Use **Epic 1, Story 1.1** from [epics.md](./epics.md) as the first execution
entry point: audit the container for mutable long-lived state, classify risky
services, and use that inventory to drive the `ResetInterface` and leak-test
implementation work.

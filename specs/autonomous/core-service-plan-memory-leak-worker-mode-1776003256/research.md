# Research: FrankenPHP Worker-Mode Adoption and Memory-Safety Baseline

## Goal

Define a repository-grounded baseline for moving core-service from `php-fpm` to
FrankenPHP worker mode and for adding endpoint-wide memory-safety coverage that
can detect retained state and memory leaks before rollout.

## Current Evidence From This Repository

- The committed runtime is still `php-fpm` behind Caddy:
  `Dockerfile` ends with `CMD ["php-fpm"]`, the container entrypoint defaults
  to `php-fpm`, and `infrastructure/docker/caddy/Caddyfile` routes requests to
  `php_fastcgi`.
- No FrankenPHP bootstrap or worker loop is currently committed.
- The repository uses Symfony 7.4, API Platform test tooling, and PHPUnit 10.5.
- `tests/Integration/ObservabilityBusinessMetricsTest.php` already uses
  `disableReboot()`, which proves the repo has a same-kernel BrowserKit test
  primitive available today.
- The service uses Doctrine MongoDB ODM, Redis-backed cache pools, Symfony
  Messenger, serializer-heavy API Platform flows, and custom OpenAPI
  normalizers. All of these are long-lived-state risk surfaces once the kernel
  is reused across requests.
- `src/Shared/Infrastructure/Bus/Event/Async/DomainEventMessageHandler.php`
  already models worker-style processing and explicitly avoids logging payloads
  because of PII concerns.
- Repository scan did not find committed `ResetInterface` implementations or
  app-level `kernel.reset` tags yet.
- Repository scan did not find existing `shipmonk/memory-scanner`,
  `arnaud-lb/memprof`, or `roave/no-leaks` dependencies.
- The current public API surface is documented in `docs/api-endpoints.md` and
  load-tested in `docs/performance.md`.

## Why FrankenPHP Worker Mode Changes the Design

`php-fpm` resets process memory between requests. FrankenPHP worker mode does
not. The Symfony kernel, service container, static state, caches, Doctrine ODM
objects, serializer state, and request-derived references can survive from one
request to the next.

That means the migration cannot be treated as a simple web-server swap. The
application must be treated as a long-running process with explicit cleanup
rules, worker restart safeguards, and tests that intentionally reuse the same
kernel across multiple requests.

Traditional request-isolated assumptions are therefore insufficient for this
migration.

## Source Facts That Must Drive the Plan

1. Worker mode keeps the application booted and in memory across requests.
2. The design must include explicit post-request cleanup and
   `gc_collect_cycles()` in the worker loop.
3. The rollout must include a MAX_REQUESTS-style restart fuse for legacy or
   third-party leaks.
4. Services that may retain mutable state between requests must implement
   `ResetInterface` and clear state in `reset()`.
5. Symfony functional tests can simulate same-process multi-request behavior
   with `disableReboot()`.
6. In `disableReboot()` mode Symfony resets `kernel.reset` services instead of
   rebuilding the container, and this can affect security token storage and
   Doctrine behavior.
7. `shipmonk/memory-scanner` is the primary leak-testing package for this
   migration.
8. `KernelTestCase` and `WebTestCase` leak checks should use
   `ObjectDeallocationCheckerKernelTestCaseTrait` where applicable.
9. `arnaud-lb/memprof` is the optional deep-forensics tool for difficult cases.
10. `roave/no-leaks` is not the primary solution for this migration.

## Endpoint Inventory That the Plan Must Cover

### REST Endpoints

Current documentation exposes nineteen REST routes:

- `GET /api/health`
- `GET /api/customers`
- `POST /api/customers`
- `GET /api/customers/{id}`
- `PUT /api/customers/{id}`
- `PATCH /api/customers/{id}`
- `DELETE /api/customers/{id}`
- `GET /api/customer_types`
- `POST /api/customer_types`
- `GET /api/customer_types/{id}`
- `PUT /api/customer_types/{id}`
- `PATCH /api/customer_types/{id}`
- `DELETE /api/customer_types/{id}`
- `GET /api/customer_statuses`
- `POST /api/customer_statuses`
- `GET /api/customer_statuses/{id}`
- `PUT /api/customer_statuses/{id}`
- `PATCH /api/customer_statuses/{id}`
- `DELETE /api/customer_statuses/{id}`

### GraphQL Operations

Current documentation exposes fifteen GraphQL operations:

- Queries:
  `customer`, `customers`, `customerType`, `customerTypes`, `customerStatus`,
  `customerStatuses`
- Mutations:
  `createCustomer`, `updateCustomer`, `deleteCustomer`,
  `createCustomerType`, `updateCustomerType`, `deleteCustomerType`,
  `createCustomerStatus`, `updateCustomerStatus`, `deleteCustomerStatus`

The plan should treat this documented inventory as the baseline matrix for
endpoint-wide memory-safety testing.

## Highest-Risk Stateful Surfaces

- Same-kernel HTTP request handling across the full REST and GraphQL surface
- API Platform normalization and collection rendering
- Doctrine ODM `DocumentManager` state across repeated reads and writes
- Cache-backed repositories and tag invalidation logic
- Long-lived SDK or infrastructure clients that may capture request-derived data
- Observability emitters and log-context assembly
- Messenger/domain-event subscriber chains using shared services
- Static registries, memoizers, or arrays that can grow without bounds

## Testing Implications for Symfony

- Endpoint-wide memory tests should use `WebTestCase`-compatible clients
  (`ApiTestCase` in this repo) with `disableReboot()` so multiple requests reuse
  the same kernel.
- Service-level and message-level leak tests should use `KernelTestCase` with
  `ObjectDeallocationCheckerKernelTestCaseTrait`.
- `disableReboot()` is useful specifically because it approximates the same
  application-kernel reuse that FrankenPHP worker mode introduces.
- Because `disableReboot()` triggers resets instead of full container rebuilds,
  leak tests must not assume that security token storage, Doctrine ODM unit of
  work, or cached service state behave the same way they do in reboot-per-
  request tests.
- Test-environment adjustments will likely be required instead of naive
  copy-paste from existing functional tests.

## Tooling Position

- Primary CI leak-testing package:
  `shipmonk/memory-scanner`
- Primary integration approach:
  `KernelTestCase` and `WebTestCase` suites with
  `ObjectDeallocationCheckerKernelTestCaseTrait`
- Primary endpoint strategy:
  repeated-request suites that cover the full documented REST and GraphQL
  endpoint matrix
- Secondary supporting suite:
  high-risk service and async-worker flows such as `DomainEventMessageHandler`
- Optional deep-debug path:
  `arnaud-lb/memprof` in local or staging forensic runs
- Explicit non-primary option:
  do not anchor the migration on `roave/no-leaks`

## Research Conclusion

- The plan must treat FrankenPHP worker mode as the architectural destination,
  not as a distant comparison target.
- Endpoint-wide same-kernel repeated-request testing is the primary safety net,
  because the migration risk is request-to-request retained state under a reused
  Symfony kernel.
- Async domain-event handling remains a valuable supporting suite because it
  exercises long-lived shared-service behavior outside the HTTP entrypoint.
- The rollout must be staged: spec rewrite, service audit, leak-focused test
  implementation, staging soak, conservative worker rollout, then tuning from
  measured evidence.

## Assumptions / Open Questions

- No FrankenPHP bootstrap was found in the repository. The exact worker
  front-controller and loop structure still need confirmation during
  implementation.
- The repository uses PHPUnit 10.5 and Symfony 7.4 today, but
  `shipmonk/memory-scanner` compatibility is still an implementation-time
  check.
- The repo scan did not find committed security firewall configuration, even
  though OpenAPI factories model `401` responses. The authenticated endpoint
  required by the test strategy still needs to be identified or introduced in a
  test-safe way.
- The repository documents K6 load testing but does not obviously document a
  dedicated long-running-worker soak environment.
- A service audit has not yet identified the hottest mutable services; that work
  is part of the implementation backlog, not completed in this research phase.

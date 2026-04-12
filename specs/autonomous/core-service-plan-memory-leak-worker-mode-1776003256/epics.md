# Epics and Stories: Memory-Leak Regression Coverage for Worker-Mode Readiness

## Requirements Coverage Map

- `FR1`, `FR4`, `FR5`, `FR6`, `NFR1`, `NFR3`, `NFR4` → Epic 1
- `FR2`, `FR3`, `NFR2`, `NFR6`, `NFR7` → Epic 2
- `FR7`, `FR8`, `FR9`, `NFR5`, `NFR8` → Epic 3

## Epic 1: Establish the Memory-Regression Foundation

Goal: define the measurement policy and reusable harness primitives needed for deterministic leak-regression testing in a single PHP process.

### Story 1.1: Define Measurement Policy and Scenario Boundaries

As a maintainer, I want a documented measurement policy for memory-regression tests so that future scenarios use the same warm-up, sampling, and threshold conventions.

Acceptance criteria:

- Warm-up behavior is explicitly separated from measured behavior.
- The policy defines which memory signals are captured and how they are interpreted.
- The policy explains how thresholds are calibrated before becoming blocking CI gates.
- The policy names the first required async scenarios and the first informational HTTP scenarios.

### Story 1.2: Add Reusable Memory Sampling Test Utilities

As a developer, I want reusable test utilities for memory sampling and reporting so that each scenario does not reinvent measurement logic.

Acceptance criteria:

- A shared test-support utility can record baseline, periodic checkpoints, final retained delta, and peak memory.
- Failure output is scenario-specific and readable in CI logs.
- Utility behavior is deterministic enough for CI usage on Linux runners.
- The utilities avoid exposing business payload details in diagnostics.

### Story 1.3: Introduce a Single-Process Worker-Style Loop Harness

As a developer, I want a loop-based execution harness for repeated async processing so that the test suite can simulate long-lived service reuse.

Acceptance criteria:

- The harness runs many iterations in one PHP process.
- The harness supports warm-up iterations and measured iterations.
- The harness can run both happy-path and failure-path scenarios.
- The harness integrates with the repository's existing PHPUnit conventions.

## Epic 2: Cover the Highest-Risk Memory-Retention Paths

Goal: make the existing async domain-event path the first blocking regression signal for long-lived memory stability.

### Story 2.1: Add Happy-Path Async Memory Regression Scenarios

As a release owner, I want happy-path async event processing covered by memory-regression tests so that normal worker-style behavior is guarded before worker mode rollout.

Acceptance criteria:

- A scenario repeatedly executes `DomainEventMessageHandler` with representative envelopes.
- The scenario includes real shared-service interactions such as cache invalidation or metrics emission.
- The scenario enforces calibrated retained-memory expectations after warm-up.
- The scenario is runnable locally and inside CI.

### Story 2.2: Add Failure-Path Async Memory Regression Scenarios

As a maintainer, I want repeated failure-path execution covered so that exceptions, logging, and resilience paths do not leak retained state.

Acceptance criteria:

- At least one scenario repeatedly triggers subscriber failure.
- At least one scenario repeatedly triggers metric-emission failure handling.
- Diagnostics clearly distinguish which failure path exceeded the allowed growth budget.
- The suite proves that failure handling remains non-throwing while still checking memory behavior.

### Story 2.3: Define Informational HTTP Memory Baseline Scenarios

As a platform engineer, I want an HTTP-oriented baseline plan so that future FrankenPHP worker-mode behavior can be compared against request-shaped workloads.

Acceptance criteria:

- A low-noise HTTP scenario is defined for baseline comparison.
- A mixed customer workload scenario is defined for higher-churn comparison.
- The plan specifies external memory sampling requirements for these scenarios.
- These scenarios are explicitly marked informational until the target worker runtime exists in the repository.

## Epic 3: Operationalize the Signal for Local Use and CI

Goal: make the future implementation practical, reviewable, and usable as a worker-mode rollout gate.

### Story 3.1: Expose Memory-Regression Execution Through Make and Docs

As a contributor, I want documented Make targets for the memory-regression suite so that I can run the same signal locally that CI will use.

Acceptance criteria:

- The blocking async-worker suite has a dedicated Make entrypoint.
- Any informational HTTP evidence path has a separate Make entrypoint.
- Developer documentation explains prerequisites, expected runtime, and failure interpretation.
- The docs explain why these tests are a prerequisite for FrankenPHP worker mode.

### Story 3.2: Add CI Wiring and Artifact Capture Policy

As a reviewer, I want CI integration for memory-regression evidence so that pull requests can surface worker-mode readiness information consistently.

Acceptance criteria:

- Blocking and informational jobs are separated by purpose.
- The blocking async-worker suite fits the repository's CI duration budget.
- The plan defines a minimum failure-evidence schema captured as JSON or NDJSON artifacts, optionally gzip-compressed when large, containing at least: timestamp, job name, git commit, worker or process identifier, test case id, scenario name, warm-up and measured iteration counts, baseline memory bytes, post-warmup memory bytes, final retained memory bytes, peak memory bytes, short failure reason, reproduction command, linked logs path, and calibration policy version; any optional allocator stats, heap snapshot paths, or core-dump references must also remain free of business payloads and customer PII.
- The policy states when calibration runs are required, how thresholds are updated safely, and that failure artifacts are uploaded under a stable CI prefix such as `artifacts/memory-regression/<job>/<scenario>/` with a 14-day retention period.

### Story 3.3: Define the Worker-Mode Rollout Gate

As a release owner, I want a clear rollout gate so that FrankenPHP worker mode is only enabled after memory-regression evidence is credible.

Acceptance criteria:

- The gate explicitly requires the blocking async-worker suite to be green.
- The gate explicitly requires informational HTTP memory evidence to exist before final worker-mode enablement.
- The gate names the unresolved environment assumptions that must be closed before rollout.
- The gate points to the future implementation artifacts and comparison evidence needed for approval.

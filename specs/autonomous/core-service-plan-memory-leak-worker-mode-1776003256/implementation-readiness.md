# Implementation Readiness: Memory-Leak Regression Coverage for Worker-Mode Readiness

## Assessed Inputs

- [research.md](./research.md)
- [product-brief.md](./product-brief.md)
- [prd.md](./prd.md)
- [architecture.md](./architecture.md)
- [epics.md](./epics.md)

## Readiness Verdict

Ready to begin implementation planning with two explicit guardrails.

## Why It Is Ready

- The planning set is aligned on the primary goal: add memory-regression coverage before enabling FrankenPHP worker mode.
- The plan deliberately avoids coupling the prerequisite testing work to the runtime migration itself.
- The architecture and epics both prioritize the existing async worker-style path as the first blocking signal.
- The PRD and epics both separate blocking regression coverage from later informational HTTP comparison evidence.
- The bundle identifies concrete repository surfaces rather than describing worker-memory testing in generic terms.

## Required Guardrails Before Implementation Starts

1. Approve the phased signal strategy:
   the first merge-blocking implementation should cover async worker-style memory regression only, while HTTP memory evidence remains informational until FrankenPHP exists in the repo.
2. Approve the calibration policy:
   thresholds must be established from representative runners before becoming strict CI blockers.

## Traceability Check

- Research identifies the async domain-event path as the highest-fidelity current proxy.
- The product brief turns that into a scoped, repository-specific prerequisite initiative.
- The PRD converts the scope into measurable functional and non-functional requirements.
- The architecture explains how the harness, scenario groups, and CI split satisfy those requirements.
- The epics decompose the work into a logical implementation sequence that preserves the phased strategy.

## Gaps and Warnings

- Repository documentation around event-driven architecture lags behind the current code; implementation should reconcile docs as part of execution.
- The eventual home for informational HTTP memory evidence in CI is still a policy decision, not a settled implementation detail.
- Thresholds are intentionally not fixed yet; implementation must treat calibration as a first-class activity rather than an afterthought.

## Recommended First Story

Start with **Epic 1, Story 1.1** to define the measurement policy, scenario list, and calibration rules. That story unlocks the harness and prevents later stories from encoding inconsistent memory assertions.

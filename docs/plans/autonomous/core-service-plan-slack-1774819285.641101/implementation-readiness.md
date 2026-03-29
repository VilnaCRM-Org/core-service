# Implementation Readiness Review - Audit Log Export with Saved Filters and Scheduled Exports

**Date:** 2026-03-29  
**Assessor:** BMALPH `implementation-readiness` validate pass  
**Status:** Draft  
**Readiness Verdict:** `NOT READY FOR IMPLEMENTATION`

## Documents Reviewed

- `docs/plans/autonomous/core-service-plan-slack-1774819285.641101/product-brief.md`
- `docs/plans/autonomous/core-service-plan-slack-1774819285.641101/prd.md`
- `docs/plans/autonomous/core-service-plan-slack-1774819285.641101/architecture.md`
- `docs/plans/autonomous/core-service-plan-slack-1774819285.641101/epics.md`
- `docs/plans/autonomous/core-service-plan-slack-1774819285.641101/research.md`
- `docs/plans/autonomous/core-service-plan-slack-1774819285.641101/run-summary.md`

## Executive Summary

The planning artifacts are materially aligned on the feature goal, scope, architecture direction, and rollout sequence. They consistently describe a brownfield `Core/AuditLog` bounded context, a normalized audit filter model, REST-first lifecycle contracts, asynchronous export generation, controlled artifact retrieval, and externally triggered scheduled execution. PRD-to-epics traceability is mostly complete pending verification of the Epic 6 FR traceability corrections captured in `epics.md`.

The feature is still not implementation-ready. Four decisions remain prerequisite rather than implementation detail:

- the authoritative audit source of truth
- the authorization and ownership model
- the production artifact storage provider
- the default retention and expiry policy

Those unresolved items affect domain boundaries, permission checks, artifact delivery, cleanup behavior, self-auditing, and the final acceptance criteria for multiple stories. The bundle is strong enough to continue planning refinement, but not safe to hand to implementation unchanged.

## Alignment Assessment

| Area                                   | Assessment        | Evidence                                                                                                                                                                                                                                    |
| -------------------------------------- | ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Problem statement and business value   | Aligned           | Brief, PRD, architecture, and epics all center on self-service, repeatable, governed audit exports that reduce engineering-assisted reporting.                                                                                              |
| Scope boundaries                       | Aligned           | All four exclude internal scheduler development, cross-service aggregation, recipient delivery, and broad GraphQL parity from MVP.                                                                                                          |
| API direction                          | Aligned           | Brief, PRD, architecture, and epics consistently make export lifecycle and artifact retrieval REST-first, with GraphQL optional and narrower.                                                                                               |
| Bounded context strategy               | Aligned           | PRD assumes a dedicated audit-focused domain; architecture makes `Core/AuditLog` explicit; epics sequence work accordingly.                                                                                                                 |
| Async export lifecycle                 | Aligned           | Brief and PRD require async behavior; architecture defines durable pre-dispatch state and dedicated transport; Epic 3 implements the lifecycle.                                                                                             |
| Scheduled execution direction          | Mostly aligned    | Architecture resolves scheduling to an external trigger plus an internal execution endpoint; epics now carry idempotency and machine-authenticated batch-safe execution expectations, but operational ownership details still need closure. |
| Governance, privacy, and self-auditing | Aligned in intent | All artifacts require redaction, controlled access, expiry/revocation, and auditability of feature actions.                                                                                                                                 |
| Operational readiness detail           | Partially aligned | Architecture is materially more specific than brief/PRD/epics on queueing, storage abstraction, and internal trigger behavior. Stories still need final policy-backed values for retention and guardrails.                                  |

## Strengths

- Product brief, PRD, architecture, and epics describe the same feature and rollout sequence.
- PRD functional requirement coverage in epics is mostly complete pending verification of the Epic 6 traceability correction.
- Bounded context, API direction, async lifecycle, and artifact-governance intent are aligned.
- The architecture fits the repository’s documented DDD, CQRS, hexagonal, MongoDB, API Platform, and YAML resource-discovery patterns.
- The bundle explicitly avoids implementation work and stays at planning level.

## Gaps and Risks

- The authoritative audit dataset is still unresolved, which blocks stable browse semantics, paging guarantees, redaction guarantees, and self-auditing.
- The permission and ownership model is still abstract, which blocks testable acceptance criteria for cross-user administration, artifact visibility, and machine-triggered schedule execution.
- Artifact storage is abstracted correctly in architecture but not selected, which blocks final delivery, encryption, revocation, and cleanup semantics.
- Retention and expiry remain policy-driven but not concretely defaulted, which blocks final behavior for `available`, `expired`, `revoked`, cleanup cadence, and `410 Gone` semantics.
- Export guardrails are still underspecified. Maximum time window, row count, file size, queue depth, and concurrent schedule volume are not yet defined.
- Empty-result export behavior remains open: whether no-data exports still produce a CSV artifact or terminate in a no-data state.
- No dedicated UX artifact exists for downstream consumers. That is acceptable for backend-first planning, but status and error semantics still need to be frozen before implementation.

## Critical Blockers

### 1. Authoritative Audit Source of Truth

The bundle consistently acknowledges that the authoritative audit dataset is not defined in this repository. Architecture introduces `AuditLogEntryQueryInterface`, which is the correct boundary, but the actual source decision is still missing.

This blocks implementation because:

- browse filters, stable identifiers, and paging guarantees depend on the real source
- redaction consistency and self-auditing depend on how that source is queried and written
- historical coverage and consistency guarantees remain undefined
- indexing, scale planning, and export latency assumptions are not credible until the source is known

### 2. Authorization and Ownership Model

FR-13 is clear, and architecture correctly keeps authorization in application-layer policy adapters. However, none of the artifacts defines the actual ownership matrix.

This blocks implementation because:

- saved-filter sharing, cross-user export visibility, schedule management, and artifact retrieval all depend on who can act across ownership boundaries
- the internal schedule execution path needs a machine actor and explicit privilege boundaries
- self-auditing semantics depend on who is considered the actor for scheduled runs, cleanup, expiry, and revocation
- testing and acceptance criteria cannot be finalized without a role/scope model

### 3. Artifact Storage Provider

Architecture correctly chooses a storage abstraction and keeps bytes outside MongoDB, but the actual provider is unresolved.

This blocks implementation because:

- retrieval behavior, encryption posture, revocation mechanics, file-size handling, and cleanup semantics all depend on the provider
- operational ownership, residency, backup, checksum verification, and latency behavior are provider-specific
- Epic 4 and Epic 6 acceptance criteria cannot be completed against an unknown delivery and storage model

### 4. Retention and Expiry Defaults

Every artifact correctly treats retention as policy-driven, but none sets the MVP default values or policy owner.

This blocks implementation because:

- export state transitions, cleanup cadence, `410 Gone` behavior, and dashboard expectations all depend on concrete defaults
- support and compliance cannot reason about `available`, `expired`, and `revoked` states without default timing
- Story 4.2 and Story 6.1 remain only partially testable until retention is concrete

## Recommended Next Steps

1. Publish a short decision record for the authoritative audit source, including scope, consistency model, query guarantees, and self-auditing write path.
2. Publish an authorization and ownership matrix that covers human actors, machine actors, cross-user administration, sharing rules, and tenant boundaries.
3. Select the artifact storage provider and capture delivery, encryption, retention, revocation, and cleanup behavior in architecture.
4. Approve MVP retention defaults and metadata-retention rules, then update Story 4.2 and Story 6.1 acceptance criteria accordingly.
5. Define concrete export guardrails and scale targets so FR-8, NFR-1, NFR-2, and NFR-5 become testable.
6. Freeze user-visible status and error semantics for consuming clients.
7. Re-run implementation readiness after those decisions are folded into `prd.md`, `architecture.md`, and `epics.md`.

## Readiness Checklist

- [x] Product brief, PRD, architecture, and epics describe the same feature and scope.
- [ ] PRD functional requirement coverage in epics is mostly complete pending verification of the Epic 6 traceability correction.
- [x] Bounded context, API direction, async lifecycle, and artifact-governance intent are aligned.
- [x] MVP scope is consistent on owner-scope authenticated retrieval, not recipient-based delivery.
- [ ] Authoritative audit source of truth is selected and documented.
- [ ] Authorization and ownership model is defined and testable.
- [ ] Artifact storage provider is selected and operationalized in planning.
- [ ] Default retention and expiry policy is approved.
- [ ] Scheduler trigger ownership, auth, and monitoring model are fully documented.
- [ ] Export guardrails and scale targets are defined.
- [ ] User-visible status and error semantics are frozen for any consuming client.

## Final Note

This feature is close to planning-complete but not implementation-ready. The next work is decision closure, not coding. Once the four critical blockers are resolved and the dependent stories are tightened, the bundle should be ready for a fresh implementation-readiness pass.

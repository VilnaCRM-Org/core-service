# Product Requirements Document - Audit Log Export with Saved Filters and Scheduled Exports

**Author:** BMad  
**Date:** 2026-03-29  
**Status:** Draft  
**Product Area:** VilnaCRM core-service  
**Mode:** Brownfield extension, planning only

## Problem Framing

VilnaCRM core-service does not currently provide a documented audit-log export capability, reusable saved audit filters, or recurring export scheduling. Teams that need audit evidence must recreate filter logic manually, depend on individual knowledge, or involve engineering for repeat exports. That creates slow investigations, inconsistent report scope, missed recurring obligations, and unnecessary privacy and compliance risk.

This PRD defines a product-level capability that makes audit exports self-service, repeatable, and governable. Authorized users must be able to define audit filters, save them for reuse, request exports asynchronously, and schedule recurring exports with visible status, run history, and controlled artifact access.

## Product Goal

The goal is to let authorized users generate trustworthy, repeatable audit exports from core-service without engineering assistance, while preserving brownfield architectural fit and enforcing security, redaction, retention, and lifecycle controls from day one.

## Brownfield Context and Constraints

- core-service is a Symfony 7, API Platform 4, GraphQL, and MongoDB service built around DDD, CQRS, and hexagonal architecture.
- The currently documented bounded contexts are `Shared`, `Core/Customer`, and `Internal/HealthCheck`; no audit-specific bounded context is documented today.
- API resources are configured through YAML mappings, and current write flows follow API Platform processor and command-dispatch patterns.
- GraphQL is enabled, but export creation, status lookup, and artifact retrieval are better aligned with REST-shaped workflows.
- Current async infrastructure is centered on domain events and Symfony Messenger; it is not yet a documented export-job subsystem.
- No internal recurring-job scheduler or export artifact delivery subsystem is documented in the repository.
- The authoritative audit source of truth is not yet defined in this repository and is therefore a prerequisite dependency.

## Personas

### Primary Personas

**Platform or Tenant Administrator**  
Needs fast access to scoped audit evidence for oversight and issue review. Success means saving a trusted filter once, rerunning it reliably, and retrieving exports without engineering support.

**Compliance, Security, or Operations Lead**  
Needs recurring, defensible evidence with clear timing, scope, and failure visibility. Success means scheduled exports run predictably, results are governed, and failures are visible without inspecting infrastructure.

### Secondary Personas

**Support or Investigation Analyst**  
Needs quick, time-bounded exports during escalations and incident reviews.

**Engineering or Platform Maintainer**  
Benefits when audit exports become self-service, observable, and supportable instead of custom one-off requests.

**Internal or External Auditor**  
Consumes generated artifacts and needs them to be consistent, traceable, and trustworthy.

## Scope

### In Scope

- Audit-log filtering for export use cases.
- Named saved filters for reusable audit queries.
- On-demand export requests with asynchronous processing and persistent lifecycle states.
- Scheduled export definitions with recurring cadence, status, and run history.
- Controlled artifact retrieval, expiry, and revocation behavior.
- Product-level permissions, privacy, redaction, retention, and observability requirements.
- REST-first export lifecycle contracts for core-service.
- Optional GraphQL support only where it adds clear value for browsing or filter management.

### Out of Scope

- Detailed implementation design for audit capture, queue topology, storage provider, or API schema.
- A general-purpose internal scheduler inside core-service.
- Cross-service enterprise audit aggregation.
- Analytics dashboards or non-audit reporting.
- Emailing raw export files as attachments in MVP.
- Recipient-based export delivery beyond authenticated owner-scope retrieval.
- Full GraphQL parity for artifact download and export lifecycle management.

## Personas and Jobs to Be Done

- When an administrator needs evidence for a known audit scenario, they want to reuse an approved filter so they can generate the same export consistently every time.
- When a compliance lead has a recurring reporting obligation, they want to schedule exports and review run history so they can prove the report was generated on time.
- When an investigator is handling an incident, they want a fast export request and clear status so they can get evidence without waiting on engineering.
- When operations staff see a failed run, they want a clear failure state and next action so they can recover without reading raw infrastructure logs.

## Primary User Flows

### 1. Save a Reusable Audit Filter

1. A user defines audit criteria such as actor, action, target, outcome, and time window.
2. The system validates the criteria against the supported normalized filter model.
3. The user saves the criteria with a name and optional description.
4. The system stores the filter and makes it available for reuse, editing, duplication, or deletion.

### 2. Request an On-Demand Export

1. A user starts from ad hoc criteria or a saved filter.
2. The user requests an export.
3. The system immediately creates an export record and returns a status handle without blocking on file generation.
4. The user monitors lifecycle state until the artifact is available or failed.
5. The user retrieves the artifact through the approved access path before expiry.

### 3. Create and Manage a Scheduled Export

1. A user selects a saved filter or defines filter criteria for recurring use.
2. The user chooses cadence, timezone, and owner scope within the approved permission model.
3. The system stores the schedule, shows next run time, and activates it.
4. The user can pause, resume, edit, or delete the schedule.
5. Each scheduled run creates a trackable run record and export result.

### 4. Monitor History and Resolve Failures

1. A user reviews export history or schedule run history.
2. The system shows timestamps, lifecycle states, file availability, expiry status, and non-sensitive failure details.
3. The user retries or recreates the export when allowed, or adjusts the filter or schedule if the request is invalid.
4. The system preserves an auditable trail of who requested, downloaded, expired, paused, resumed, or deleted export assets.

## Functional Requirements

**FR-1 Audit Filter Model**  
The product shall support a normalized audit filter model for at least actor, action, target entity or resource, outcome, and time window. The same filter model shall be reusable across audit browsing, saved filters, on-demand exports, and schedules.

**FR-2 Filter Persistence**  
Authorized users shall be able to create, name, edit, duplicate, and delete saved audit filters. Each saved filter shall persist criteria, creator, ownership scope, timestamps, and status.

**FR-3 Relative Time Windows for Schedules**  
The product shall support relative time windows for scheduled exports so recurring runs can resolve dynamic periods at execution time. Absolute time windows remain valid for one-off exports.

**FR-4 Schedule Filter Reproducibility**  
A scheduled export shall snapshot the effective filter definition when the schedule is created or updated, so later edits to the source saved filter do not silently change an active schedule.

**FR-5 On-Demand Export Requesting**  
Users shall be able to request an export from either ad hoc criteria or a saved filter. The request response shall create a persistent export record immediately and return current lifecycle state plus a stable identifier for lookup.

**FR-6 Export Format and Contents**  
MVP shall support CSV export output. Each generated export shall include, or be paired with, metadata describing applied filters, generation timestamp, requester or schedule source, and record count.

**FR-7 Export Lifecycle States**  
The product shall track export lifecycle through at least `requested`, `queued`, `processing`, `available`, `failed`, and `expired`. Lifecycle transitions shall be visible to authorized users.

**FR-8 Export Guardrails**  
The product shall enforce request guardrails for unsupported or excessive export requests. When a request cannot be fulfilled, the user shall receive a clear validation or failure message with actionable guidance.

**FR-9 Scheduled Export Management**  
Authorized users shall be able to create, view, edit, pause, resume, and delete scheduled exports. MVP cadence support shall cover at least daily, weekly, and monthly recurrence with explicit timezone handling.

**FR-10 Schedule Run Tracking**  
Every scheduled execution shall create a run record with trigger time, effective filter window, lifecycle state, completion time, failure details when relevant, and reference to any generated artifact.

**FR-11 Idempotent Recurring Behavior**  
The product shall prevent or clearly resolve duplicate scheduled runs for the same schedule and execution window, so users can trust recurring evidence is not accidentally duplicated.

**FR-12 Artifact Retrieval and Expiry**  
Authorized users shall be able to retrieve available exports through an authenticated, controlled access path. Artifacts shall expire automatically according to retention policy and become inaccessible after expiry or revocation.

**FR-13 Permissions and Ownership**  
The product shall enforce explicit permission checks for viewing audit records, saving filters, requesting exports, creating schedules, retrieving artifacts, and managing schedules or exports created by other users.

**FR-14 Failure Visibility**  
Users shall be able to view export and schedule failures through product surfaces that explain the failure class without exposing raw stack traces, secrets, or sensitive payloads.

**FR-15 Self-Auditing of Export Operations**  
Actions taken within this feature, including filter changes, export requests, schedule changes, artifact retrieval, and artifact revocation, shall themselves be auditable.

**FR-16 Interface Direction**  
core-service shall expose REST-first product contracts for export creation, export status, export history, schedule management, run history, and artifact retrieval. GraphQL support is optional for browsing and filter management and is not required for artifact download in MVP.

## Non-Functional Requirements

- The system must acknowledge export request creation quickly and process generation asynchronously so request flows remain interactive.
- Export status and schedule status must remain available even when generation is delayed or a downstream dependency is impaired.
- The feature must not rely on logging raw export payloads, secrets, or sensitive audit contents.
- State transitions for exports and scheduled runs must be durable and traceable.
- The feature must expose product and operational telemetry for request counts, completion counts, failure counts, duration, schedule lag, missed runs, artifact retrieval, expiry, and revocation.
- The solution must preserve brownfield fit with the repository’s DDD, CQRS, hexagonal, and API Platform conventions.
- Audit export behavior must be supportable without direct infrastructure access for common operational issues.
- Retention, access, and redaction behavior must be configurable at policy level, even if initial defaults are chosen in MVP.

## Data, Privacy, and Compliance Considerations

- Audit data and generated exports are sensitive and must be treated as governed artifacts, not generic downloadable files.
- Redaction rules must apply consistently to audit browsing and exported results.
- Export artifacts must be accessible only to authorized users within the correct ownership or tenant scope.
- Artifact access must be revocable, and expiry must be enforced automatically.
- Logs, metrics, and failure messages must avoid raw record payloads and personally sensitive details.
- Scheduled exports must not bypass data governance simply because they are automated.
- The system must preserve evidence of who created, ran, accessed, changed, or revoked export-related assets.
- Delivery behavior in MVP should prefer authenticated retrieval over insecure push channels.

## Dependencies and Open Decisions

### External and Product Dependencies

- An authoritative audit dataset and query surface must exist or be defined in parallel.
- A platform identity and authorization model must be available for role and scope enforcement.
- A production scheduling trigger is required because no internal scheduler is currently documented in core-service.
- A governed artifact storage and retrieval model is required for generated exports.
- Observability hooks and operational dashboards must be available for lifecycle monitoring.

### Brownfield Decisions That Must Carry Into Architecture

- Whether audit capture is allowed to be eventually consistent or must be loss-intolerant.
- How scheduled runs are triggered in production.
- Where generated artifacts live and how authenticated retrieval is implemented.
- What default retention and expiry values apply to artifacts.
- Whether MVP remains CSV-only or expands format support.
- How ownership and cross-user administration work within VilnaCRM’s permission model.

## Acceptance and Success Criteria

### Release Acceptance Criteria

- An authorized user can create, edit, reuse, and delete a saved audit filter without engineering help.
- An authorized user can request an export and track it through clear lifecycle states until success, failure, or expiry.
- A scheduled export can be created, paused, resumed, edited, and deleted, with visible next run time and run history.
- A completed export can be retrieved only through an authenticated path and becomes unavailable after expiry or revocation.
- Failed exports and failed scheduled runs expose user-appropriate failure information and operational visibility.
- Export-related actions are themselves auditable.
- The feature’s product contracts and documentation clearly define permissions, lifecycle states, redaction expectations, and retention behavior.

### Success Metrics

- Median time from export request to artifact availability improves for standard audit use cases.
- The percentage of export requests completed without manual intervention reaches an acceptable operational baseline.
- Recurring audit needs shift from ad hoc manual exports to saved filters and schedules.
- Engineering- or support-assisted audit export requests decrease after rollout.
- Scheduled export success rate is high and missed-run rate is low.
- Generated artifacts are retrieved before expiry at a healthy rate.
- Security, privacy, and redaction incidents related to exports remain at or near zero.

## Assumptions Made

- This feature is a net-new brownfield capability and should be planned as a dedicated audit-focused domain rather than folded into existing `Customer` or `HealthCheck` areas.
- The authoritative audit dataset will exist or be planned in parallel; this PRD covers exportability, not audit capture implementation.
- Export generation is asynchronous and status-driven rather than synchronous in the request-response path.
- REST is the required interface for export lifecycle and artifact retrieval, while GraphQL is optional for browse or filter workflows.
- MVP supports CSV output first; additional export formats can be considered later.
- Scheduled exports need relative time-window support and should snapshot effective filter definitions for reproducibility.
- MVP delivery should prefer authenticated in-product retrieval over raw email attachments or unsecured push delivery.
- Artifact retention and expiry will be policy-driven, with an initial default to be confirmed during architecture and readiness work.

## Unresolved Questions

- What exact audit events and entities are in scope for the authoritative audit dataset in core-service?
- Must audit capture and export source data be loss-intolerant, or is eventual consistency acceptable?
- What is the exact permission and ownership model for saved filters, schedules, and artifacts across users, admins, and tenant scope?
- What artifact retention and expiry defaults should apply in MVP?
- Is CSV-only sufficient for MVP, or do compliance users require JSON or another format immediately?
- What production mechanism will trigger scheduled runs?
- What governed storage and retrieval model will be used for generated artifacts?
- What scale targets for volume, export size, and completion latency should architecture optimize for on day one?

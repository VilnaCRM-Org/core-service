# core-service - Epic Breakdown

## Overview

This document provides the epic and story breakdown for the audit log export feature in VilnaCRM core-service. It decomposes the approved research, PRD, and architecture decisions into user-value-driven epics and development-ready stories.

The epic order intentionally follows the architecture rollout sequence defined in the architecture draft:

1. audit query contract and bounded context
2. saved filters
3. on-demand exports
4. artifact retrieval
5. scheduled exports
6. cleanup, observability, and self-auditing

This is a planning-only artifact. It defines scope, sequencing, and acceptance criteria without prescribing implementation diffs or code-level tasks.

## Requirements Inventory

### Functional Requirements

FR-1: Support one normalized audit filter model for actor, action, target resource type, target resource id, outcome, and time window, reusable across browse, saved filters, exports, and schedules.

FR-2: Allow authorized users to create, name, edit, duplicate, and delete saved audit filters with criteria, creator, ownership scope, timestamps, and status.

FR-3: Support relative time windows for scheduled exports so recurring runs resolve dynamic periods at execution time, while absolute time windows remain valid for one-off exports.

FR-4: Snapshot the effective filter definition when a schedule is created or updated so later edits to the source saved filter do not silently change active schedules.

FR-5: Allow users to request exports from ad hoc criteria or saved filters, returning a persistent export record immediately with a stable identifier and current lifecycle state.

FR-6: Support CSV as the MVP export format, with metadata describing applied filters, generation timestamp, requester or schedule source, and record count.

FR-7: Track export lifecycle through at least `requested`, `queued`, `processing`, `available`, `failed`, and `expired`, and make those transitions visible to authorized users.

FR-8: Enforce request guardrails for unsupported or excessive exports and provide clear validation or failure messages with actionable guidance.

FR-9: Allow authorized users to create, view, edit, pause, resume, and delete scheduled exports with at least daily, weekly, and monthly cadence plus explicit timezone handling.

FR-10: Create a run record for every scheduled execution, including trigger time, effective filter window, lifecycle state, completion time, failure details when relevant, and any generated artifact reference.

FR-11: Prevent or safely resolve duplicate scheduled runs for the same schedule and execution window.

FR-12: Allow authorized users to retrieve available exports through an authenticated, controlled access path, with automatic expiry and inaccessibility after expiry or revocation.

FR-13: Enforce explicit permissions for browsing audit records, managing saved filters, requesting exports, creating schedules, retrieving artifacts, and administering resources across ownership boundaries.

FR-14: Expose export and schedule failures through product surfaces with user-appropriate failure detail and no raw stack traces, secrets, or sensitive payloads.

FR-15: Make feature actions themselves auditable, including filter changes, export requests, schedule changes, artifact retrieval, and artifact revocation.

FR-16: Expose REST-first contracts for export creation, export status, export history, schedule management, run history, and artifact retrieval, with GraphQL optional only for browse and filter management.

### Non-Functional Requirements

NFR-1: Export request creation must remain interactive and acknowledge work quickly while generation runs asynchronously.

NFR-2: Export and schedule status must remain visible even when generation is delayed or a downstream dependency is impaired.

NFR-3: The feature must not log raw export payloads, secrets, or sensitive audit contents.

NFR-4: Export and scheduled-run state transitions must be durable and traceable.

NFR-5: The feature must expose telemetry for requests, completions, failures, duration, schedule lag, missed runs, artifact retrieval, expiry, and revocation.

NFR-6: The solution must preserve brownfield fit with the repository's DDD, CQRS, hexagonal, API Platform, and MongoDB conventions.

NFR-7: Common operational issues must be diagnosable from product-facing or support-facing surfaces without direct infrastructure access.

NFR-8: Retention, access, and redaction behavior must remain policy-driven and configurable.

### Additional Requirements

- Introduce a dedicated `Core/AuditLog` bounded context rather than extending `Shared`, `Core/Customer`, or `Internal/HealthCheck`.
- Keep public product contracts REST-first; GraphQL is optional for browse and filter management and is not required for artifact delivery in MVP.
- Use explicit resource discovery and explicit resource mappings rather than convention-based exposure.
- Keep the authoritative audit source behind a query contract so browse and export flows do not assume a fixed storage implementation.
- Store feature-owned metadata in MongoDB, but store generated CSV bytes through an external artifact-storage abstraction rather than inside Mongo documents.
- Persist export state before asynchronous generation begins so enqueue or worker failures do not erase user-visible work state.
- Treat schedule triggering and expiry cleanup as externally triggered operational flows owned by core-service rather than introducing a general-purpose internal scheduler.
- Default audit browse and artifact responses to private, non-cacheable behavior; artifact retrieval must be `no-store`.
- Preserve history for expired and failed exports, and logically delete filters and schedules rather than hard-deleting traceable user actions.
- Apply one redaction policy across browse and export behavior and capture the effective policy version used for generated exports.
- Emit safe observability signals for exports, schedules, downloads, expiries, and duplicate-run skips without logging raw filter payloads or audit rows.

### UX Design Requirements

No separate UX design artifact was provided for this planning run. API usability, validation clarity, and support-facing status visibility are covered through the stories below.

### Requirement Coverage Summary

- Functional coverage: all 16 functional requirements are mapped to at least one epic, with cross-cutting ownership for FR-13, FR-15, and FR-16.
- Non-functional coverage: asynchronous responsiveness and durable lifecycle handling are anchored in Epics 3 and 5; privacy, retention, and governed access are anchored in Epics 1, 4, and 6; telemetry and supportability are anchored in Epic 6.
- Rollout alignment: epic order follows the architecture sequence from browse contract to saved filters, on-demand exports, artifact retrieval, schedules, and operational hardening.

### FR Coverage Map

FR-1: Epic 1 establishes the normalized audit filter contract and browse surface; Epics 2, 3, and 5 reuse that same model.

FR-2: Epic 2 delivers saved audit filter lifecycle management.

FR-3: Epic 5 delivers relative time-window support for recurring exports.

FR-4: Epic 5 snapshots effective filter definitions for schedules.

FR-5: Epic 3 delivers on-demand export request creation and durable export records.

FR-6: Epic 3 delivers CSV generation and export metadata; Epic 4 delivers governed retrieval of the generated artifact.

FR-7: Epic 3 delivers export lifecycle state tracking.

FR-8: Epic 3 delivers export request guardrails and actionable failure messaging.

FR-9: Epic 5 delivers schedule CRUD, pause, resume, and delete behavior.

FR-10: Epic 5 delivers run tracking for scheduled executions.

FR-11: Epic 5 delivers idempotent recurring execution behavior.

FR-12: Epic 4 delivers authenticated retrieval, expiry, and revocation behavior; Epic 6 reinforces expiry cleanup and defensible post-expiry metadata behavior.

FR-13: Epics 1 through 5 enforce permissions and ownership checks across browse, filters, exports, schedules, runs, and artifacts.

FR-14: Epics 3 and 5 expose non-sensitive failure visibility for exports and scheduled runs; Epic 6 adds support-facing diagnostics and safe failure observability.

FR-15: Epic 6 closes end-to-end self-auditing, with action capture requirements reinforced in Epics 2 through 5.

FR-16: Epic 1 establishes the REST-first browse contract and Epic 3 through Epic 5 define the REST-first export, artifact, and schedule lifecycle surfaces.

## Epic List

### Epic 1: Governed Audit Browse Foundation

Authorized users can browse audit evidence through a normalized, REST-first contract that establishes the `Core/AuditLog` bounded context and reusable filter semantics for later saved-filter, export, and schedule capabilities.

**FRs covered:** FR-1, FR-13, FR-16

### Epic 2: Saved Audit Filters

Authorized users can store, manage, and reuse named audit filters so repeated audit investigations do not require manual re-entry of criteria.

**FRs covered:** FR-1, FR-2, FR-13, FR-15

### Epic 3: On-Demand Export Requests and Lifecycle

Authorized users can request audit exports and track them through a durable, asynchronous lifecycle with clear validation, status, and failure handling.

**FRs covered:** FR-5, FR-6, FR-7, FR-8, FR-13, FR-14, FR-16

### Epic 4: Governed Artifact Retrieval and Retention

Authorized users can retrieve completed export artifacts through a controlled access path with explicit expiry, revocation, and metadata visibility.

**FRs covered:** FR-6, FR-12, FR-13, FR-15

### Epic 5: Scheduled Exports and Run History

Authorized users can define recurring exports, trust their execution windows and idempotency rules, and review run history without engineering intervention.

**FRs covered:** FR-3, FR-4, FR-9, FR-10, FR-11, FR-13, FR-14, FR-16

### Epic 6: Cleanup, Observability, and Self-Auditing

Operations, compliance, and support teams can trust the feature in production because cleanup, telemetry, support-facing status, and self-auditing are complete and governable.

**FRs covered:** FR-12, FR-14, FR-15  
**NFR focus:** NFR-3, NFR-5, NFR-7, NFR-8

## Epic 1: Governed Audit Browse Foundation

Authorized users can browse audit evidence through a normalized, REST-first contract that establishes the `Core/AuditLog` bounded context and reusable filter semantics for later saved-filter, export, and schedule capabilities.

### Story 1.1: Define the Audit Browse Contract and Bounded Context

As a platform maintainer,  
I want audit browsing to live behind a dedicated audit-log contract and query boundary,  
So that later filter, export, and schedule capabilities are not coupled to an unresolved audit source implementation.

**FRs:** FR-1, FR-13, FR-16  
**NFRs:** NFR-4, NFR-6

**Acceptance Criteria:**

1. **Given** the audit export feature is introduced into core-service  
   **When** the browse capability is defined for MVP  
   **Then** it is planned as part of a dedicated `Core/AuditLog` bounded context with explicit public resource exposure  
   **And** the browse surface depends on a source-agnostic audit query contract rather than assuming a fixed persistence model.

2. **Given** later features must reuse browse criteria  
   **When** the normalized audit filter definition is documented  
   **Then** it includes actor, action, target resource type, target resource id, outcome, and time window  
   **And** the same definition can round-trip between browse requests, saved filters, export requests, and schedule snapshots.

### Story 1.2: Browse Audit Entries with Normalized Filters and Stable Ordering

As an administrator,  
I want to query audit entries with consistent filters and cursor ordering,  
So that I can inspect recent audit evidence before saving or exporting a filter.

**FRs:** FR-1, FR-13, FR-16  
**NFRs:** NFR-2, NFR-6

**Acceptance Criteria:**

1. **Given** an authorized user supplies supported audit filter criteria  
   **When** they browse audit entries  
   **Then** the system returns matching entries through a read-only REST collection surface  
   **And** results are ordered by a stable descending cursor model suitable for repeatable paging.

2. **Given** a user submits unsupported filters or an invalid time-window combination  
   **When** the browse request is evaluated  
   **Then** the system rejects the request with actionable validation guidance  
   **And** it does not silently broaden or reinterpret the requested scope.

### Story 1.3: Enforce Browse Privacy, Permission, and Redaction Defaults

As a compliance lead,  
I want browse results to respect access scope and redaction rules by default,  
So that interactive audit review does not expose more data than the export feature is allowed to reveal.

**FRs:** FR-13  
**NFRs:** NFR-3, NFR-8

**Acceptance Criteria:**

1. **Given** a user lacks permission to inspect a tenant, owner scope, or resource subset  
   **When** they browse audit entries outside their allowed scope  
   **Then** access is denied or safely scoped down according to policy  
   **And** no unauthorized record details are disclosed.

2. **Given** a browse response is returned to an authorized user  
   **When** record data is rendered  
   **Then** the active redaction policy is applied consistently  
   **And** the response is marked as private and non-cacheable.

## Epic 2: Saved Audit Filters

Authorized users can store, manage, and reuse named audit filters so repeated audit investigations do not require manual re-entry of criteria.

### Story 2.1: Create and List Saved Audit Filters

As an administrator,  
I want to save a named audit filter and see it in my reusable filter list,  
So that I can repeat a known investigation or reporting scope without rebuilding criteria.

**FRs:** FR-1, FR-2, FR-13  
**NFRs:** NFR-6, NFR-8

**Acceptance Criteria:**

1. **Given** an authorized user submits a valid normalized filter definition with a name and optional description  
   **When** they create a saved audit filter  
   **Then** the system persists the criteria, creator, ownership scope, timestamps, and active status  
   **And** the new filter appears in the user's default saved-filter listing.

2. **Given** a saved-filter list is requested  
   **When** the response is returned  
   **Then** filters are scoped according to ownership and permission rules  
   **And** each entry includes enough metadata for the user to distinguish and reuse it safely.

### Story 2.2: Edit, Duplicate, and Logically Delete Saved Filters

As an administrator,  
I want to refine or clone existing saved filters without losing history,  
So that I can evolve recurring audit use cases while preserving traceability.

**FRs:** FR-2, FR-13, FR-15  
**NFRs:** NFR-4, NFR-8

**Acceptance Criteria:**

1. **Given** an authorized user selects an existing saved filter they may manage  
   **When** they edit its descriptive fields or filter criteria  
   **Then** the updated filter remains valid against the normalized filter model  
   **And** the system preserves modification timestamps and actor traceability.

2. **Given** a user duplicates or deletes a saved filter  
   **When** the action completes  
   **Then** duplication creates a new filter with copied criteria and distinct identity  
   **And** deletion is logical rather than destructive so historical references remain explainable.

### Story 2.3: Reuse Saved Filters Consistently Across Browse and Export Flows

As an investigation analyst,  
I want saved filters to behave the same way wherever I reuse them,  
So that I can trust a filter definition before I turn it into an export or schedule.

**FRs:** FR-1, FR-2, FR-13  
**NFRs:** NFR-2, NFR-6

**Acceptance Criteria:**

1. **Given** a user selects a saved filter from a browse or export flow  
   **When** the filter is applied  
   **Then** the effective criteria match the stored normalized definition exactly  
   **And** the user can see which filter is in use.

2. **Given** a user attempts to reuse or modify a saved filter outside their permitted scope  
   **When** the action is evaluated  
   **Then** the system blocks the unauthorized action with a clear explanation  
   **And** no hidden cross-scope filter reuse occurs.

## Epic 3: On-Demand Export Requests and Lifecycle

Authorized users can request audit exports and track them through a durable, asynchronous lifecycle with clear validation, status, and failure handling.

### Story 3.1: Request an Export from Ad Hoc or Saved Criteria

As an administrator,  
I want to request an audit export from either current criteria or a saved filter,  
So that I can start evidence generation without waiting for the full file to be built in the request path.

**FRs:** FR-5, FR-6, FR-8, FR-13, FR-16  
**NFRs:** NFR-1, NFR-4, NFR-6

**Acceptance Criteria:**

1. **Given** an authorized user submits a valid export request based on ad hoc criteria or a saved filter  
   **When** the request is accepted  
   **Then** the system creates a persistent export record immediately with a stable identifier and an initial lifecycle state of `requested` or `queued`  
   **And** the record captures the effective filter snapshot, requester identity, owner scope, and CSV as the selected MVP format.

2. **Given** a user submits an unsupported, excessive, or invalid export request  
   **When** the request is evaluated  
   **Then** the system rejects it with actionable guidance about the guardrail that was violated  
   **And** it does not create a misleading partial export record.

### Story 3.2: Process Export Jobs Asynchronously with Durable Lifecycle Tracking

As an operations lead,  
I want export generation to progress through visible lifecycle states without losing work,  
So that users can trust the system even when generation is delayed or a dependency is impaired.

**FRs:** FR-6, FR-7, FR-14  
**NFRs:** NFR-1, NFR-2, NFR-4

**Acceptance Criteria:**

1. **Given** an export record has been created successfully  
   **When** background generation begins and completes  
   **Then** the export transitions through `requested`, `queued`, `processing`, and either `available` or `failed`  
   **And** the system records the relevant timestamps and non-sensitive completion metadata, including record count when generation succeeds.

2. **Given** enqueueing or downstream generation fails after the export request was accepted  
   **When** the failure is recorded  
   **Then** the export remains visible to authorized users in a durable failure state  
   **And** the request is not lost or silently dropped.

### Story 3.3: Review Export Status, History, and Retry-Safe Failures

As an investigation analyst,  
I want to see export progress and understand failures without reading infrastructure logs,  
So that I can decide whether to wait, retry, or adjust the underlying request.

**FRs:** FR-7, FR-8, FR-14, FR-16  
**NFRs:** NFR-2, NFR-7

**Acceptance Criteria:**

1. **Given** an authorized user opens export status or export history  
   **When** they inspect a current or past export  
   **Then** they can see the lifecycle state, failure summary when relevant, and whether an artifact is available or expired  
   **And** the failure information is user-safe and actionable rather than raw infrastructure detail.

2. **Given** a failed export is eligible for retry  
   **When** the user initiates a retry  
   **Then** the system creates a new export record linked to the failed attempt  
   **And** the historical failed export remains unchanged for auditability.

## Epic 4: Governed Artifact Retrieval and Retention

Authorized users can retrieve completed export artifacts through a controlled access path with explicit expiry, revocation, and metadata visibility.

### Story 4.1: Retrieve Completed Artifacts Through an Authenticated Path

As an administrator,  
I want to download a completed export only through a governed application path,  
So that evidence retrieval is controlled, authenticated, and consistent with privacy requirements.

**FRs:** FR-12, FR-13  
**NFRs:** NFR-3, NFR-8

**Acceptance Criteria:**

1. **Given** an export is in the `available` state and the caller is authorized  
   **When** the user retrieves the artifact  
   **Then** the system streams the CSV through an authenticated core-service access path  
   **And** the response uses safe download headers that prevent public caching or storage leakage.

2. **Given** the caller is authenticated but not allowed to access the requested export  
   **When** they attempt retrieval  
   **Then** the system returns the appropriate denied or out-of-scope outcome  
   **And** it does not expose underlying storage details or secret delivery URLs.

### Story 4.2: Enforce Expiry, Revocation, and Retention-Aware Download Outcomes

As a compliance lead,  
I want expired or revoked artifacts to become inaccessible in a predictable way,  
So that retention policy is enforceable and evidence access can be governed after generation.

**FRs:** FR-12, FR-13  
**NFRs:** NFR-4, NFR-8

**Acceptance Criteria:**

1. **Given** an export artifact has passed its retention window or has been explicitly revoked  
   **When** a user attempts to retrieve it  
   **Then** the system returns a terminal unavailable outcome that distinguishes expired or revoked artifacts from active ones  
   **And** the artifact is not downloadable.

2. **Given** an export has expired or been revoked  
   **When** an authorized user views its status resource  
   **Then** metadata such as `availableAt`, `expiresAt`, `revokedAt`, and lifecycle state remains visible  
   **And** historical traceability is preserved even if the bytes themselves have been removed.

### Story 4.3: Expose Artifact Metadata and Access History

As an auditor,  
I want the export resource to explain what was generated and who accessed it,  
So that the resulting evidence remains understandable and defensible after download.

**FRs:** FR-6, FR-12, FR-15  
**NFRs:** NFR-5, NFR-7

**Acceptance Criteria:**

1. **Given** an authorized user views a completed export record  
   **When** they inspect its details  
   **Then** the system shows the effective filter summary, generation timestamp, requester or schedule origin, record count, file size, and retention timestamps  
   **And** the user can distinguish generated metadata from the downloadable CSV content itself.

2. **Given** an artifact is downloaded or revoked  
   **When** the action completes  
   **Then** the system records the access or revocation event for later audit review  
   **And** retrieval telemetry is updated accordingly.

## Epic 5: Scheduled Exports and Run History

Authorized users can define recurring exports, trust their execution windows and idempotency rules, and review run history without engineering intervention.

### Story 5.1: Create Schedules with Relative Windows and Filter Snapshots

As a compliance lead,  
I want to create a recurring export schedule from reusable criteria,  
So that recurring reporting windows are resolved automatically without changing when the source filter is edited later.

**FRs:** FR-3, FR-4, FR-9, FR-13  
**NFRs:** NFR-4, NFR-8

**Acceptance Criteria:**

1. **Given** an authorized user creates a scheduled export  
   **When** they choose cadence, timezone, and either ad hoc criteria or a saved filter  
   **Then** the schedule stores a relative execution-window rule and shows the calculated next run time  
   **And** daily, weekly, and monthly recurrence are supported in MVP.

2. **Given** a schedule is created or updated from a saved filter  
   **When** the schedule is persisted  
   **Then** the effective filter definition is snapshotted on the schedule itself  
   **And** later edits to the source saved filter do not silently alter the active schedule.

### Story 5.2: Manage Schedule Lifecycle and Next-Run Visibility

As an administrator,  
I want to edit, pause, resume, and logically delete schedules,  
So that recurring audit obligations can be controlled without losing traceability.

**FRs:** FR-9, FR-13  
**NFRs:** NFR-2, NFR-4

**Acceptance Criteria:**

1. **Given** an authorized user manages an existing schedule  
   **When** they edit cadence or metadata, pause it, resume it, or logically delete it  
   **Then** the schedule status and next-run visibility update accordingly  
   **And** the action is constrained by ownership and permission rules.

2. **Given** a schedule has historical runs or linked exports  
   **When** it is paused or deleted  
   **Then** prior run and export history remains visible for auditability  
   **And** future execution behavior changes only according to the new schedule status.

### Story 5.3: Execute Due Schedules Idempotently via External Scheduler Invocation of the Internal Execution Endpoint

As an operations lead,  
I want due schedules to create exactly one run per execution window when an external scheduler calls the internal execution endpoint,
So that recurring evidence generation is trustworthy even if the trigger fires more than once.

**FRs:** FR-9, FR-10, FR-11, FR-16  
**NFRs:** NFR-2, NFR-4, NFR-6

**Acceptance Criteria:**

1. **Given** an external scheduler invokes the internal due-schedule execution endpoint for a point in time
   **When** the system resolves schedules that are due  
   **Then** it creates run records with the resolved execution window, trigger timestamp, and linked export reference  
   **And** it advances next-run tracking according to cadence and timezone rules  
   **And** the trigger path is authenticated for machine-to-machine use and supports bounded batch execution controls for safe operations.

2. **Given** the same schedule and resolved execution window are triggered more than once  
   **When** the duplicate attempt is processed  
   **Then** the system safely skips or resolves the duplicate without creating multiple runs or exports for the same window  
   **And** the duplicate handling outcome is visible for operations review.

### Story 5.4: Review Run History and Scheduled-Export Failures

As a compliance lead,  
I want to see the history and outcomes of scheduled runs,  
So that I can prove whether recurring exports were generated on time and understand what failed when they were not.

**FRs:** FR-10, FR-14  
**NFRs:** NFR-2, NFR-7

**Acceptance Criteria:**

1. **Given** an authorized user views a schedule and its runs  
   **When** they inspect run history  
   **Then** each run shows trigger time, effective execution window, lifecycle state, completion time, failure summary when relevant, and any linked export or artifact reference  
   **And** the history is ordered so recent compliance activity can be reviewed quickly.

2. **Given** a scheduled run fails  
   **When** the failure is presented to the user  
   **Then** the system exposes a non-sensitive failure summary with a next-action hint  
   **And** the failure remains traceable without exposing stack traces, secrets, or audit payload contents.

## Epic 6: Cleanup, Observability, and Self-Auditing

Operations, compliance, and support teams can trust the feature in production because cleanup, telemetry, support-facing status, and self-auditing are complete and governable.

### Story 6.1: Cleanup Expired Artifacts While Preserving Defensible Metadata

As an operations lead,  
I want expired export artifacts to be cleaned up automatically without erasing history,  
So that storage remains governed while the audit trail stays defensible.

**FRs:** FR-12, FR-15  
**NFRs:** NFR-4, NFR-5, NFR-8

**Acceptance Criteria:**

1. **Given** an export artifact has reached its expiry threshold  
   **When** cleanup is executed  
   **Then** the bytes are removed or revoked according to policy  
   **And** the export metadata remains queryable with an `expired` or equivalent terminal state.

2. **Given** cleanup processes multiple expired artifacts  
   **When** the run completes  
   **Then** the system records how many artifacts were processed, expired, skipped, or failed  
   **And** cleanup failures do not silently remove metadata visibility for affected exports.

### Story 6.2: Provide Telemetry, Safe Logs, and Support-Facing Status Clarity

As a support engineer,  
I want export and schedule health to be diagnosable from safe product and operational signals,  
So that I can triage common issues without direct queue or storage access.

**FRs:** FR-14  
**NFRs:** NFR-3, NFR-5, NFR-7

**Acceptance Criteria:**

1. **Given** exports and schedules are running in production  
   **When** operational telemetry is reviewed  
   **Then** the feature exposes counts and timings for requests, queueing, completions, failures, row counts, schedule lag, downloads, expiries, and revocations  
   **And** the emitted signals are sufficient to identify stuck or degraded flows.

2. **Given** support or engineering staff inspect feature logs and status resources  
   **When** they diagnose a problem  
   **Then** they can correlate work using safe identifiers, state transitions, and failure classes  
   **And** no raw filter payloads, audit rows, CSV bytes, or secret storage references are logged.

### Story 6.3: Ensure End-to-End Self-Auditing and Policy-Driven Governance

As a compliance owner,  
I want every filter, export, schedule, and artifact action to be auditable under one governance model,  
So that the feature meets its own accountability requirements and remains explainable over time.

**FRs:** FR-15  
**NFRs:** NFR-4, NFR-8

**Acceptance Criteria:**

1. **Given** a user creates, edits, deletes, requests, pauses, resumes, downloads, revokes, or otherwise manages export-related resources  
   **When** the action completes  
   **Then** the system creates a corresponding audit record with actor, action, ownership scope, and outcome  
   **And** those feature actions are visible to the same governed audit-review surface used elsewhere.

2. **Given** browse, export generation, and artifact retrieval all rely on governance rules  
   **When** policy is applied  
   **Then** retention, access, and redaction remain configurable at policy level  
   **And** the effective redaction or governance context used for exports remains historically explainable.

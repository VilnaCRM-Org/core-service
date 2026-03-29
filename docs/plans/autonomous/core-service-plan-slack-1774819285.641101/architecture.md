# Architecture Decision Document - Audit Log Export with Saved Filters and Scheduled Exports

**Date:** 2026-03-29  
**Status:** Draft  
**Scope:** Planning only, no implementation  
**Product Area:** VilnaCRM core-service

## 1. Architectural Summary

This feature should be introduced as a dedicated bounded context, `Core/AuditLog`, rather than extending `Shared`, `Core/Customer`, or `Internal/HealthCheck`. The design must preserve the repository's existing Symfony, API Platform, MongoDB, DDD, CQRS, and hexagonal patterns while staying explicit about resource discovery and operational boundaries.

The core architectural decisions are:

- `Core/AuditLog` owns saved filters, export requests, export schedules, run history, and artifact metadata.
- Audit record browsing and export generation share one normalized filter model.
- Public product contracts are REST-first for export lifecycle and artifact retrieval; GraphQL is optional and limited to browse/filter use cases.
- Feature-owned metadata is stored in MongoDB; generated CSV bytes are stored outside MongoDB through a storage port.
- Export generation uses a dedicated async job transport. Durable export state is written before any async dispatch.
- The current resilient async event bus remains acceptable for secondary side effects, but it must not be the authoritative durability mechanism for audit capture or export job state.
- Scheduled exports are triggered by an external scheduler calling a core-service-owned internal execution endpoint.
- Sensitive audit data is never cached publicly and is never logged as raw payloads.

## 2. Context and Constraints

The repository already establishes several constraints that this design must respect:

- Bounded contexts follow `Domain`, `Application`, and `Infrastructure` layers with no framework dependencies in `Domain`.
- API Platform resource discovery is explicit through `config/packages/api_platform.yaml` and YAML resource files under `config/api_platform/resources`.
- Current write flows use DTOs plus API Platform processors that transform input and dispatch synchronous commands.
- Current async processing in `config/packages/messenger.yaml` is specialized for `DomainEventEnvelope` and routed through a resilient, availability-first event bus.
- Existing observability avoids logging payloads that may contain PII and already emits endpoint and async-failure metrics.
- No audit-log bounded context, scheduler subsystem, or artifact storage subsystem exists today.
- Customer resources currently use HTTP caching, but audit resources should not inherit that behavior because freshness and confidentiality are more important than read caching.

The design also inherits three upstream product constraints from the PRD and research:

- CSV is the only required export format for MVP.
- Schedule triggering defaults to an external scheduler invoking core-service-owned execution.
- The authoritative audit dataset exists separately or is planned in parallel; this architecture covers exportability and lifecycle management, not capture implementation.

## 3. Bounded Context Layout

### 3.1 New Bounded Context

The feature should live under:

```text
src/Core/AuditLog/
  Application/
    Command/
    CommandHandler/
    DTO/
    Message/
    MessageHandler/
    Policy/
    Processor/
    Provider/
    Service/
    Transformer/
  Domain/
    Entity/
    Event/
    Exception/
    Repository/
    Service/
    ValueObject/
  Infrastructure/
    Filter/
    Query/
    Repository/
    Scheduler/
    Storage/
```

### 3.2 Configuration Footprint

The implementation footprint should be explicit and predictable:

- Add `src/Core/AuditLog/Domain/Entity` to `api_platform.resource_class_directories`.
- Add new YAML resource mappings under `config/api_platform/resources/`:
  - `audit_log_entry.yaml`
  - `audit_saved_filter.yaml`
  - `audit_export.yaml`
  - `audit_export_schedule.yaml`
  - `audit_export_run.yaml`
- Add service aliases and filter registrations in `config/services.yaml`.
- Add one or more dedicated Messenger transports in `config/packages/messenger.yaml` for export jobs and their failure handling.
- Keep internal scheduler-trigger routes separate from public product resources.

### 3.3 Boundary Responsibility

`Core/AuditLog` should own:

- Saved filter lifecycle
- Export request lifecycle
- Export schedule lifecycle
- Schedule run history
- Artifact metadata and controlled retrieval
- Audit-specific redaction, retention, and access policy integration
- Export-specific observability and self-auditing

`Core/AuditLog` should not own:

- General-purpose internal cron infrastructure
- Platform-wide identity or authorization definitions
- Cross-service audit aggregation
- Email attachment delivery
- The authoritative audit capture mechanism unless a later design explicitly folds it into this bounded context

## 4. Implementation Patterns and Consistency Rules

To fit the repository's current style and reduce future implementation drift, this feature should follow these patterns.

### 4.1 Command and Write Pattern

All public writes should use the same path already visible in customer flows:

- API Platform input DTO
- Application processor
- Command creation
- Command bus dispatch
- Command handler
- Domain repository persistence
- Optional domain event publication after durable state change

This keeps write orchestration consistent with `CreateCustomerProcessor.php` and the current command-handler model.

### 4.2 Read Pattern

Two read patterns are needed:

- Feature-owned persisted aggregates such as saved filters, exports, schedules, and runs can use Mongo-backed repositories and standard API Platform ODM reads.
- Audit log entry browsing should use a query port, because the authoritative audit dataset is unresolved. That surface should use a provider or query adapter backed by `AuditLogEntryQueryInterface` rather than assuming the data already lives in the same ODM model.

### 4.3 Resource Discovery Pattern

Resource exposure must remain explicit:

- No annotation-based discovery.
- One YAML mapping file per resource.
- REST and GraphQL operations declared intentionally, not by default.
- Internal operational routes kept separate from public product resources when the surface is not a domain resource.

### 4.4 Identifier and Pagination Pattern

All feature-owned aggregates should use ULIDs and cursor-oriented ordering, matching existing customer resources.

- Saved filters, exports, schedules, and runs should paginate by ULID descending.
- Audit log entry browsing should prefer a stable descending cursor. If the authoritative source does not use ULIDs, the adapter should expose an equivalent cursor based on `(occurredAt, stableId)` while preserving the public cursor model.

### 4.5 Cache Pattern

Do not introduce cache decorators or shared HTTP caching for audit surfaces by default.

- Collection and item reads should default to private, no-store semantics.
- Artifact downloads should always return `Cache-Control: no-store`.
- Any later caching must be justified against confidentiality, freshness, and revocation behavior.

## 5. Domain Model

### 5.1 Core Aggregates and Read Models

| Model                 | Kind           | Responsibility                                                                        |
| --------------------- | -------------- | ------------------------------------------------------------------------------------- |
| `AuditLogEntry`       | Read model     | Read-only representation of authoritative audit records for browse and export queries |
| `SavedAuditFilter`    | Aggregate root | Stores reusable named filter definitions and ownership metadata                       |
| `AuditExport`         | Aggregate root | Tracks one export request from creation through expiry or failure                     |
| `AuditExportSchedule` | Aggregate root | Stores recurring export intent, cadence, timezone, and next-run state                 |
| `AuditExportRun`      | Aggregate root | Records one scheduled execution window, idempotency key, status, and linked export    |

### 5.2 Key Value Objects

The domain should use explicit value objects rather than raw arrays for the behavior that must stay consistent:

- `AuditFilterDefinition`
- `AbsoluteTimeWindow`
- `RelativeTimeWindow`
- `ResolvedExecutionWindow`
- `ExportFormat`
- `ExportStatus`
- `ScheduleStatus`
- `ScheduleCadence`
- `ScheduleTimezone`
- `ArtifactMetadata`
- `OwnershipScope`
- `FailureSummary`
- `RedactionPolicyVersion`

### 5.3 Aggregate Responsibilities

`SavedAuditFilter` should contain:

- Name
- Optional description
- Owner scope
- Creator identity
- Normalized filter definition
- Soft-delete or archived status
- Audit timestamps

`AuditExport` should contain:

- Immutable effective filter snapshot
- Source type: ad hoc, saved filter, or schedule run
- Requester or triggering actor
- Ownership scope
- Current lifecycle state
- Requested, queued, started, completed, failed, and expired timestamps
- Artifact metadata when available
- Retry lineage when the export was recreated from a previous failed export
- Failure summary that is safe for product display

`AuditExportSchedule` should contain:

- Name
- Creator and owner scope
- Saved-filter reference when relevant
- Immutable filter snapshot used for execution
- Relative time window rules for recurring execution
- Cadence and timezone
- Current status
- `nextRunAt`
- Last successful and last attempted run references

`AuditExportRun` should contain:

- Schedule reference
- Resolved execution window
- Trigger source
- Idempotency key
- Linked export reference
- Lifecycle state
- Failure summary
- Start and completion timestamps

### 5.4 Domain Invariants

The design should enforce these invariants:

- A saved filter is mutable, but every export stores an immutable effective filter snapshot.
- A schedule must also store an immutable filter snapshot, so later saved-filter edits do not silently change active recurring behavior.
- Retry should create a new export linked to the failed one, not mutate the old export in place.
- One schedule can create at most one run for a given resolved execution window.
- An artifact can only be downloaded while the linked export is in an available state and the artifact is not expired or revoked.
- Redaction policy must be applied consistently across browse and export surfaces.

### 5.5 Lifecycle States

`AuditExport` should expose at least:

- `requested`
- `queued`
- `processing`
- `available`
- `failed`
- `expired`

The model may additionally support `revoked` for explicit administrative revocation without violating the PRD.

`AuditExportSchedule` should expose at least:

- `active`
- `paused`
- `deleted`

`AuditExportRun` should expose at least:

- `queued`
- `processing`
- `completed`
- `failed`

## 6. API Surface

## 6.1 Public REST Resources

The public product surface should remain REST-first and explicit.

| Resource                               | Operations               | Notes                                                        |
| -------------------------------------- | ------------------------ | ------------------------------------------------------------ |
| `/api/audit-log/entries`               | `GET collection`         | Shared normalized filter model; read-only browse surface     |
| `/api/audit-log/entries/{id}`          | optional `GET item`      | Only if the authoritative source supports stable item lookup |
| `/api/audit-log/filters`               | `GET collection`, `POST` | Create reusable saved filters                                |
| `/api/audit-log/filters/{id}`          | `GET`, `PATCH`, `DELETE` | Delete should be logical, not destructive                    |
| `/api/audit-log/exports`               | `GET collection`, `POST` | `POST` creates a durable export record immediately           |
| `/api/audit-log/exports/{id}`          | `GET`                    | Export status, metadata, timestamps, and failure summary     |
| `/api/audit-log/exports/{id}/artifact` | custom `GET`             | Authenticated CSV retrieval only                             |
| `/api/audit-log/export-schedules`      | `GET collection`, `POST` | Create recurring schedules                                   |
| `/api/audit-log/export-schedules/{id}` | `GET`, `PATCH`, `DELETE` | `PATCH` covers edit, pause, and resume                       |
| `/api/audit-log/export-runs`           | `GET collection`         | Read-only run history                                        |
| `/api/audit-log/export-runs/{id}`      | `GET`                    | Individual run details                                       |

### 6.2 Filter Model

All browse, saved-filter, export, and schedule flows should use one normalized filter definition. That definition should align with the repository's API Platform filter style rather than storing raw query strings.

The MVP filter model should cover:

- Actor
- Action
- Target resource type
- Target resource id
- Outcome
- Time window

The public query surface should feel similar to existing API Platform filters. The persisted filter definition should therefore store normalized criteria that can round-trip cleanly between:

- REST query parameters
- Saved filter storage
- Export request bodies
- Schedule snapshots

### 6.3 REST Write Semantics

The write surface should follow these semantics:

- `POST /api/audit-log/exports` returns a created export resource immediately in `requested` or `queued` state.
- `POST /api/audit-log/exports` may be based on ad hoc criteria, a saved filter id, or a previous export id for retry.
- `PATCH /api/audit-log/export-schedules/{id}` should allow both schedule edits and explicit pause or resume intent.
- `DELETE` on saved filters and schedules should be logical deletion so history remains auditable.

### 6.4 GraphQL Scope

GraphQL should be optional and intentionally smaller than REST for MVP.

Recommended GraphQL scope:

- Optional `Query` and `QueryCollection` for `AuditLogEntry`
- Optional `Query` and `QueryCollection` for `SavedAuditFilter`
- Optional filter CRUD mutations only if a consumer needs them immediately

Not recommended for MVP:

- Export creation and lifecycle management
- Schedule management
- Artifact download
- Internal schedule execution

This keeps GraphQL from duplicating the most operationally sensitive workflows.

### 6.5 Internal Operational Endpoint

Scheduled execution should be triggered by an internal service endpoint rather than a public user-facing resource.

Recommended shape:

- `POST /internal/audit-log/export-schedules/execute-due`

This endpoint should:

- Require machine-to-machine authentication
- Accept an execution timestamp and optional batch controls
- Resolve due schedules
- Create idempotent run records
- Create linked export records
- Enqueue export generation
- Return counts for created, skipped, and failed work items

This endpoint is operational, not part of the public product contract.

## 7. Persistence Strategy

### 7.1 Feature-Owned Metadata Store

MongoDB should remain the default store for feature-owned metadata because that matches current repository persistence patterns.

Recommended collections:

- `audit_saved_filters`
- `audit_exports`
- `audit_export_schedules`
- `audit_export_runs`

### 7.2 Artifact Bytes

Generated CSV bytes should not be stored in MongoDB documents. They should be stored through an artifact storage port such as `AuditExportArtifactStorageInterface`.

The export aggregate should persist only metadata:

- Storage key
- File name
- Content type
- Size
- Checksum
- `availableAt`
- `expiresAt`
- `revokedAt`

This keeps Mongo focused on durable lifecycle state and keeps file delivery concerns replaceable.

### 7.3 Audit Source Query Port

The authoritative audit dataset should be accessed through a port such as `AuditLogEntryQueryInterface`.

That port shields the feature from the still-unresolved capture decision and allows one of two later outcomes without changing the public contract:

- The audit source becomes a Mongo-backed local repository in this service.
- The audit source is provided by another internal adapter or data source.

### 7.4 Indexing Strategy

Minimum indexing should cover the product's dominant access paths.

`audit_saved_filters`:

- `(ownerScope, status, updatedAt desc)`
- `(creatorId, createdAt desc)`

`audit_exports`:

- `(ownerScope, createdAt desc)`
- `(status, createdAt desc)`
- `(expiresAt)`
- `(sourceScheduleId, createdAt desc)`

`audit_export_schedules`:

- `(status, nextRunAt asc)`
- `(ownerScope, updatedAt desc)`

`audit_export_runs`:

- unique `(scheduleId, executionWindowStart, executionWindowEnd)`
- `(scheduleId, createdAt desc)`
- `(status, createdAt desc)`

The audit source itself must also support the filter model efficiently. If the authoritative dataset is local, it should be indexed for actor, action, target, outcome, and time-ordered scans.

### 7.5 Deletion and Retention

Do not hard-delete user-facing history by default.

- Saved filters and schedules should be logically deleted.
- Exports and runs should remain queryable after failure or expiry.
- Artifact bytes may be physically removed after expiry, but metadata should remain so the trail is still defensible.

## 8. Async Processing and Scheduling Model

### 8.1 Export Processing

The export request path should be:

1. Validate input and access policy.
2. Create and persist `AuditExport` with immutable filter snapshot and `requested` state.
3. Dispatch a dedicated job message such as `GenerateAuditExportMessage`.
4. If enqueue succeeds, transition the export to `queued`.
5. If enqueue fails, keep the export durable and visible, record a retryable failure summary, and do not lose the request.
6. Worker transitions the export to `processing`, streams query results, writes CSV to artifact storage, and updates the aggregate to `available` or `failed`.

This ensures that state transitions remain durable even when the queue or worker layer is impaired.

### 8.2 Dedicated Async Transport

The current resilient async event bus is not sufficient as the authoritative mechanism for export jobs because it is designed to prefer availability and to swallow dispatch failures.

This feature should therefore add a dedicated Messenger transport for export jobs and a dedicated failure transport for those jobs.

The transport should carry explicit job messages such as:

- `GenerateAuditExportMessage`
- `ExpireAuditExportArtifactMessage`

Domain events may still be published after durable state changes for secondary purposes such as metrics, notifications, or downstream cache invalidation, but those events must not be the sole durable record of requested work.

### 8.3 Schedule Execution

An external scheduler should trigger schedule execution through the internal endpoint described above. Core-service should own the scheduling behavior once triggered.

For each due schedule, the application service should:

1. Resolve the relative window into an absolute execution window using the schedule timezone.
2. Build a deterministic idempotency key from schedule id and resolved window.
3. Attempt to create a run record keyed by that idempotency value.
4. If the run already exists, skip it safely.
5. If the run is new, create a linked export record from the schedule snapshot.
6. Enqueue export generation.
7. Advance `nextRunAt`.

This model keeps the scheduler external but keeps execution semantics, idempotency, and history inside the service.

### 8.4 Expiry Sweep

Artifact expiry should be enforced in two places:

- Synchronously on the download path by checking `expiresAt` and `revokedAt`
- Asynchronously by a cleanup use case that deletes or revokes expired objects in storage and marks export metadata accordingly

The cleanup use case can be invoked by the same external scheduler pattern. That avoids introducing an internal cron subsystem.

## 9. Artifact Delivery Model

### 9.1 MVP Delivery Choice

MVP should use authenticated retrieval through core-service rather than direct email attachment delivery or blind storage exposure.

The download endpoint should:

- Authorize the caller
- Verify current export state
- Check retention and revocation status
- Stream CSV bytes from artifact storage
- Emit retrieval telemetry
- Create an audit trail entry for access

### 9.2 Metadata Pairing

CSV files should remain plain CSV. Export metadata should be paired with the file through the `AuditExport` resource rather than embedded into the file format.

The status resource should expose:

- Effective filter summary
- Generation timestamp
- Requester or schedule origin
- Record count
- File size
- Retention timestamps
- Failure summary when relevant

### 9.3 Future Flexibility

The artifact delivery abstraction should allow a later optimization where the same authenticated endpoint returns a short-lived redirect or signed URL. That decision should not leak into the domain model. The public contract remains the controlled core-service access path.

### 9.4 Response Semantics

Recommended retrieval outcomes:

- `200 OK` for successful streaming
- `403 Forbidden` for authenticated callers without access
- `404 Not Found` when the resource is unknown in scope
- `410 Gone` when the artifact existed but is expired or revoked

## 10. Security, Privacy, and Compliance

### 10.1 Access Policy Integration

The domain model should remain free of framework security dependencies. Authorization belongs in application-layer policies or adapters that evaluate:

- Who may browse audit entries
- Who may create or reuse saved filters
- Who may request exports
- Who may manage schedules
- Who may download artifacts
- Who may act across ownership boundaries

Because the repository does not currently expose a definitive ownership model, resources should carry explicit owner-scope metadata and leave exact policy enforcement to a platform-integrated policy adapter.

### 10.2 Redaction

One redaction policy should govern both browse and export behavior. The worker must not export fields that the browse surface would hide.

The effective redaction policy or policy version should be captured on the export aggregate so that historical artifacts remain explainable.

### 10.3 Logging and Secrets

Follow the existing observability discipline already visible in the async event dispatcher:

- Do not log raw filter payloads when they may contain sensitive identifiers.
- Do not log audit row contents.
- Do not log CSV bytes, object contents, or secret storage URLs.
- Restrict logs to identifiers, states, counts, durations, and safe failure classes.

### 10.4 Self-Auditing

The feature itself must be auditable. At minimum, these actions should create auditable records:

- Saved filter create, update, delete
- Export request
- Export failure and completion
- Schedule create, update, pause, resume, delete
- Schedule execution
- Artifact download
- Artifact revocation
- Artifact expiry

This depends on the authoritative audit dataset being able to receive these feature actions.

### 10.5 HTTP Caching and Privacy Headers

All public audit surfaces should default to private, non-cacheable responses. Artifact downloads should be `no-store`.

## 11. Observability and Operational Support

### 11.1 Metrics

The feature should emit product and operational metrics for:

- Export requests created
- Export jobs queued
- Export completions
- Export failures
- Export duration
- Export row counts
- Schedule executions attempted
- Schedule executions skipped as duplicates
- Schedule failures
- Schedule lag
- Artifact downloads
- Artifact expiries
- Artifact revocations

The current endpoint business metrics subscriber can continue to emit generic API invocation counts, but feature-specific metrics are still needed.

### 11.2 Structured Logging

Worker and API logs should include safe correlation fields such as:

- `export_id`
- `schedule_id`
- `run_id`
- `actor_id` when safe
- `owner_scope`
- `state_transition`
- `failure_class`
- `storage_provider`
- `record_count`

Logs should exclude sensitive filter and row content.

### 11.3 Failure Surfacing

Operational failures should be visible in three places:

- User-facing export and run status resources
- Worker/failure transport telemetry
- Aggregated operational dashboards or alerting

The user-facing layer should expose non-sensitive failure summaries only.

### 11.4 Supportability

Common support scenarios should be diagnosable from product surfaces without direct queue or storage access:

- Stuck in `requested`
- Stuck in `queued`
- Worker failure
- Artifact expired
- Artifact revoked
- Duplicate schedule trigger skipped
- Invalid schedule filter snapshot

## 12. Testing Strategy

The design should support the repository's existing quality bar and test layering.

### 12.1 Unit Tests

Unit tests should cover:

- Aggregate invariants
- Filter normalization
- Relative window resolution
- Idempotency key generation
- State transition rules
- Redaction policy behavior
- Retention and revocation checks

### 12.2 Integration Tests

Integration tests should cover:

- Mongo repository persistence and indexing behavior
- API Platform processors and DTO validation
- Query provider behavior for audit browsing
- Schedule execution service idempotency
- Worker message handling with in-memory Messenger transports
- Artifact storage adapters using test doubles or isolated test storage

### 12.3 API Tests

API-level tests should cover:

- Public REST resource behavior
- Export creation and status polling
- Schedule CRUD and pause/resume
- Artifact access authorization and expiry responses
- Optional GraphQL browse and filter operations if those are added

### 12.4 End-to-End Scenarios

End-to-end coverage should include:

- Create saved filter -> request export -> poll status -> retrieve CSV
- Create schedule -> external trigger -> run creation -> export completion
- Retry failed export through new export creation
- Expired artifact retrieval returns the expected terminal response
- Unauthorized access to another scope's export is denied

### 12.5 Quality Gates

Any implementation should inherit the repository's existing CI expectations, including full test coverage, zero Psalm errors, zero Deptrac violations, and no lowered thresholds.

## 13. Rollout and Migration Notes

### 13.1 Recommended Delivery Sequence

1. Define the authoritative audit query contract and adapter boundary.
2. Introduce the `Core/AuditLog` bounded context and explicit API Platform resource discovery.
3. Deliver read-only audit browsing and saved filters.
4. Deliver on-demand exports with durable lifecycle state and dedicated job transport.
5. Deliver authenticated artifact retrieval and expiry enforcement.
6. Deliver schedule management and the internal due-schedule execution endpoint.
7. Deliver cleanup sweeps, dashboards, and optional GraphQL browse/filter support.

### 13.2 Configuration and Infrastructure Changes

Expected brownfield changes include:

- `config/packages/api_platform.yaml` resource discovery update
- New YAML resource files under `config/api_platform/resources`
- New service aliases, filters, and policy adapters in `config/services.yaml`
- New Messenger transports and routing in `config/packages/messenger.yaml`
- New Mongo collections and indexes
- New storage-provider configuration for artifact bytes

### 13.3 Data Migration

No customer-domain migration is required for feature metadata. New feature collections can be added independently.

Historical usefulness, however, depends on the authoritative audit dataset. If older audit records are unavailable, the export feature can still launch for new records, but historical scope will be limited.

### 13.4 Rollout Controls

If the platform supports it, rollout should be gated by permission or feature flag so operations can validate storage, queueing, and retention behavior before broad exposure.

## 14. Key Risks and Tradeoffs

- The authoritative audit dataset is still unresolved. This is the main architectural dependency.
- The repository does not currently expose a definitive ownership and authorization model. Access policy integration remains a prerequisite.
- A dedicated async job transport adds configuration complexity, but it is the safest way to avoid overloading the current event-bus design with authoritative work state.
- Service-mediated downloads are simpler and more governable than direct storage exposure, but they increase application bandwidth responsibility.
- Avoiding caching is correct for privacy and freshness, but it may cost read throughput for large browse workloads.
- External schedule triggering keeps the service simple and consistent with current repo state, but it introduces an external operational dependency.
- Schedule idempotency should rely on unique run keys and retry-safe application logic rather than assuming multi-document transactions are always available.
- CSV-only MVP reduces implementation surface and governance complexity, but future consumers may want richer formats later.

## Assumptions Made

- `Core/AuditLog` is the correct bounded context for this feature unless a stronger repo constraint appears later.
- The authoritative audit dataset will exist behind a query adapter before export implementation begins.
- Saved filters and schedules must snapshot effective filter definitions for reproducibility.
- Export retries should create new export records rather than mutating failed records in place.
- CSV metadata is paired through the export status resource rather than embedded in the file itself.
- External scheduler triggering is the default operational model for schedule execution and expiry cleanup.
- Artifact bytes will live outside MongoDB, with MongoDB storing lifecycle metadata only.
- GraphQL is optional and should stay narrower than REST for MVP.

## Unresolved Questions

- What concrete audit entities and event types are included in the authoritative audit dataset for core-service?
- Is the authoritative audit dataset allowed to be eventually consistent, or must it be loss-intolerant?
- What is the exact ownership and cross-user administration model for filters, exports, schedules, and artifacts?
- What storage provider will back CSV artifacts in production?
- What default retention and expiry values apply to generated artifacts?
- Should an empty result set still produce a downloadable CSV, or should it produce a terminal no-data state?
- Is stable item lookup for individual audit log entries required, or is collection browsing sufficient for MVP?
- What scale targets for record volume, export size, and completion latency should size the worker and storage configuration?
- Should artifact revocation be an explicit MVP user action, or only an administrative/retention action?
- Is optional GraphQL support for saved filters needed in MVP, or can all write workflows remain REST-only?

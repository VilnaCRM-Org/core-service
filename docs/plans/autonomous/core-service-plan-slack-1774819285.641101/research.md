# Research: Audit Log Export With Saved Filters and Scheduled Exports

## Research Frame

- This draft follows the BMALPH `analyst` command surface described in `_bmad/COMMANDS.md`, the local `bmad-bmalph` wrapper, and the repository's autonomous planning contract.
- `_bmad/config.yaml` identifies this repository as a Codex BMALPH project with planning artifacts rooted in `_bmad-output/planning-artifacts`.
- `_bmad/bmm/agents/analyst.agent.yaml` frames the analyst stage as evidence-driven research and requirements discovery, so this document stays focused on verified repository state, inferred surface area, risks, assumptions, and planning decisions.
- This is planning only. No implementation approach is treated as final in this document.

## Current Repository State

| Area                     | Observed state                                                                                                                                                                                                                                                                           | Why it matters                                                                                                                                                                                        |
| ------------------------ | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Service architecture     | The service is a Symfony 7 + API Platform 4 + GraphQL + MongoDB application with DDD, CQRS, and hexagonal boundaries. This is reinforced across `AGENTS.md`, `docs/design-and-architecture.md`, `docs/getting-started.md`, `docs/onboarding.md`, and `docs/developer-guide.md`.          | Any new feature is expected to fit those patterns rather than introducing a parallel architecture.                                                                                                    |
| Current bounded contexts | The documented bounded contexts are `Shared`, `Core/Customer`, and `Internal/HealthCheck`. No audit-specific or export-specific bounded context is documented today.                                                                                                                     | Audit logging is not an obvious fit for the current `Customer` or `HealthCheck` contexts, and `Shared` is described as foundational support rather than feature-domain ownership.                     |
| API Platform shape       | API resources are configured through YAML mappings, and current write flows use API Platform processors plus GraphQL mutation resolvers that validate/transform input and dispatch commands. `CreateCustomerProcessor.php` and the customer GraphQL resolver show this pattern directly. | Saved filters, export requests, and schedule management should be planned against existing processor/resolver + command-dispatch conventions.                                                         |
| Resource discovery       | `config/packages/api_platform.yaml` explicitly points API Platform resource discovery at current customer and health-check domain directories.                                                                                                                                           | A new audit domain would be an explicit addition to the service, not something already wired by convention.                                                                                           |
| Filtering and pagination | Customer collections already use shared search, order, date, boolean, and ULID range filters. `UlidRangeFilter.php` supports `lt`, `lte`, `gt`, `gte`, and `between`, and customer collections use cursor pagination by ULID.                                                            | Saved filters can align with an existing filter-parameter model instead of inventing a new query DSL. Audit-log browsing will likely benefit from the same cursor-friendly, time-ordered conventions. |
| Persistence pattern      | Mongo repositories are used behind domain interfaces. `MongoCustomerRepository.php` is deliberately persistence-only, while `config/services.yaml` shows interface-to-implementation aliasing and a cache-decorator pattern around the customer repository.                              | A new audit feature can follow the same repository/interface split, but caching should be a deliberate choice rather than a default.                                                                  |
| Async processing pattern | The command bus is synchronous/in-memory. Existing async processing is centered on domain events routed through Symfony Messenger to SQS via `ResilientAsyncEventBus`, `ResilientAsyncEventDispatcher`, and `config/packages/messenger.yaml`.                                            | Heavy export generation fits the repo's async direction, but the current queue setup is event-oriented, not a ready-made async command/job pipeline.                                                  |
| Observability pattern    | The service already emits API endpoint business metrics and async failure metrics. The observability and async code explicitly avoid logging payloads when they may contain PII.                                                                                                         | Audit exports are likely sensitive. Planning has to include redaction, limited logging, and measurable success/failure states from the start.                                                         |
| Feature-area discovery   | A narrow repository search over `src/`, `config/`, and the reviewed docs found no existing audit-log domain, export subsystem, scheduler, or recurring-job implementation. The onboarding/getting-started docs also describe no such feature.                                            | This is a net-new feature area, not an extension of an already-implemented subsystem.                                                                                                                 |

## Inferred Feature Surface Area

The requested feature implies at least five distinct surfaces:

- An audit-log query surface for browsing and filtering audit records by actor, action, target, outcome, and time window.
- A saved-filter surface for storing named, reusable filter definitions in a normalized format.
- An on-demand export surface for requesting export generation and tracking export job state.
- A scheduled-export surface for defining recurring export rules, schedule state, and execution history.
- A delivery and artifact surface for generated files, downloadability, expiry, and failure handling.

The likely domain nouns are:

- `AuditLogEntry`
- `SavedAuditFilter`
- `AuditExport`
- `AuditExportSchedule`
- `AuditExportRun`

Cross-cutting concerns are also unavoidable:

- authorization and ownership
- retention and expiry
- redaction and sensitive-field handling
- observability for API, queue, and schedule health
- idempotency for repeated schedule execution

## Risks and Constraints

- There is no current audit source of truth in the repository. The service has generic command and event infrastructure, but no existing audit domain or capture model.
- The current resilient async event bus explicitly favors availability over consistency. Dispatch failures are logged and metriced, but they do not fail the main request. That is useful for non-critical side effects, but it may be too weak if audit capture must be loss-intolerant or compliance-grade.
- Messenger routing is currently specific to `DomainEventEnvelope`. Long-running export generation is therefore not a drop-in fit unless the architecture deliberately models export work as event handling or introduces a separate async job/command transport.
- No scheduler or recurring-job subsystem was found. Scheduled exports therefore require an explicit orchestration decision, not an assumption that the platform already has cron-like support inside this service.
- No export storage or delivery subsystem was found in the reviewed code paths. File storage, artifact serving, download links, and expiry behavior remain open planning topics.
- GraphQL is enabled and used in current resources, but file delivery is naturally a REST-shaped concern. Full REST/GraphQL parity may not be the right goal for every part of this feature.
- The existing repository shows a cache-decorator pattern for customer reads, but audit logs are freshness-sensitive and likely high volume. Caching audit queries or export payloads should not be assumed safe by default.
- The reviewed files do not expose a clear service-local authorization model for user-scoped ownership of saved filters, schedules, or export artifacts. Ownership and permission boundaries cannot be inferred confidently from current code alone.

## Assumptions

- This feature is net-new and should be planned as its own bounded context rather than being folded into `Shared`, `Core/Customer`, or `Internal/HealthCheck`.
- Saved filters are persisted query presets, not just transient request payloads.
- Export generation is asynchronous and status-driven rather than synchronous in the request/response path.
- MongoDB remains the default persistence choice for feature metadata unless later architecture work proves otherwise.
- Existing resilient async events may still be useful for notifications or downstream side effects even if audit capture itself needs stronger durability guarantees.
- Scheduled execution should default to an external scheduler invoking core-service-owned schedule processing because no internal scheduler exists in the current repository.

## Recommended Planning Decisions

1. Plan this as a dedicated bounded context, tentatively `Core/AuditLog`, instead of extending `Shared` or `Customer`.
2. Split the planning work into four distinct concerns: audit capture, audit querying/filtering, export execution, and schedule orchestration.
3. Make audit durability a first-class product and architecture decision. Do not assume the current AP-oriented resilient async event bus is sufficient for the authoritative audit record if event loss is unacceptable.
4. Model saved filters as persisted, named filter definitions that store normalized filter parameters aligned with existing API Platform filter conventions.
5. Model exports as asynchronous jobs with persistent lifecycle states such as `requested`, `queued`, `processing`, `available`, `failed`, and `expired`.
6. Decide the scheduling boundary early. Given current repo state, the lowest-friction default is an external scheduler triggering core-service-owned schedule execution, while core-service owns schedule definitions, idempotency, and run tracking.
7. Use REST as the primary interface for export request creation, status lookup, and artifact retrieval. Add GraphQL only where it adds clear value for audit browsing or saved-filter management.
8. Reuse existing service conventions where they fit: API Platform resources, processors for writes, resolvers when GraphQL parity matters, domain interfaces backed by Mongo repositories, and shared filter strategies for collection querying.
9. Define security and compliance requirements before architecture begins: who can see which audit records, who can create or run schedules, which fields are redacted, how long exports live, and how generated artifacts are revoked or expired.
10. Add feature-specific observability requirements to downstream planning: export requested/completed/failed counts, export latency, schedule success/failure, schedule lag or missed runs, and delivery failures.

## Planning Signal

The feature fits the repository's overall architectural direction, but three decisions should be treated as prerequisites for implementation-ready planning:

- the durability model for audit capture
- the scheduling/orchestration model for recurring exports
- the storage and delivery model for generated export artifacts

Without those decisions, the feature can be described at a product level, but architecture and story breakdown will remain ambiguous.

## Open Questions

- What exact actions and entities must be captured in the audit log for this service?
- Is audit capture allowed to be eventually consistent, or must it be loss-intolerant?
- Who owns saved filters and schedules: individual user, team, organization, or system-wide admin scope?
- Should scheduled exports reference the latest saved filter definition or snapshot filter criteria at schedule creation time?
- Which export formats are required: CSV, JSON, both, or something else?
- How are scheduled exports triggered in production: external scheduler, internal worker, or another platform service?
- Where do generated export artifacts live, and how are they delivered or downloaded?
- What retention, expiry, and redaction rules apply to both audit records and export artifacts?
- Is GraphQL parity required for saved filters and export management, or is REST-first acceptable for this feature?
- What volume, latency, and retention expectations should the audit log support from day one?

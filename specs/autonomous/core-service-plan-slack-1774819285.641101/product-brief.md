# Product Brief: Audit Log Export with Saved Filters and Scheduled Exports

## Executive Summary

VilnaCRM core-service currently has no documented audit-log export capability, no saved audit-filter workflow, and no recurring export scheduling. Teams that need repeatable audit evidence are left with manual querying, ad hoc report recreation, or engineering assistance. That creates slow investigations, inconsistent report scope, and unnecessary operational risk when the same export must be reproduced on demand or on a schedule.

This brief proposes a brownfield extension to core-service that makes audit exports self-service, repeatable, and governable. Authorized users should be able to filter audit records, save named filter presets, request exports asynchronously, and schedule recurring exports with visible run history and failure states. The capability must fit the repository's DDD, CQRS, and hexagonal architecture rather than introducing a parallel subsystem.

The business value is reduced time-to-evidence, fewer manual support requests, and more reliable compliance and operational reporting. This brief assumes an authoritative audit dataset will exist or be defined in parallel. The export feature is the product focus; audit capture completeness and durability remain prerequisite planning decisions.

## Core Vision

### Problem Statement

VilnaCRM stakeholders who need audit evidence cannot currently create repeatable, governed exports from core-service. Even when audit-relevant data exists, the workflow is likely manual, one-off, and dependent on individual knowledge of filters or engineering support. This makes recurring compliance, investigation, and oversight work slower and less reliable than it should be.

### Problem Impact

- Administrators and operations teams spend too much time rebuilding the same filter criteria for repeated exports.
- Compliance and security stakeholders lack a dependable self-service path to generate time-bounded, actor-bounded, or action-bounded audit evidence.
- Ad hoc export handling increases the risk of inconsistent scope, missing records, overexposed data, and poor repeatability.
- Engineering becomes a bottleneck for report generation and troubleshooting.
- Without scheduled delivery, recurring audit obligations are easy to miss or execute late.

### Why Existing Solutions Fall Short

The repository currently documents no audit bounded context, no export subsystem, no schedule orchestration, and no artifact delivery flow. Existing API Platform filters and async infrastructure are useful building blocks, but they do not provide a user-owned, repeatable export product. The status quo is not a missing screen. It is a missing capability.

### Proposed Solution

Add a brownfield audit-log export capability to core-service that lets authorized users:

- filter audit records using normalized criteria aligned with existing API filtering conventions
- save named filter presets for repeated use
- request on-demand exports and track lifecycle states such as `requested`, `queued`, `processing`, `available`, `failed`, and `expired`
- create scheduled exports that run on a recurring cadence against an approved filter definition
- retrieve generated artifacts through a controlled delivery path with retention, expiry, and redaction rules

The product should favor REST for export creation, status lookup, and artifact retrieval, while leaving GraphQL optional for audit browsing or saved-filter management where it adds clear value.

### Key Differentiators

- Self-service repeatability: users define a filter once and reuse it consistently for both immediate and recurring exports.
- Brownfield fit: the feature extends the repository's Symfony, API Platform, DDD, CQRS, and Mongo-backed patterns instead of inventing a separate platform.
- Governance by design: retention, redaction, lifecycle tracking, and observability are product requirements from the start.
- Operational realism: scheduled exports are planned around explicit external triggering and persistent run tracking because no internal scheduler exists in the current repository state.

## Target Users

### Primary Users

**Platform or tenant administrators**  
These users need dependable access to audit evidence for day-to-day oversight, incident follow-up, and policy checks. Success means finding the right records quickly, saving a trusted filter once, and regenerating the same export without rework.

**Compliance, security, or operations leads**  
These users are accountable for recurring reporting and defensible evidence trails. Success means receiving scheduled exports on time, trusting that the exported scope is consistent, and verifying job status or failures without engineering involvement.

### Secondary Users

**Support and investigation teams**  
They need fast access to scoped audit exports during incident review, escalation handling, or customer investigations.

**Engineering and platform teams**  
They are not the intended daily users, but they benefit when audit exports become self-service and observable instead of custom support work.

**Internal or external auditors**  
They may not configure exports themselves, but they are downstream consumers of the generated artifacts and require consistent, trustworthy output.

### User Journey

1. A user opens the audit-log experience and applies filters such as actor, action, target, outcome, and time window.
2. The user saves the filter as a named preset for future reuse.
3. The user either requests an immediate export or creates a recurring schedule tied to that filter.
4. The system processes the export asynchronously and exposes status, timestamps, and failure details.
5. When the export becomes available, the user retrieves it through the approved delivery path before expiry.
6. For recurring needs, the user monitors scheduled runs, adjusts the saved filter or schedule when needed, and reuses the same workflow without re-specifying query logic.

## Stakeholders

- Product and platform leadership, who need evidence that the feature reduces operational toil and fits the brownfield architecture.
- Security and compliance owners, who need consistent, timely, and defensible exports with redaction and retention controls.
- Operations and support leadership, who need faster investigations and fewer manual reporting steps.
- Engineering maintainers, who need a solution that preserves architectural boundaries and reduces bespoke report requests.

## Goals and Success Metrics

### User Goals

- Reduce the effort required to generate repeatable audit exports.
- Make recurring audit reporting self-service for authorized users.
- Ensure saved filters preserve consistency across repeated exports.
- Give users clear visibility into export state, schedule state, and failure conditions.
- Protect sensitive data through predictable redaction, access control, and expiry behavior.

### Business Objectives

- Lower the volume of engineering-assisted audit export requests.
- Improve readiness for compliance, security review, and operational investigations.
- Establish a reusable audit-export capability that can support future expansion of audit coverage.
- Add measurable operational visibility for export throughput, failure rates, and schedule health.

### Key Performance Indicators

- Median time from export request to artifact availability for standard export ranges.
- Percentage of export requests completed successfully without manual intervention.
- Percentage of recurring audit use cases handled through saved filters and schedules rather than ad hoc exports.
- Reduction in support or engineering requests related to manual audit report generation after rollout.
- Schedule success rate and missed-run rate for recurring exports.
- Percentage of generated exports retrieved or delivered before expiry.
- Number of security, privacy, or redaction incidents related to exported artifacts.

## Scope

### In Scope for the First Planning Slice

- Audit-log querying and filtering for export use cases.
- Named saved filters for reusable audit queries.
- On-demand export requests with persistent lifecycle tracking.
- Scheduled export definitions with recurring execution intent, run tracking, and failure visibility.
- Controlled artifact availability, expiry, and download or delivery workflow.
- Product-level security, observability, retention, and redaction requirements for the export lifecycle.

### Out of Scope for This Brief

- Detailed implementation design for audit capture, job orchestration, storage, or API schema.
- A full internal scheduler subsystem inside core-service.
- Broad GraphQL parity for every export and artifact workflow.
- Analytics, dashboarding, or non-audit reporting beyond export use cases.
- Cross-service enterprise audit aggregation beyond the core-service boundary.
- Final decisions on storage provider, delivery channel, or exact format set beyond what later planning resolves.

### MVP Success Criteria

- An authorized user can define a reusable audit filter, run an export without engineering help, and understand whether it succeeded or failed.
- A recurring audit export can be scheduled, executed, and tracked with clear run history and failure handling.
- Export lifecycle visibility is sufficient for operations teams to troubleshoot missed or failed runs without inspecting raw infrastructure internals.
- Generated artifacts follow explicit retention and access rules rather than ad hoc handling.

## Constraints and Planning Dependencies

- This must be a brownfield extension that respects the repository's documented DDD, CQRS, and hexagonal architecture.
- The current repository has no documented audit bounded context, scheduler, or export artifact subsystem, so those capabilities must be introduced deliberately rather than assumed.
- Existing async infrastructure is event-oriented and availability-first. Whether it is sufficient for audit-related durability remains a product and architecture decision.
- Audit exports are sensitive by nature, so payload logging, data exposure, and artifact retention must be constrained from day one.
- MongoDB is the default persistence direction for feature metadata unless later architecture work proves otherwise.
- The feature is planned as REST-first for export lifecycle and artifact access because file delivery maps more naturally to REST than GraphQL.
- The feature cannot be implementation-ready until the audit source-of-truth, scheduling trigger model, and artifact storage and delivery model are resolved.

## Future Vision

If this feature succeeds, VilnaCRM gains a reusable audit operations capability rather than a one-off export tool. Saved filters become durable reporting assets, scheduled exports become routine compliance infrastructure, and audit workflows move from reactive manual effort to predictable product behavior. Over time, the same foundation can support broader audit domains, stronger governance controls, and tighter integration with platform-wide security and operations processes without abandoning the core-service architectural model.

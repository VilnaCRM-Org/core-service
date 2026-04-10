# Migration Sync Checklist

This control artifact records whether each in-scope documentation surface changes during issue `#155` and why.
Story `1.1` initializes the required evaluation targets. Final changed-or-unchanged outcomes are recorded as later stories complete.

## Status Legend

- `pending`: not evaluated yet
- `changed`: edited during this issue
- `unchanged`: evaluated and intentionally left as-is
- `read-only reference`: consulted only and never edited in this issue

## Evaluation Targets

| Path or pattern | Group | Edit target | Final status | Rationale or notes |
| --- | --- | --- | --- | --- |
| `AGENTS.md` | Canonical routing docs | `yes` | `pending` | High-visibility repository routing guidance; evaluate during the canonical-routing stories. |
| `.claude/skills/AI-AGENT-GUIDE.md` | Canonical routing docs | `yes` | `pending` | AI-agent baseline guide; must stay aligned with repository routing policy. |
| `.claude/skills/SKILL-DECISION-GUIDE.md` | Canonical routing docs | `yes` | `pending` | Skill routing decision surface; evaluate for policy and taxonomy alignment. |
| `.claude/skills/README.md` | Canonical routing docs | `yes` | `pending` | Contributor-facing skill catalog mirror; evaluate after canonical routing settles. |
| `.agents/skills/**` | Wrapper layer | `conditional` | `pending` | Evaluate wrappers only where canonical routing language or BMALPH handoff wording changes; unchanged wrappers must still be recorded with rationale. |
| `README.md` | Repo-facing mirrors | `yes` | `pending` | Repo-facing contributor guide; sync after canonical routing and skill guidance settle. |
| `docs/getting-started.md` | Repo-facing mirrors | `yes` | `pending` | Setup and onboarding mirror; sync after canonical routing and skill guidance settle. |
| `docs/onboarding.md` | Repo-facing mirrors | `yes` | `pending` | Contributor onboarding mirror; sync after canonical routing and skill guidance settle. |
| `docs/design-and-architecture.md` | Runtime reference docs | `evaluate only` | `pending` | Explicit changed-or-unchanged decision required before closure because this file is a correctness anchor. |
| `docs/developer-guide.md` | Runtime reference docs | `evaluate only` | `pending` | Explicit changed-or-unchanged decision required before closure because this file is a correctness anchor. |
| `_bmad/COMMANDS.md` | Read-only references | `no` | `read-only reference` | Consult only for portability and routing comparisons; not an edit target in this issue. |
| `_bmad/config.yaml` | Read-only references | `no` | `read-only reference` | Consult only for portability and routing comparisons; not an edit target in this issue. |

# Stage 4 — Critique verdicts (iteration 1)

## Final 18-issue plan (from 29 candidates: 5 killed, 8 absorbed)

| # | final title | absorbs | scope |
|---|---|---|---|
| 1 | [ci-gap][api-contract] Make openapi-diff actually fail on breaking changes; replace spec auto-commit with fail-on-drift | G01+N3 | Fix arg order, add --fail-on-incompatible, fix dead artifact; regenerate-and-diff instead of bot-committing to PR head (kills auto-commit race with lint workflow) |
| 2 | [ci-gap][coverage] Enforce the existing 100% coverage gate in CI | G02 | tests.yml calls gated target (or parses clover) + codecov.yml informational:false |
| 3 | [ci-gap][ci-health] Run full CI for Dependabot and fork PRs | G03+N2 | Delete 14 actor step-guards; guard only secret-dependent steps (codecov upload, GPG) mirroring memory-tests.yml:210 fork guard; budget memory-tests root-cause fix (fails 6/6 dep PRs) |
| 4 | [ci-gap][ci-health] Branch ruleset: required checks, require-up-to-date, post-merge main verification | G04+N1 | Ruleset on main + push→main test run (today only autorelease has push trigger) + scheduled ruleset-presence audit. Sequence after #17 renames and #3 |
| 5 | [ci-gap][security] Secrets scanning: gitleaks + GitHub push protection | G05 | Strictly after #220 remediation (committed .env/Mercure JWT = day-one red) |
| 6 | [ci-gap][security] Semgrep SAST with custom ID-randomness and dynamic-instantiation rules | G06 | Custom rules blocking diff-aware on PR; generic rulesets nightly non-blocking |
| 7 | [ci-gap][static-analysis] Stage Psalm errorLevel 8→4 with baseline | G07 | Cross-ref #264; baseline debt, stays green throughout |
| 8 | [ci-gap][security] Dockerfile/container scanning: hadolint + trivy config blocking, nightly CVE alert | G08+G12-docker | Digest-pin base images; after #220 |
| 9 | [ci-gap][security] Prod-mode guard: boot prod stack, assert headers/introspection/debug/container wiring; nightly ZAP+graphql-cop | G09+G10 | One job sharing prod-stack boot; covers #218/#219/#223 class |
| 10 | [ci-gap][supply-chain] Workflow lint & hardening gate: actionlint + zizmor, pin remaining actions, permissions blocks, remove auto-commit/GPG-env smells | G13+G14+G12+G15+G26-security | Path-filtered .github/** with skip-fallback so it can be required |
| 11 | [ci-gap][deps] dependency-review-action on PR + nightly advisory re-scan (composer + tests/Load pnpm) | G11 (weakened) | PR composer audit dropped as redundant with symfony security:check post-#3; triage 6 current GHSAs before first nightly |
| 12 | [ci-gap][test-effectiveness] Property-based invariant tests (Eris) with pinned seed on PR | G17 | VO + cache-key/repo invariants; seed pinned to protect Infection MSI 100 determinism; nightly randomized high-iteration; cross-ref #261 |
| 13 | [ci-gap][api-contract] Schemathesis: re-enable negative_data_rejection + positive_data_acceptance; nightly deep run | G18 | max-examples stays 5 on PR; 200+stateful nightly; API strictness fix in-scope |
| 14 | [ci-gap][performance] k6 policy: retry transparency + trend artifacts on PR; nightly average/stress/spike profiles | G19(weakened)+G20 | Keep 1 retry; attempt/trend artifacts; alert on chronic retries; schedule existing make targets nightly |
| 15 | [ci-gap][release] Fix autorelease (version-vs-tag guard) + PR-title conventional lint | G22+G27 | 30/30 failures; assert composer.json version > latest tag; title-only lint |
| 16 | [ci-gap][ci-health] main/scheduled failure alerting + weekly flake report | G23 | workflow_run responder; PREREQUISITE for all nightly-alert jobs |
| 17 | [ci-gap][ci-health] CI hygiene: job timeouts + workflow naming/rename fixes | G25+G24 | 8 missing timeouts; dedupe "code quality" names; graphql-diff label; tempate→template typo. Land before #4 |
| 18 | [ci-gap][resilience] Messenger DLQ poison-message integration test | G28 | Failing message reaches failure transport within N retries (#94 regression guard) |

Killed: G16 SBOM (no releases/consumer; #272/#327 track), G21 external links (noise, no evidence), G26 blocking mega-linter (no evidence; security smells → #10), G29 Scorecard-in-CI (redundant with #10).

## Pass-2 new gaps (all folded)
- N1 post-merge main verification → #4 (only autorelease.yml has push trigger; nothing re-tests merged code; rulesets empty)
- N2 fork-PR codecov failure asymmetry → #3 (tests.yml codecov fail_ci_if_error:true no fork guard; memory-tests.yml:210 has one)
- N3 spec auto-commit race → #1 (openapi-diff + lint workflow both bot-push to PR head; invalidates check runs once checks required)
Rejected: mongo migrations (none exist), tests/Load npm supply chain (devDeps lint-only, → #11 nightly), merge_group (no merge queue), PR→non-main triggers (main boundary is where it matters).

## Set-level notes
- PR wall-clock: net ~0 (new jobs parallel, +12-18 runner-min; critical path stays schemathesis/memory/bats/infection)
- Sequencing: #17 → #4; #3 before/with #4; #220 fix before #5 and #8 block; #16 before all nightly jobs; #12 must pin Eris seed
- Path-filtered jobs need skip-fallbacks or exclusion from required list
- Verified in-repo during critique: Dockerfile:81 COPY bakes .env (dockerignore excludes only compose files); docker-compose.prod.yml exists; tests/Load has package.json+pnpm-lock (eslint only); 8 jobs missing timeouts; only autorelease.yml triggers on push→main

# Iteration 2 results — 4 NEW confirmed gaps (issues 19-22)

| # | title | scope |
|---|---|---|
| 19 | [ci-gap][ci-health] Wire the locked quality-config tamper gate (validate-configuration) into CI | scripts/validate-configuration.sh fails if LOCKED files (phpinsights.php, psalm.xml, deptrac.yaml, infection.json5, phpmd*.xml, .php-cs-fixer.dist.php — lines 50-59) modified vs origin/main; only in make ci (Makefile:589-596), zero .github/ hits. PR job with fetch-depth:0. Blocking day one, zero flake. NOTE: issue #7 (psalm level) must update locked list in same PR. Effort S |
| 20 | [ci-gap][test-effectiveness] Unit-test the k6 gate-defining JS (tests/Load/utils) | ~1291 lines untested; ThresholdsBuilder has silent-disable branch (K6_SKIP_DURATION_THRESHOLDS → always-true max>=0); empty-thresholds = vacuous pass (k6 doesn't error on absent thresholds); package.json test script is a no-op stub. node:test specs gating load-test shards, path-filtered with skip-fallback. Effort M |
| 21 | [ci-gap][docs] Validate documented make commands against the Makefile in docs-check | LIVE defect: AGENTS.md:124 documents `make e2e-tests` — target doesn't exist (only `behat`); CLAUDE.md calls AGENTS.md single source of truth. Extend scripts/check-docs.sh: extract `make X` tokens, check vs target list, allowlist placeholders. Blocking via existing docs-check. Effort S |
| 22 | [ci-gap][docs] Validate workspace.dsl (Structurizr C4) in CI | workspace.dsl at root, structurizr/lite in dev stack, skill mandates sync — zero CI validation. `docker run structurizr/cli validate -workspace workspace.dsl`, path-filtered, skip-fallback. Effort XS |

Iteration-2 rejected (do not relitigate): Mongo index consistency (schema:create runs pre-test in CI), fixture || true (local-only path), Negative suite exclusion (all suites run), Infection Integration blind spot (min-msi=100 covers uncovered mutants), prod Dockerfile build (covered by #9), shellcheck (G26 family, below bar), .env drift (by design), translations (Behat covers), composer platform drift (benign), composer validate --strict (below bar), dependabot config (natively validated), infrastructure/ (single script), GraphQL committed-spec drift (generated fresh both sides).

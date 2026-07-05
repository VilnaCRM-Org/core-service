# Stage 2 — Gap analysis (candidate list, pre-critique)

Benchmarks used: api-platform/core (PHPStan max + CodeQL + required checks), symfony/symfony (psalm+phpstan, fabbot, security monitoring), sylius (Behat+Psalm+PHPStan+ECS, required checks), OpenSSF best practices. Network research unavailable in this pass; benchmarks from model knowledge.

Legend: cat = taxonomy category. Scores = category (coverage/enforcement) now → target. Evidence refs = stage3 escaped defects. Overlap = existing open issues to cross-reference (overlap ≠ duplicate; NFR issues are broad narratives, these are single actionable checks).

## G01 fix-openapi-diff-enforcement (cat 6, 4/3 → 5/5) — flagship "exists but never fails"
- Tool: existing openapitools/openapi-diff docker step — add `--fail-on-incompatible`, swap inverted base/head args, fix dead artifact upload (`./output` never written).
- Trigger: PR (existing workflow).
- Defect class: breaking REST API change merged silently. Evidence: category proven by #62 (spec break shipped); the check was added later but configured to never fail (openapi-diff.yml:91-96).
- Config sketch: `docker run --rm -v $PWD:/specs openapitools/openapi-diff:2.1.0@sha256:... /specs/base.json /specs/head.json --fail-on-incompatible --markdown /specs/diff.md` + upload diff.md as artifact + PR comment.
- Effort: S. Blocking: job already required-candidate; prove by seeding a removed required response field in a test PR.
- Duplicate check: graphql-diff blocks (fail-on-breaking default); Spectral lints. Nothing enforces REST breaking-change detection. No open issue covers this specific fix.

## G02 wire-coverage-gate-into-ci (cat 2, 5/4 → 5/5)
- Tool: existing `make unit-tests` 100% line-coverage gate (Makefile:213-247) — invoke it in tests.yml instead of bare `make coverage-xml`; add `codecov.yml` with `coverage.status.project.default.target: 100%` and `informational: false` for defense in depth.
- Trigger: PR → main (existing tests.yml).
- Defect class: untested code merged while AGENTS.md:105 claims 100% coverage; silent coverage erosion. Evidence: enforcement hole found in stage1; Infection MSI 100 partially compensates on Unit suite only.
- Effort: S. Blocking: coverage <100% exits 1; prove by adding an uncovered branch in a test PR.
- Duplicate check: no workflow enforces coverage today; no codecov.yml exists. Not covered by any open issue.

## G03 remove-dependabot-ci-bypass (cat 10 + 4, enforcement hole → 5/5)
- Tool: delete the `if: !contains(toLower(github.actor),'dependabot')`-style step guards from the ~14 workflows (added in #215/c3d0aac); where secrets are the blocker, split into secretless jobs or use `pull_request` (not `pull_request_target`) with default token.
- Trigger: PR (all existing workflows).
- Defect class: unverified dependency updates — behavioral breaks, supply-chain regressions. Evidence: ~15 dep PRs (#198, #206–#216) merged with all verification steps skipped; memory-tests fails 6/6 Dependabot PRs yet they merge.
- Effort: M. Blocking: jobs actually run for dependabot actor; prove via a Dependabot-authored test PR (or actor-spoofed dry run) showing tests execute.
- Duplicate check: overlaps NFR #303/#313/#290/#260 narratives — cross-reference all; none is a single actionable "remove the skip guards" issue.

## G04 branch-rulesets-required-checks (cat 10, 3/2 → 5/5)
- Tool: GitHub ruleset on main: require the gate jobs (PHPUnit, behat, infection, deptrac, psalm, phpinsights, schemathesis, load-tests gate, memory gates, bats gate, docs-check, graphql-diff, openapi-diff-after-G01) as required status checks + require branch up to date + block force pushes.
- Trigger: repo config, verified by a scheduled `actions/github-script` audit job asserting ruleset presence (config-as-code via `gh api` in CI or Probot settings).
- Defect class: red main / merges with failing checks. Evidence: REST /rules/branches/main = []; memory-tests failing yet merging; #59 Schemathesis red on main for months.
- Effort: S (admin config) + S (audit job). Blocking: merge blocked when any required check fails; prove by opening a PR with a seeded failing test.
- Duplicate check: #328/#290 mention "gates not CI-enforced" broadly; cross-ref.

## G05 secrets-scanning (cat 4, 3/3 → 5/5 part 1)
- Tool: gitleaks/gitleaks-action@v2 (SHA-pinned) full-history on PR + nightly; enable GitHub push protection + secret scanning in repo settings.
- Trigger: PR + nightly.
- Defect class: committed credentials. Evidence: #220 (OPEN): committed .env baked into image, hardcoded Mercure JWT fallback.
- Effort: S. Blocking: gitleaks exit 1; prove by seeding a fake AWS key in a test PR.
- Duplicate check: no secrets scanning exists (stage1 NOT FOUND list). Cross-ref #303, #301, #258, #220.

## G06 semgrep-sast-rules (cat 4) 
- Tool: semgrep ci with p/php, p/security-audit + 2 custom rules: ban `uniqid()|mt_rand()|rand()` for identifiers; ban `new $var(...)` from request-derived strings.
- Trigger: PR (changed files via semgrep ci diff-aware) + nightly full.
- Defect class: weak randomness, unsafe reflection/injection. Evidence: #221 (uniqid event IDs, 7 classes), #222 (UlidFilterProcessor `new $class()` from query string) — both sailed through green CI incl. Psalm taint at level 8.
- Effort: S-M. Blocking: `semgrep ci` exits non-zero on findings; prove by seeding `uniqid()` usage.
- Duplicate check: Psalm taint exists but missed both defects (level 8, no such rules). Complementary, not duplicate. Cross-ref #303, #264.

## G07 tighten-psalm-level (cat 1, 5/4 → 5/5 part)
- Tool: psalm.xml errorLevel 8 → 4 (staged: 6 then 4), with baseline file for existing debt; keep taint analysis.
- Trigger: PR (existing psalm.yml).
- Defect class: type-coercion/nullability bugs. Evidence: #27 (string "false" vs bool filter) is the class psalm-strict catches.
- Effort: M-L (baseline manages debt). Blocking: already blocking; prove by seeding a type error caught at level 4 but not 8.
- Duplicate check: direct overlap with #264 (Analyzability: Psalm at level 8) and #261 — cross-ref; #264 identifies the problem but proposes no staged enforcement plan. Decide in critique whether to file or comment on #264.

## G08 container-image-scanning (cat 4/5)
- Tool: trivy (aquasecurity/trivy-action, SHA-pinned): `image` scan of built prod image + `config` scan (Dockerfile misconfig) + hadolint for Dockerfiles.
- Trigger: PR (config+fs, fast) + nightly (full image scan).
- Defect class: vulnerable base images, .env/secrets baked into layers, Dockerfile misconfig. Evidence: #220 (.env in image); Scorecard: unpinned base images Dockerfile:3,5,45,70,111.
- Effort: M. Blocking: `exit-code: 1`, severity CRITICAL,HIGH; prove by seeding `COPY .env` (already present!) — the check fails on day one, demonstrating real value; needs remediation of #220 first or a scoped ignore.
- Duplicate check: none exists. Cross-ref #303, #220, #272, #311.

## G09 dast-prod-mode (cat 4)
- Tool: OWASP ZAP baseline (zaproxy/action-baseline) + graphql-cop against `docker-compose.prod.yml` stack with APP_ENV=prod; PR smoke = curl assertions (security headers present, introspection/GraphiQL disabled, debug off); nightly = full ZAP baseline + graphql-cop.
- Trigger: PR smoke (fast curl/Behat checks) + nightly full DAST.
- Defect class: prod-only insecure config. Evidence: #218 (introspection+GraphiQL in prod), #219 (no security headers), #223 (destructive listener in prod) — 3 defects, all found only by out-of-band pentest.
- Effort: M-L. Blocking: ZAP fail-on WARN/FAIL rules subset; smoke asserts exit 1. Prove by re-enabling introspection in a test PR.
- Duplicate check: Schemathesis targets test env with test config — does not cover prod-mode config. Cross-ref #298, #303.

## G10 prod-container-lint (cat 7/4 — bespoke)
- Tool: custom CI step: build container with APP_ENV=prod, run `bin/console debug:container --env=prod` and assert no services from tests/Support/test-only namespaces registered; assert `%kernel.debug%` false.
- Trigger: PR (fast, container already built for other jobs).
- Defect class: env-gating config errors. Evidence: #223 (SchemathesisCleanupListener deleting customers registered in ALL envs incl. prod).
- Effort: S-M. Blocking: script exit 1; prove by re-seeding a test-namespace service without `when@test` guard.
- Duplicate check: Deptrac checks layer deps, not container env wiring. Nothing covers this. No open-issue overlap beyond #223's fix.

## G11 dependency-audit-in-ci (cat 4/5)
- Tool: `composer audit --locked` as blocking CI step (exists only in local `make ci`); optionally actions/dependency-review-action on PRs (needs GH Advanced Security? — no, free for public repos).
- Trigger: PR + nightly (nightly catches newly published advisories on unchanged code).
- Defect class: known-vulnerable dependencies. Evidence: Scorecard Vulnerabilities 4/10 — 6 current GHSAs; `symfony security:check` exists but is skipped for Dependabot PRs (the PRs that change deps!).
- Effort: S. Blocking: composer audit exit 1; prove: it fails today with 6 GHSAs.
- Duplicate check: symfony security:check covers PHP advisories on PR but (a) skipped for dependabot, (b) no npm audit for tests/Load, (c) no nightly re-scan. Cross-ref #303.

## G12 sha-pin-remaining-actions (cat 5, 2/2 → part)
- Tool: pin the 9 tag-pinned uses to SHAs (bats-tests.yml:86,90,102; documentation.yml:18; tempate-sync-pat.yml:16,21; template-sync-app.yml:14,20,25); pin Docker base images by digest; add zizmor or `scorecard --checks Pinned-Dependencies` as CI guard against regressions.
- Trigger: PR on .github/** + Dockerfile changes.
- Defect class: action/image tag hijack (supply chain). Evidence: Scorecard Pinned-Dependencies 5/10; industry: tj-actions/changed-files compromise class.
- Effort: S. Blocking: zizmor/scorecard threshold; prove by seeding a tag-pinned action in a test PR.
- Duplicate check: 40/49 already pinned — this closes the tail. Cross-ref #272, #304.

## G13 workflow-security-audit-zizmor (cat 5/10)
- Tool: zizmor (via `pip install zizmor` or docker) blocking on PR for .github/workflows/**; catches persist-credentials, secret-in-env, unpinned, template injection.
- Trigger: PR changed-files (.github/**).
- Defect class: workflow security misconfig. Evidence: GPG_PASSPHRASE written to GITHUB_ENV (code-lint-and-prettify.yml:52), persist-credentials:true checkouts with PAT (tempate-sync-pat.yml:18), 12/20 workflows missing permissions block (Scorecard Token-Permissions 0/10).
- Effort: S. Blocking: zizmor --min-severity medium exit 1; prove: fails today on the above findings.
- Duplicate check: none exists. Complements actionlint (G14) — zizmor=security, actionlint=correctness.

## G14 actionlint-workflow-lint (cat 10)
- Tool: rhysd/actionlint (SHA-pinned action or docker) blocking.
- Trigger: PR changed-files (.github/workflows/**).
- Defect class: workflow syntax/expression errors. Evidence: #215/c3d0aac shipped invalid `toLower()` → ALL push workflows on main failed at parse for a day (#217).
- Effort: S. Blocking: exit 1; prove by seeding `${{ toLower('X') }}`.
- Duplicate check: none; Super-Linter could run actionlint but is continue-on-error. Cross-ref #328.

## G15 workflow-permissions-hardening (cat 5/4)
- Tool: add top-level `permissions: contents: read` to all 20 workflows; job-level escalation only where needed (autorelease, lint auto-commit, SARIF upload).
- Trigger: n/a (config change), guarded by zizmor/scorecard (G13/G12).
- Defect class: token abuse blast radius on compromised action. Evidence: Scorecard Token-Permissions 0/10; 12/20 missing permissions.
- Effort: S. Duplicate: none. Cross-ref #303/#304. NOTE: may merge into G13 at critique.

## G16 sbom-and-provenance (cat 5, 2/2 → part)
- Tool: cyclonedx-php-composer (composer plugin) + CycloneDX for npm(tests/Load) generating SBOM on release; actions/attest-build-provenance for release artifacts; attach to GH release via autorelease.yml (after G22 fixes it).
- Trigger: push main / release.
- Defect class: unauditable supply chain, no ingredient list for CVE response. Evidence: Scorecard/inventory: no SBOM/signing/provenance. 
- Effort: M. Blocking: release job fails if SBOM generation fails; prove by breaking composer.lock in dry run.
- Duplicate check: none exists. Direct overlap with #272 (Reproducibility) and #327/#304 — cross-ref.

## G17 property-based-tests (cat 3, 4/5 → 5/5 part)
- Tool: giorgiosironi/eris (PHPUnit integration) — property suites for Domain value objects + cache/repo consistency invariants (cacheKey equality ⇒ repo lookup equality; filter predicate parity vs in-memory model).
- Trigger: PR (part of Unit suite, so also mutated by Infection) + nightly with higher iteration count (e.g. ERIS_REPEAT=1000).
- Defect class: invariant violations across input space. Evidence: #231 (email case cache poisoning), #27 (bool filter coercion).
- Effort: M. Blocking: part of blocking Unit suite; prove by reverting b5e0ec5 locally and watching the property fail.
- Duplicate check: zero property-based tests exist. Direct overlap with #261 (Provability) — cross-ref.

## G18 schemathesis-depth (cat 3/6)
- Tool: existing schemathesis: raise --max-examples 5→25 on PR; re-enable `negative_data_rejection` and `positive_data_acceptance` checks; add nightly full run with --max-examples 200 + stateful phase always on.
- Trigger: PR (bounded) + nightly (deep) — matches fuzzing charter constraint.
- Defect class: contract violations, lax validation. Evidence: #28 (extra fields accepted), #29 (empty PATCH 200) — both are exactly what negative_data_rejection catches; excluded today (Makefile:54).
- Effort: S-M (may require fixing the API first — that's the point). Blocking: warnings already promoted to failures; prove by re-seeding an endpoint accepting unknown fields.
- Duplicate check: schemathesis exists — this is a depth/enforcement fix, not a new tool. Cross-ref #249 (inconsistent strictness), #142.

## G19 k6-retry-masking (cat 8, 4/4 → 5/5 part)
- Tool: existing load-tests.yml: drop K6_SMOKE_RETRIES 2→0 (or 1 with mandatory retry-report artifact + fail if attempt>1 twice in a row); publish trend data (k6 summary JSON) as artifact for regression tracking.
- Trigger: PR → main (existing).
- Defect class: intermittent perf regressions masked by retries; flaky-threshold drift. Evidence: #173/#174 perf shortfall shipped; retry policy currently allows 3 attempts per scenario.
- Effort: S. Blocking: thresholds already binding — this removes the escape hatch; prove by lowering a p(99) budget in test branch.
- Duplicate check: cross-ref #271 (flaky-test defense), #289.

## G20 nightly-perf-profiles (cat 8)
- Tool: new nightly workflow running `make average-load-tests`/`stress`/`spike` (targets exist, never scheduled) with binding thresholds; compare against stored baseline (k6-summary artifact diff).
- Trigger: nightly schedule.
- Defect class: capacity/stress regressions invisible to smoke profile. Evidence: only smoke runs in CI (stage1 #18); #173 class.
- Effort: M. Blocking: k6 thresholds; alert on failure (see G23). Prove by seeding an artificial sleep in a handler on a test branch nightly run.
- Duplicate check: make targets exist but unscheduled — enforcement gap. Cross-ref #329, #289.

## G21 external-link-check (cat 9, 4/4 → 5/5 part)
- Tool: lycheeverse/lychee-action (SHA-pinned) over docs/ + README + *.md; .lycheeignore for flaky hosts.
- Trigger: nightly (external links are nondeterministic — do NOT block PRs) + PR for changed .md files with `--offline` local-anchor mode (blocking).
- Defect class: dead links / rotted docs. Evidence: check-docs.sh validates local links only (stage1 #24).
- Effort: S. Blocking: nightly failure files an issue via peter-evans/create-issue-from-file rather than blocking merges. 
- Duplicate check: none for external links. Cross-ref #253/#259.

## G22 fix-autorelease-pipeline (cat 9/10)
- Tool: repair autorelease.yml (version-vs-tag consistency: assert composer.json version > latest v* tag before tagging; or drop composer.json version syncing entirely and derive from tags); add job asserting release succeeded.
- Trigger: push main (existing) + the assert as PR check on release-relevant files.
- Defect class: release pipeline rot — no releases produced. Evidence: 30/30 recent runs failed (2026-05-09→06-14); 67 runs total; #217 fix didn't cure it.
- Effort: M. Blocking: currently it IS failing — the gap is nobody notices (see G23) and the failure mode is chronic. Prove: green run producing a release on next main push.
- Duplicate check: direct overlap with #290/#328/#304/#243 narratives — cross-ref; none is a targeted "fix autorelease + add version-consistency guard" issue.

## G23 ci-failure-alerting-and-flake-tracking (cat 10)
- Tool: workflow-run alerting: a `workflow_run` (conclusion: failure, branch: main) responder that opens/updates a pinned issue (or Slack webhook) for main/scheduled failures; optionally a weekly flake-rate report job.
- Trigger: workflow_run on main + schedule weekly.
- Defect class: silent CI rot. Evidence: autorelease failed 30×/5+ weeks unnoticed; template-sync failing (2026-07-01); #59 Schemathesis red on main for ~6 months.
- Effort: S-M. Blocking: n/a (alerting); acceptance = alert fired for a seeded failing nightly.
- Duplicate check: nothing exists. Cross-ref #290, #271, #328.

## G24 job-timeouts (cat 10)
- Tool: `timeout-minutes` on the 6 jobs lacking it (E2Etests, psalm, infection, phpinsights, deptrac, tests) — default is 360 min.
- Trigger: n/a (config).
- Defect class: hung jobs burning runners/blocking merges for hours. Evidence: stage1 CI-health note.
- Effort: S. Blocking: n/a. NOTE: candidate for merging into a single CI-hygiene issue with G25 at critique.
- Duplicate check: none.

## G25 workflow-naming-hygiene (cat 10)
- Tool: rename: psalm.yml + phpinsights.yml both `name: code quality` → distinct; graphql-diff job mislabeled "Openapi-diff" (graphql-diff.yml:18); filename typo tempate-sync-pat.yml → template-sync-pat.yml.
- Defect class: required-check misconfiguration risk (can't require a check you can't uniquely name). Evidence: stage1 naming note; blocks G04 correctness.
- Effort: S. NOTE: merge candidate with G24.

## G26 superlinter-blocking (cat 1)
- Tool: code-lint-and-prettify.yml: remove `continue-on-error: true` (line 91); stop auto-committing fixes (or keep autofix but fail if fixes were needed); stop exporting GPG_PASSPHRASE to GITHUB_ENV.
- Trigger: PR (existing).
- Defect class: JS/MD/YAML/env lint errors merged; also what-you-test ≠ what-you-merge when autofix mutates the checkout (same class as PHPInsights --fix, include note).
- Effort: S. Blocking: remove the flag; prove by seeding a YAML syntax error.
- Duplicate check: the check exists non-blocking — enforcement fix. Cross-ref: none direct.

## G27 commit-message-lint (cat 9)
- Tool: `ramsey/conventional-commits` (already in require-dev!) or commitlint via wagoid/commitlint-github-action on PR title+commits; needed because autorelease derives versions from conventional commits.
- Trigger: PR.
- Defect class: broken changelog/version derivation. Evidence: autorelease chronic failure partially rooted in commit/version discipline.
- Effort: S. Blocking: exit 1 on non-conforming; prove with a "wip lol" commit PR.
- Duplicate check: tool in require-dev but wired to nothing (no hooks exist). Cross-ref #328.

## G28 dlq-poison-message-test (cat 2/3)
- Tool: PHPUnit integration test: dispatch permanently-failing message through messenger transport, assert it lands in failure transport (DLQ) within N retries; requires fixing retry strategy config first (done in #193).
- Trigger: PR (Integration suite).
- Defect class: resilience regressions (infinite retry, message loss). Evidence: #94 (InfiniteRetryStrategy always true → poison messages retried forever).
- Effort: M. Blocking: part of Integration suite. Prove by reverting 12f6d60 locally.
- Duplicate check: no such test exists (fix #193 shipped without regression test per stage3). Cross-ref #274, #284, #316.

## G29 scorecard-in-ci (cat 5/10)
- Tool: ossf/scorecard-action (SHA-pinned) weekly + on push main, publishing to code-scanning + badge; acts as regression guard for pinning/permissions/etc.
- Trigger: schedule weekly + push main.
- Defect class: supply-chain hygiene regressions. Evidence: baseline aggregate 6.0/10 (local mode).
- Effort: S. Blocking: not directly; the code-scanning alerts + G04 keep it visible. 
- Duplicate check: none. Cross-ref #304.

## Deliberately NOT proposed (redundant with existing strong gates)
- New mutation tool / MSI threshold — Infection MSI 100 full-run already blocks every PR (stronger than the charter's changed-files-only ask). Optional runtime optimization (changed-files on PR + nightly full) NOT filed as a gap — it's a trade-down.
- New spec linter — Spectral fail-on-hint blocks.
- New architecture tool — Deptrac fail-on-uncovered + custom guards + PHPInsights architecture 100.
- CodeQL for PHP — CodeQL does not support PHP; Semgrep (G06) + Psalm taint is the right stack. (Scorecard SAST 0 is partially a detection artifact.)
- composer validate --strict, npm audit for tests/Load — folded into G11.

## Summary table
| gap-id | cat | tool | trigger | effort | evidence | overlap |
|---|---|---|---|---|---|---|
| G01 fix-openapi-diff-enforcement | 6 | openapi-diff --fail-on-incompatible + arg fix | PR | S | openapi-diff.yml:91-96; #62 class | — |
| G02 wire-coverage-gate-into-ci | 2 | make unit-tests + codecov.yml | PR | S | Makefile:213 vs tests.yml:32 | — |
| G03 remove-dependabot-ci-bypass | 10/4 | delete actor guards / secretless jobs | PR | M | #198,#206-#216; memory 6/6 | #303 #313 #290 #260 |
| G04 branch-rulesets-required-checks | 10 | GH ruleset + audit job | config | S | rulesets empty; #59 | #328 #290 |
| G05 secrets-scanning | 4 | gitleaks + push protection | PR+nightly | S | #220 | #303 #301 #258 |
| G06 semgrep-sast-rules | 4 | semgrep ci p/php + custom | PR diff + nightly | S-M | #221 #222 | #303 #264 |
| G07 tighten-psalm-level | 1 | psalm errorLevel 8→4 + baseline | PR | M-L | #27 class | #264 #261 |
| G08 container-image-scanning | 4/5 | trivy + hadolint | PR+nightly | M | #220; unpinned bases | #303 #220 #272 |
| G09 dast-prod-mode | 4 | ZAP baseline + graphql-cop + header smoke | PR smoke + nightly | M-L | #218 #219 #223 | #298 #303 |
| G10 prod-container-lint | 7/4 | debug:container prod assert | PR | S-M | #223 | — |
| G11 dependency-audit-in-ci | 4/5 | composer audit --locked + dep-review | PR+nightly | S | 6 GHSAs; dependabot skip | #303 |
| G12 sha-pin-remaining-actions | 5 | pin 9 actions + image digests | PR | S | Scorecard Pinned-Deps 5/10 | #272 #304 |
| G13 workflow-security-audit-zizmor | 5/10 | zizmor | PR (.github diff) | S | GPG env, persist-creds, Token-Perms 0/10 | #303 |
| G14 actionlint-workflow-lint | 10 | actionlint | PR (.github diff) | S | #215→#217 outage | #328 |
| G15 workflow-permissions-hardening | 5/4 | permissions: contents: read | config | S | Token-Permissions 0/10 | #303 #304 |
| G16 sbom-and-provenance | 5 | cyclonedx + attest-build-provenance | release | M | no SBOM | #272 #327 #304 |
| G17 property-based-tests | 3 | Eris property suites | PR + nightly deep | M | #231 #27 | #261 |
| G18 schemathesis-depth | 3/6 | re-enable checks, examples 5→25/200 | PR + nightly | S-M | #28 #29 | #249 |
| G19 k6-retry-masking | 8 | K6_SMOKE_RETRIES 2→0 | PR | S | #173 class | #271 #289 |
| G20 nightly-perf-profiles | 8 | scheduled stress/spike/average k6 | nightly | M | smoke-only today | #329 #289 |
| G21 external-link-check | 9 | lychee | nightly + PR offline | S | local-links-only today | #253 #259 |
| G22 fix-autorelease-pipeline | 9/10 | version-vs-tag guard + repair | push main | M | 30/30 failures | #290 #328 #304 |
| G23 ci-failure-alerting | 10 | workflow_run responder + flake report | main+weekly | S-M | 5wk silent failure; #59 | #290 #271 |
| G24 job-timeouts | 10 | timeout-minutes ×6 | config | S | 360min default | — |
| G25 workflow-naming-hygiene | 10 | rename dup names/typo | config | S | required-check risk | — |
| G26 superlinter-blocking | 1 | drop continue-on-error | PR | S | line 91 | — |
| G27 commit-message-lint | 9 | commitlint/ramsey | PR | S | autorelease rot | #328 |
| G28 dlq-poison-message-test | 2/3 | messenger DLQ integration test | PR | M | #94 | #274 #284 |
| G29 scorecard-in-ci | 5/10 | ossf/scorecard-action | weekly | S | baseline 6.0 | #304 |

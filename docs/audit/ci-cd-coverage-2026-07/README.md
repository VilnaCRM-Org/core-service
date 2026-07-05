# CI/CD Verification-Coverage Audit — July 2026

Audit of every automated verification gate in this repository, scored against a
10-category taxonomy for coverage and enforcement strength, with gaps confirmed
through git-history evidence and a 3-iteration adversarial critique loop.

## Method

1. **Baseline** — OpenSSF Scorecard (local mode; remote GraphQL blocked in the audit
   environment): aggregate **6.0/10**. SAST 0, Fuzzing 0, Token-Permissions 0,
   Pinned-Dependencies 5, Vulnerabilities 4 (6 known GHSAs). Details in
   `scorecard-baseline-details.txt`.
2. **Stage 1 — Inventory** (`stage1-inventory.md`): all 29 checks across 20 workflows,
   Makefile, and tool configs, with per-check blocking status and per-category scores.
3. **Stage 3 — Evidence mining** (`stage3-evidence.md`): 19 escaped defects mapped to
   the missing check that would have caught each; CI-health run history; all 93 open
   issues captured for dedup.
4. **Stage 2 — Gap analysis** (`stage2-gaps.md`): 29 candidate gaps with tools,
   triggers, config sketches, and effort.
5. **Stage 4 — Critique loop** (`stage4-verdicts.md`): three adversarial iterations
   (kill/merge/weaken on false-positive cost, runtime cost, redundancy; then fresh gap
   hunts). 29 candidates → 5 killed, 8 merged, 7 new gaps found across iterations →
   **24 confirmed gaps, filed as issues #356–#378** (one gap rides as a scope item in
   #368).

## Headline findings

- Six security defects (#218–#224, #231) shipped through a fully green pipeline and
  were found only by an out-of-band pentest sweep — the gaps are in check coverage,
  not check execution.
- `openapi-diff` can never fail (no `--fail-on-incompatible`, inverted args) → #356.
- The advertised 100% coverage gate exists only in `make unit-tests`, which CI never
  calls → #357.
- Dependabot PRs skip ~14 workflows via actor guards; ~15 dependency PRs merged with
  all verification steps skipped; `memory-tests` failed 6/6 recent Dependabot PRs and
  they merged anyway → #358, #359.
- No branch rulesets exist on `main`; nothing re-tests merged code → #359.
- `autorelease` failed 30/30 recent main pushes over 5+ weeks, unnoticed → #370, #371.
- The repo's own quality-config tamper gate (`scripts/validate-configuration.sh`) is
  never run in CI → #374.

## Filed issues

#356 openapi-diff enforcement · #357 coverage gate · #358 Dependabot/fork CI ·
#359 branch ruleset + post-merge verify · #360 gitleaks · #361 Semgrep SAST ·
#362 Psalm level · #363 hadolint/trivy · #364 prod-mode guard + DAST ·
#365 actionlint/zizmor + pinning · #366 dependency review · #367 property-based
tests · #368 Schemathesis depth + GraphQL fuzz · #369 k6 retry/nightly profiles ·
#370 autorelease fix · #371 CI failure alerting · #372 timeouts/naming ·
#373 DLQ poison-message test · #374 config tamper gate · #375 k6 JS tests ·
#376 docs-vs-Makefile drift · #377 workspace.dsl validation · #378 PHPUnit strict mode

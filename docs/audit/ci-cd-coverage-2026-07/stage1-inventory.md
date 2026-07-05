# Stage 1 — CI check inventory (VilnaCRM-Org/core-service)

## Scorecard baseline (local mode, aggregate 6.0/10)
- Binary-Artifacts: 10 | Dangerous-Workflow: 10 | Dependency-Update-Tool: 10 | License: 9 | Security-Policy: 10
- Fuzzing: 0 (no OSS-Fuzz-style fuzzing detected)
- SAST: 0 (scorecard heuristic did not detect; repo does run Psalm taint via psalm.yml)
- Token-Permissions: 0 — 12/20 workflows have no top-level permissions block
- Pinned-Dependencies: 5 — 9 tag-pinned actions (bats-tests.yml:86,90,102; documentation.yml:18; tempate-sync-pat.yml:16,21; template-sync-app.yml:14,20,25), Docker base images unpinned (Dockerfile:3,5,45,70,111; tests/Load/Dockerfile:6), curl|run in schemathesis.yml:17, npm installs unpinned in scripts/
- Vulnerabilities: 4 — 6 GHSAs in current deps: GHSA-cwxw-98qj-8qjx, GHSA-wpwq-4j6v-78m3, GHSA-vm85-hxw5-5432, GHSA-f886-m6hf-6m8v + 2 more (see scorecard-details.txt)
- Branch protection: no rulesets on main (REST /rules/branches/main = []); classic protection unreadable (403) — required-check status unknown

## Check inventory

| # | Check | file:job | Trigger | Tool / exact command | Blocking? (evidence) | Scope |
|---|---|---|---|---|---|---|
| 1 | Behat E2E tests | E2Etests.yml:behat | pull_request (all) | make behat → behat --stop-on-failure -n (strict: true) | YES | Full feature suite, PR |
| 2 | Unit+Integration tests w/ coverage | tests.yml:tests ("PHPUnit") | PR → main only | make coverage-xml → phpunit --coverage-clover (all suites) | YES for test failures; NO coverage threshold in CI (100% gate lives only in make unit-tests, Makefile:213-247, never called by CI). Codecov fail_ci_if_error only gates upload; no codecov.yml | Full suite, PR to main |
| 3 | Mutation testing | infection.yml:infection | pull_request (all) | CI=1 make infection → infection --min-msi=100 --min-covered-msi=100 -j4, Unit suite only (Makefile:429-430) | YES | Full src/, PR |
| 4 | Psalm static analysis | psalm.yml:psalm | pull_request (all) | make psalm → forbid-static-methods.php + check-architecture.php + psalm (errorLevel="8" — most lenient) | YES | src+tests, PR |
| 5 | Psalm taint analysis (SAST) | psalm.yml (SARIF upload) | pull_request (all) | make psalm-security-report → psalm --taint-analysis --report=results.sarif; uploaded via codeql-action/upload-sarif | YES | Full codebase, PR |
| 6 | PHPMD | phpinsights.yml (make prereq) | pull_request (all) | phpmd src ansi phpmd.xml + phpmd tests (Makefile:205-207) | YES | src+tests, PR |
| 7 | PHPInsights | phpinsights.yml:phpinsights | pull_request (all) | phpinsights --fix --disable-security-check (src: 100/93/100/100, tests: 95/95/90/95) | YES; but --fix mutates CI checkout; security check disabled | src+tests, PR |
| 8 | Deptrac | deptrac.yml:deptrac | pull_request (all) | make deptrac → deptrac analyse --report-uncovered --fail-on-uncovered (Makefile:249-250) | YES | Full src/, hexagonal layers, Domain zero-dep (deptrac.yaml:69-86) |
| 9 | Custom architecture guards | psalm.yml (via make psalm) | pull_request (all) | scripts/forbid-static-methods.php, scripts/check-architecture.php | YES | Full codebase, PR |
| 10 | Super-Linter + Prettier | code-lint-and-prettify.yml:lint | pull_request (all) | super-linter VALIDATE_*/FIX_* | NO — continue-on-error: true (line 91); auto-commits fixes to PR branch (lines 108-123) | Full codebase, PR |
| 11 | Composer validate | symfony.yml:symfony-checks | pull_request (all) | composer validate (NOT --strict, line 56) | YES | PR |
| 12 | Symfony requirements | symfony.yml | pull_request (all) | symfony check:requirements | YES | PR |
| 13 | Dependency vuln scan | symfony.yml:63 | pull_request (all) | symfony security:check | YES (but skipped for dependabot!) | composer.lock, PR |
| 14 | OpenAPI lint | openapi-validator.yml | pull_request (all) | Spectral --fail-severity=hint (scripts/validate-openapi-spec.sh:31) | YES | Generated spec, PR |
| 15 | OpenAPI breaking-change diff | openapi-diff.yml:openapi-diff | pull_request (all) | openapitools/openapi-diff WITHOUT --fail-on-incompatible (lines 91-96); head/base args inverted; artifact upload dead (./output never written, lines 98-102) | EFFECTIVELY NO — exits 0 on any diff | Spec diff, PR |
| 16 | GraphQL breaking-change diff | graphql-diff.yml (job mislabeled "Openapi-diff", line 18) | pull_request (all) | kamilkisiela/graphql-inspector vs base_ref spec | YES (fail-on-breaking default true) | Schema diff, PR |
| 17 | Schemathesis contract fuzzing | schemathesis.yml | PR → main | schemathesis 4.15.1: --checks all --exclude-checks negative_data_rejection,positive_data_acceptance --max-examples 5, phases examples,coverage,fuzzing[,stateful]; warnings→failures | YES | Live API vs spec, PR to main |
| 18 | K6 smoke load tests | load-tests.yml: 4 shards + gate | PR → main | make smoke-load-tests, binding p(99) thresholds + checks rate>0.99 (thresholdsBuilder.js:10-14); K6_SMOKE_RETRIES=2 (3 attempts) | YES, but retries mask flakes/regressions | All scenarios, PR to main |
| 19 | Cache perf integration | cache-performance-tests.yml | PR → main, path-filtered | phpunit CachePerformanceTest | YES | path-filtered |
| 20 | Cache perf load tests | cache-performance-tests.yml | same | k6 cachePerformance + cacheReadWriteRace | YES | path-filtered |
| 21 | Worker memory soak | memory-tests.yml: 4 shards × 3 envs + gates | PR → main | verify-frankenphp-worker-memory.sh, growth budget 32MiB (Makefile:100); duration thresholds skipped (K6_SKIP_DURATION_THRESHOLDS=1) | YES (memory only) | PR to main |
| 22 | Memory-suite PHPUnit | memory-tests.yml:memory-suite | PR → main | make memory-tests, 100% coverage of tests/Support/Memory enforced (Makefile:296-341) | YES | PR to main |
| 23 | Bats CLI tests | bats-tests.yml: 14 suites + gate | PR, path-filtered | make bats | YES | path-filtered |
| 24 | Docs validation | documentation.yml:docs-check | pull_request (all) | scripts/check-docs.sh (20 required docs, H1, whitespace, LOCAL links only) | YES | docs/+README, PR |
| 25 | Autorelease | autorelease.yml | push → main | conventional-changelog-action + gh-release | release automation | main |
| 26-27 | Template sync ×2 | tempate-sync-pat.yml (monthly), template-sync-app.yml (weekly) | schedule | actions-template-sync@v2 | n/a | scheduled |
| 28 | Dependabot | .github/dependabot.yml | weekly composer, monthly npm | grouped updates | n/a | deps |
| 29 | make ci (local only) | Makefile:592+ | manual | full aggregate incl. composer audit --locked, php-cs-fixer, 100% coverage unit tests | NOT wired to CI | local |

NOT FOUND: pre-commit hooks (no husky/captainhook/grumphp; package.json = {}), CODEOWNERS, SBOM, artifact signing/provenance, secrets scanning (no gitleaks/trufflehog), CodeQL, dependency-review-action, composite actions, codecov.yml, commit-message lint in CI, scheduled/nightly quality runs (only template-sync crons).

## Category scores (coverage / enforcement)
1. Static analysis & linting: 5/4 — Psalm errorLevel 8 lenient; Super-Linter non-blocking; php-cs-fixer local only
2. Unit/integration/e2e: 5/4 — 100% coverage gate NOT in CI
3. Test effectiveness: 4/5 — Infection MSI 100 blocking; Schemathesis shallow (max-examples 5, 2 checks excluded)
4. Security: 3/3 — Psalm taint + symfony security:check; missing secrets scan, CodeQL, container scan; PHPInsights security disabled; composer audit local-only; dependabot skips security checks
5. Supply chain: 2/2 — 40/49 SHA-pinned, 9 tag-pinned; no SBOM/signing/provenance/dependency-review
6. API & contract: 4/3 — openapi-diff decorative (no --fail-on-incompatible, args inverted); GraphQL blocks
7. Architecture: 5/5 — Deptrac --fail-on-uncovered + custom guards + min-architecture 100
8. Performance: 4/4 — binding k6 thresholds but 3-attempt retries; only smoke profile in CI; soak skips latency
9. Docs & release: 4/4 — external links unchecked; no commit-message enforcement in CI
10. CI health: 3/2 — no timeouts on 6 heavy jobs; duplicate workflow display names; required checks unknown; Dependabot skips 14 workflows (jobs pass vacuously green)

## Notable observations (verbatim from inventory agent)
- Dependabot bypasses nearly all verification: 14 workflows gate steps on !startsWith(github.actor, 'dependabot') — jobs complete "success" with steps skipped, so even required checks pass vacuously (tests.yml:17-21, psalm.yml:19-21, symfony.yml:63, infection.yml, E2Etests.yml, load-tests.yml, schemathesis.yml, deptrac.yml, phpinsights.yml, openapi-validator.yml, both diffs, cache-performance-tests.yml, code-lint-and-prettify.yml). Only bats/documentation/memory-tests run for Dependabot.
- openapi-diff decorative: openapi-diff.yml:91-96 no fail flags, inverted args, dead artifact upload (98-102)
- 100% coverage gate not in CI: tests.yml:32 runs make coverage-xml (no threshold); AGENTS.md:105 claims 100%; no codecov.yml
- Super-Linter continue-on-error:true (line 91), auto-commit with persist-credentials:true (line 32), GPG_PASSPHRASE in GITHUB_ENV (line 52)
- Psalm errorLevel 8 (psalm.xml:3)
- PHPInsights --fix + --disable-security-check in CI (Makefile:210-211)
- 9 tag-pinned actions (listed above); no pull_request_target (good); tempate-sync-pat.yml:18 PAT checkout persist-credentials default true
- permissions: block in only 8/20 workflows
- Naming: psalm.yml + phpinsights.yml both "code quality"; graphql-diff job labeled "Openapi-diff"; filename typo tempate-sync-pat.yml
- k6 smoke retried 3×; soak skips duration thresholds
- Schemathesis excludes negative_data_rejection + positive_data_acceptance, max-examples 5
- No git hooks despite ramsey/conventional-commits + php-cs-fixer in require-dev
- No nightly/scheduled quality runs; stress/spike load profiles and composer audit exist as make targets but never scheduled

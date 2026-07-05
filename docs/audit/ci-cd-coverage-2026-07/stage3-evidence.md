# Stage 3 — Evidence mining (VilnaCRM-Org/core-service)

## Escaped defects (all merged through fully-green CI)
| ref | defect | class | check that would have caught it | tool/rule | confidence |
|---|---|---|---|---|---|
| #231/PR#232/b5e0ec5 | Email case-sensitivity: cache lowercases, Mongo lookup+unique index case-sensitive → cache poisoning/collisions | case-sensitivity data bug | property-based/invariant tests | Eris/PHPUnit property test cacheKey(a)==cacheKey(b) ⇒ findByEmail(a)===findByEmail(b) | medium |
| #221/PR#228/c97b335 | uniqid() (non-CSPRNG) for domain event IDs in 7 classes | weak randomness | SAST | Semgrep php non-crypto-random rule banning uniqid/mt_rand/rand for IDs | high |
| #222/PR#227/0035532 | UlidFilterProcessor `new $class()` from query-string operator | unsafe dynamic instantiation | SAST | Semgrep variable-class-instantiation; Psalm taint | high |
| #223/PR#229/172123e | Destructive SchemathesisCleanupListener registered in ALL envs incl. prod | env-gating config error | prod-container lint | boot APP_ENV=prod container, assert no test-support services (debug:container lint) | medium |
| #218/PR#225/6010d7e | GraphQL introspection+GraphiQL+playground enabled in prod | insecure default | DAST vs prod-mode stack | graphql-cop / ZAP baseline vs APP_ENV=prod compose | high |
| #219/PR#226/768c22b | No HTTP security headers at Caddy edge | edge config error | DAST / header smoke | ZAP baseline or curl/Behat smoke asserting headers | high |
| #220 (OPEN) | Committed .env baked into image; Mercure JWT hardcoded fallback | committed secrets | secret scan + container scan | gitleaks CI + push protection; trivy config/hadolint | high |
| #224 (OPEN) | Mass-assignable `confirmed` flag on Customer | mass assignment | custom writable-fields lint | compare serialization groups/OpenAPI writable props to allow-list | low |
| #29/PR#191/d7d5776 | Empty {} PATCH returns 200 no-op | contract/validation bug | contract negative testing | minProperties:1 + Schemathesis negative mode | med-high |
| #28 | Unknown JSON fields silently accepted | lax deserialization | strict schema + fuzz | additionalProperties:false + serializer strictness test | medium |
| #27 | Boolean filter compares "false" string to bool | type-coercion query bug | filter test matrix / property tests | Behat filter×type matrix; property test vs in-memory predicate | medium |
| #94/PR#193/12f6d60 | InfiniteRetryStrategy always retryable → poison messages, no DLQ | resilience defect | chaos/integration test | messenger test: failing message → DLQ within N attempts | medium |
| PR#215/c3d0aac (fixed #217) | Invalid toLower() in Actions expressions → ALL push workflows on main failed at parse | CI config syntax error | workflow lint | actionlint as required job on .github/workflows/** | high |
| PR#217 autorelease | Autorelease re-tries existing tag v0.10.0; composer.json version out of sync | release config error | release consistency check | assert composer.json version > latest v* tag; failure alerting | medium |
| #62/PR#63 | Generated OpenAPI invalid per OAS 3.1 | spec break | spec validation | (since added: openapi-validator.yml) | high |
| #59 | Schemathesis failing on main for months while merges continued | never-green check | required checks / keep-main-green | required status check + main-failure alerting | medium |
| memory-tests history | memory-tests fails on 6/6 recent Dependabot PRs, still merged | CI bypass | required checks + remove Dependabot skip | branch protection; drop dependabot guard | high |
| #173/885789a(#174) | Customer write-path slow, GraphQL/REST parity shortfall shipped | perf regression | perf budgets | k6 hard budgets incl. parity | med-low |
| c3d0aac(#215) policy | Dependabot PRs skip ~15 workflows; ~15 dep PRs merged unverified (#198,#206–#216) | untested dependency updates | dependency-update CI | run full matrix for dependabot (secretless jobs) | high |

Note: PRs #225–#229/#232 all originate from an autonomous pentest sweep (issues #218–#224, #231) — found out-of-band, NOT by CI. No true revert commits on main.

## Gap ranking by proven impact
1. SAST (Semgrep/Psalm taint properly configured) → 3 defects (#221, #222, partial #224)
2. Contract/spec negative testing (strict OpenAPI + Schemathesis negative mode) → 4 defects (#62, #29, #28, partial #27)
3. DAST / prod-mode security smoke (ZAP baseline + graphql-cop vs APP_ENV=prod) → 3 defects (#218, #219, #223)
4. Branch protection/required checks + no Dependabot bypass → 3 systemic escapes (memory-tests, #59, 15+ unverified dep merges)
5. actionlint on workflows → 2 (#215 breakage, deprecated-action debt)
6. Secret/container scanning (gitleaks + trivy/hadolint) → 1 open (#220); NFR #303 endorses
7. Property-based/invariant testing → 2 (#231, #27); NFR #261 notes absence
8. Release pipeline validation/alerting → chronic (autorelease failing 30/30 recent runs, 5+ weeks)
9. Perf budgets in k6 CI → 1 (#173/#174)
10. Resilience/chaos tests (DLQ/poison messages) → 1 (#94)

## Existing open issues (dedup) — 93 open
Key overlap set — the NFR audit series #241–#329 already claims much CI-gap territory:
- #303 [NFR] Vulnerability: no CodeQL/secret/container/dependency scanning gates and Dependabot PRs skip CI (security)
- #304 [NFR] Credibility: broken release pipeline, no supply-chain attestation
- #290 [NFR] Stability: broken autorelease, CI-skipping Dependabot PRs, gates not CI-enforced
- #313 [NFR] Compatibility / #260 [NFR] Upgradability: dependency updates bypass CI
- #328 [NFR] Process: broken release/sync workflows, CI-bypass holes
- #261 [NFR] Provability: no property-based tests, Psalm loosest level 8
- #264 [NFR] Analyzability: Psalm level 8, thresholds hidden in Makefile
- #272 [NFR] Reproducibility: tag-pinned base images, no SBOM/provenance
- #271 [NFR] Repeatability: unseeded random test data, no flaky-test defense
- #275 auditability, #284 chaos/survivability, #327 producibility (no publishable artifact)
- #224 [Security] mass-assign confirmed (OPEN), #220 [Security] committed .env/Mercure secret (OPEN)
- Full list of 93 in agent transcript; #241 is parent audit issue; #153, #1 unrelated.

## CI health evidence
- autorelease.yml: 30/30 most recent runs (2026-05-09→06-14) FAILED on main push; 67 runs total; no releases produced. #217 fix did not cure.
- memory-tests.yml: fails 6/6 Dependabot PRs 2026-06-15→07-01 (lacks the skip guard), dep PRs merge anyway → proves it is NOT a required check.
- Dependabot skip since c3d0aac (#215, 2026-06-06): 15 workflows guard steps with !contains(toLower(github.actor),'dependabot'); jobs report success with all steps skipped. ~15 dep PRs merged unverified.
- #215 itself shipped invalid toLower() → all push workflows failed at parse 06-06→06-07 (#217). actionlint not a CI gate.
- tempate-sync-pat.yml latest scheduled run 2026-07-01 FAILED; template-sync chronically broken per NFR issues.
- Schemathesis failing on main 2025-10-31→2026-04-26 (#59) while merges continued.
- #142 (closed): negative Make-target CI tests had been dropped, later restored.

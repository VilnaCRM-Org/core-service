# Skill Decision Guide

**Choose the right skill for your task based on what you're trying to accomplish.**

## Quick Decision Tree

```
What are you trying to do?
│
├─ Fix something broken
│   ├─ Deptrac violation → deptrac-fixer
│   ├─ High complexity → complexity-management
│   ├─ Test failures → testing-workflow
│   ├─ PHPInsights fails → complexity-management
│   ├─ Slow queries/N+1 issues → query-performance-analysis
│   ├─ Stale cached data issues → cache-management
│   └─ CI checks failing → ci-workflow
│
├─ Create something new
│   ├─ New entity/value object → implementing-ddd-architecture
│   ├─ New API endpoint → developing-openapi-specs
│   ├─ New load test → load-testing
│   ├─ New database entity → database-migrations
│   ├─ New database indexes → query-performance-analysis
│   ├─ New test cases → testing-workflow
│   ├─ Add caching layer → cache-management
│   └─ Add observability → observability-instrumentation
│
├─ Review/validate work
│   ├─ Before committing → ci-workflow
│   ├─ PR feedback → code-review
│   └─ Quality thresholds → quality-standards
│
└─ Update documentation
    ├─ General docs → documentation-sync
    └─ Architecture diagrams → structurizr-architecture-sync
```

## Scenario-Based Guide

### "Deptrac is failing with violations"

**Use**: [deptrac-fixer](deptrac-fixer/SKILL.md)

This skill parses violation messages and provides exact fix patterns.

**NOT**: implementing-ddd-architecture (that's for designing new patterns)
**NOT**: quality-standards (that's just an overview)

---

### "I need to create a new entity with value objects"

**Use**: [implementing-ddd-architecture](implementing-ddd-architecture/SKILL.md)

This skill guides proper DDD structure and file placement.

**NOT**: deptrac-fixer (that's for fixing violations)
**NOT**: database-migrations (that's for the database side)

---

### "PHPInsights complexity score is too low"

**Use**: [complexity-management](complexity-management/SKILL.md)

This skill provides refactoring strategies to reduce complexity.

**NOT**: quality-standards (that's just an overview of thresholds)

---

### "I need to write K6 load tests"

**Use**: [load-testing](load-testing/SKILL.md)

This skill has REST and GraphQL load test patterns.

**NOT**: testing-workflow (that's for functional tests only)

---

### "Tests are failing and I need to debug"

**Use**: [testing-workflow](testing-workflow/SKILL.md)

This skill covers PHPUnit, Behat, and Infection debugging.

**NOT**: load-testing (that's for performance tests)
**NOT**: ci-workflow (that runs tests but doesn't debug)

---

### "I need to understand what quality metrics are protected"

**Use**: [quality-standards](quality-standards/SKILL.md)

This skill documents all thresholds and directs to specialized skills.

**NOT**: complexity-management (that's specifically for complexity)

---

### "I'm addressing PR review comments"

**Use**: [code-review](code-review/SKILL.md)

This skill systematically handles review feedback.

**NOT**: ci-workflow (that's for running checks)

---

### "I made code changes and need to validate before committing"

**Use**: [ci-workflow](ci-workflow/SKILL.md)

This skill runs comprehensive CI checks.

**NOT**: testing-workflow (that's specifically for tests)

---

### "I added a new feature and need to update docs"

**Use**: [documentation-sync](documentation-sync/SKILL.md)

This skill identifies which documentation files need updating.

**ALSO**: Use [structurizr-architecture-sync](structurizr-architecture-sync/SKILL.md) if you added components, handlers, or changed architecture.

---

### "I made architectural changes and need to update C4 diagrams"

**Use**: [structurizr-architecture-sync](structurizr-architecture-sync/SKILL.md)

This skill guides updating workspace.dsl for Structurizr C4 diagrams.

**NOT**: documentation-sync (that's for general docs in /docs)

---

### "I need to add a new field to an entity"

**Use**: [database-migrations](database-migrations/SKILL.md)

This skill guides entity modification with Doctrine ODM.

**ALSO**: Check [implementing-ddd-architecture](implementing-ddd-architecture/SKILL.md) for proper DDD patterns.

---

### "I'm adding OpenAPI endpoint documentation"

**Use**: [developing-openapi-specs](developing-openapi-specs/SKILL.md)

This skill covers processor patterns for OpenAPI.

---

### "I need to add logging, metrics, and tracing to my code"

**Use**: [observability-instrumentation](observability-instrumentation/SKILL.md)

This skill guides adding structured logs with correlation IDs, metrics (latency, errors, RPS), and tracing for DB/HTTP operations.

**When to use**:

- Implementing new command handlers
- Creating new API endpoints
- Adding database operations
- Instrumenting existing code for production
- Preparing code for deployment

**What it provides**:

- Structured logging patterns with correlation ID
- Metrics collection (duration, errors, throughput)
- DB/HTTP operation tracing
- PR evidence collection templates

**NOT**: testing-workflow (that's for functional tests)
**NOT**: load-testing (that's for performance tests)

---

### "My endpoint is slow / I have N+1 query problems"

**Use**: [query-performance-analysis](query-performance-analysis/SKILL.md)

This skill detects N+1 queries, analyzes slow queries with EXPLAIN, identifies missing indexes, and provides safe migration strategies.

**NOT**: database-migrations (that's for creating indexes in XML, not analyzing performance)
**NOT**: load-testing (that's for testing under load, not fixing slow queries)

**ALSO**: Use [load-testing](load-testing/SKILL.md) after fixing performance issues to prevent regression.

---

### "I need to add a database index for performance"

**Use**: [query-performance-analysis](query-performance-analysis/SKILL.md) first to analyze what indexes are needed

**THEN**: [database-migrations](database-migrations/SKILL.md) for XML mapping syntax

The query-performance-analysis skill tells you WHAT indexes to add (using EXPLAIN analysis), while database-migrations tells you HOW to add them (XML syntax).

---

### "I need to add caching to reduce database load"

**Use**: [cache-management](cache-management/SKILL.md)

This skill guides cache policy declaration, read-through caching, explicit invalidation, SWR pattern, and comprehensive testing.

**ALSO**: Use [query-performance-analysis](query-performance-analysis/SKILL.md) first to identify which queries are slow and worth caching.

**ALSO**: Use [observability-instrumentation](observability-instrumentation/SKILL.md) to add cache metrics (hit rate, latency).

---

### "Cached data is stale after updates"

**Use**: [cache-management](cache-management/SKILL.md)

This skill provides explicit invalidation strategies (write-through, tag-based, event-driven) and tests for stale read scenarios.

**NOT**: query-performance-analysis (that's for query optimization, not cache invalidation)

---

### "I need to test cache behavior (stale reads, cold start)"

**Use**: [cache-management](cache-management/SKILL.md)

This skill provides complete test patterns for:

- Stale reads after writes
- Cache warmup on cold start
- TTL expiration behavior
- Tag-based invalidation

**ALSO**: Use [testing-workflow](testing-workflow/SKILL.md) for general test guidance.

---

### "I want to implement stale-while-revalidate (SWR)"

**Use**: [cache-management](cache-management/SKILL.md)

This skill provides complete SWR implementation guide with background refresh patterns.

---

## Skill Relationship Map

```
                          quality-standards
                         (overview & routing)
                                 │
                    ┌────────────┼────────────┐
                    ▼            ▼            ▼
           complexity-    deptrac-fixer   testing-workflow
           management                            │
                              │                  │
                              ▼                  ▼
                    implementing-ddd-      load-testing
                      architecture         (performance)
                              │                  │
                    ┌─────────┼─────────┬────────┴────────┬────────────┐
                    ▼         ▼         ▼                 ▼            ▼
          database-    query-        cache-          documentation- structurizr-
          migrations   performance-  management      sync           architecture-sync
                      analysis                                      (C4 diagrams)
```

## Common Confusions

| Confusion                                           | Clarification                                                                                                                          |
| --------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| deptrac-fixer vs implementing-ddd-architecture      | **Fix violations** → deptrac-fixer<br>**Design new patterns** → implementing-ddd-architecture                                          |
| testing-workflow vs load-testing                    | **Functional tests** (unit, integration, E2E) → testing-workflow<br>**Performance tests** (K6) → load-testing                          |
| quality-standards vs complexity-management          | **Overview of all metrics** → quality-standards<br>**Fix complexity specifically** → complexity-management                             |
| ci-workflow vs testing-workflow                     | **Run all CI checks** → ci-workflow<br>**Debug specific test issues** → testing-workflow                                               |
| database-migrations vs query-performance-analysis   | **Index creation (HOW)** → database-migrations<br>**Performance analysis (WHAT/WHY)** → query-performance-analysis                     |
| query-performance-analysis vs load-testing          | **Fix slow queries** → query-performance-analysis<br>**Test under load** → load-testing                                                |
| query-performance-analysis vs cache-management      | **Fix slow queries (indexes)** → query-performance-analysis<br>**Add caching layer** → cache-management                                |
| cache-management vs observability-instrumentation   | **Implement caching** → cache-management<br>**Add metrics/logs for cache** → observability-instrumentation                             |
| documentation-sync vs structurizr-architecture-sync | **General documentation** (/docs) → documentation-sync<br>**C4 architecture diagrams** (workspace.dsl) → structurizr-architecture-sync |

## Multiple Skills for One Task

Some tasks benefit from multiple skills:

### Creating a complete new feature:

1. **implementing-ddd-architecture** - Design domain model
2. **observability-instrumentation** - Add logging, metrics, tracing
3. **database-migrations** - Configure persistence
4. **query-performance-analysis** - Optimize queries and add indexes
5. **cache-management** - Add caching layer for read-heavy operations
6. **testing-workflow** - Write tests (including cache tests)
7. **load-testing** - Add performance tests
8. **structurizr-architecture-sync** - Update C4 diagrams
9. **documentation-sync** - Update docs
10. **ci-workflow** - Validate everything

### Fixing architecture issues:

1. **deptrac-fixer** - Fix the violations
2. **implementing-ddd-architecture** - Understand why (if needed)
3. **ci-workflow** - Verify fix

### Performance optimization:

1. **query-performance-analysis** - Fix N+1 queries, add indexes
2. **cache-management** - Add caching layer for read-heavy queries
3. **observability-instrumentation** - Add cache metrics (hit rate, latency)
4. **load-testing** - Create performance tests
5. **complexity-management** - Reduce code complexity
6. **ci-workflow** - Ensure quality maintained

### Fixing slow API endpoint:

1. **query-performance-analysis** - Detect N+1, analyze with EXPLAIN
2. **database-migrations** - Add missing indexes (XML syntax)
3. **cache-management** - Add caching with proper invalidation
4. **testing-workflow** - Add cache tests (stale reads, cold start)
5. **load-testing** - Add performance regression tests
6. **documentation-sync** - Document performance considerations and cache policy
7. **ci-workflow** - Verify all checks pass

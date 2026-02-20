# Repository Guidelines for AI Agents

VilnaCRM Core Service is a PHP 8.4+ microservice built with Symfony 7, API Platform 4, and GraphQL. It provides core business functionality within the VilnaCRM ecosystem using REST API and GraphQL. The project follows hexagonal architecture with DDD & CQRS patterns and includes comprehensive testing across unit, integration, and E2E test suites.

## 🚨 CRITICAL FOR ALL AI AGENTS - READ THIS FIRST! 🚨

**BEFORE attempting to fix ANY issue in this repository, you MUST follow this workflow:**

### Mandatory Workflow for AI Agents

1. **READ** → `.claude/skills/AI-AGENT-GUIDE.md` (comprehensive guide for all AI agents)
2. **IDENTIFY** → Use `.claude/skills/SKILL-DECISION-GUIDE.md` to find the correct skill for your task
3. **EXECUTE** → Read the specific skill file (e.g., `.claude/skills/deptrac-fixer/SKILL.md`)
4. **FOLLOW** → Execute the step-by-step instructions in the skill file exactly as written

### ❌ DO NOT

- Fix issues directly from AGENTS.md without reading the skills
- Skip the skill decision guide
- Guess the fix based on general DDD knowledge
- Use only partial information from this file

### ✅ DO

- Always start with `.claude/skills/AI-AGENT-GUIDE.md`
- Use the decision tree in `SKILL-DECISION-GUIDE.md`
- Read the complete skill file for your specific task
- Check supporting files (`reference/`, `examples/`) as referenced in the skill

### Why This Matters

- The skills contain the **ACTUAL architecture patterns** used in this codebase
- AGENTS.md is a **reference**, not a complete fix guide
- Skills are **regularly updated** with correct patterns
- Following skills ensures **consistency** with the codebase

## Quick Reference

### Essential Execution Rules

1. **MANDATORY: Use Make Commands or Docker Container Access Only**

   - `make command-name` (preferred)
   - `docker compose exec php command` (direct container access)
   - `make sh` then run commands inside
   - **NEVER** run PHP commands directly on the host system

2. **CRITICAL: Run CI Before Finishing Tasks**

   - Execute `make ci` before completing any task
   - Must see "✅ CI checks successfully passed!" in output
   - Fix all issues if "❌ CI checks failed:" appears
   - **DO NOT** finish tasks until CI passes completely

3. **CRITICAL: Use GitHub Codespaces with Login Shells (for secrets/auth)**
   - Non-interactive commands: `gh codespace ssh -c <codespace-name> -- "bash -lc '<command>'"`
   - Interactive session: `gh codespace ssh -c <codespace-name>` then run `bash -l`
   - Run bootstrap from repo root when needed: `bash scripts/codespaces/setup-secure-agent-env.sh`
   - **NEVER** use non-login SSH commands for auth-dependent checks (`gh codespace ssh -- '<command>'`) because Codespaces secrets may not be loaded

### Quick Skill Guide

| Task Type                  | Skill                           | When to Use                                  |
| -------------------------- | ------------------------------- | -------------------------------------------- |
| **Fix Deptrac violations** | `deptrac-fixer`                 | Architecture boundary violations detected    |
| **Fix complexity issues**  | `complexity-management`         | PHPInsights complexity score drops           |
| **Run CI checks**          | `ci-workflow`                   | Before committing, validating changes        |
| **Debug test failures**    | `testing-workflow`              | PHPUnit, Behat, or Infection issues          |
| **Handle PR feedback**     | `code-review`                   | Processing code review comments              |
| **Create DDD patterns**    | `implementing-ddd-architecture` | New entities, value objects, aggregates      |
| **Add CRUD endpoints**     | `api-platform-crud`             | New API resources with full CRUD             |
| **Create load tests**      | `load-testing`                  | K6 performance tests (REST/GraphQL)          |
| **Update entity schema**   | `database-migrations`           | Modifying entities, adding fields            |
| **Document APIs**          | `developing-openapi-specs`      | OpenAPI endpoint factories                   |
| **Develop OpenAPI layer**  | `openapi-development`           | OpenAPI processors, complexity patterns      |
| **Organize code**          | `code-organization`             | Proper class placement, naming consistency   |
| **Sync documentation**     | `documentation-sync`            | After any code changes                       |
| **Quality overview**       | `quality-standards`             | Understanding protected thresholds           |
| **Optimize queries**       | `query-performance-analysis`    | N+1 detection, slow queries, missing indexes |
| **Add observability**      | `observability-instrumentation` | Logs, metrics, traces for new features       |
| **Implement caching**      | `cache-management`              | Cache policies, invalidation, SWR, testing   |

> **📋 Detailed Guide**: See `.claude/skills/SKILL-DECISION-GUIDE.md` for decision trees and scenarios.

### Protected Quality Thresholds

This project enforces **strict quality thresholds** that MUST NOT be lowered:

| Tool              | Metric       | Required           | Skill for Issues        |
| ----------------- | ------------ | ------------------ | ----------------------- |
| **PHPInsights**   | Complexity   | 93% src, 95% tests | `complexity-management` |
| **PHPInsights**   | Quality      | 100%               | `complexity-management` |
| **PHPInsights**   | Architecture | 100%               | `deptrac-fixer`         |
| **PHPInsights**   | Style        | 100%               | Run `make phpcsfixer`   |
| **Deptrac**       | Violations   | 0                  | `deptrac-fixer`         |
| **Psalm**         | Errors       | 0                  | Fix reported issues     |
| **Test Coverage** | Lines        | 100%               | `testing-workflow`      |
| **Infection MSI** | Score        | 100%               | `testing-workflow`      |

> **⚠️ NEVER lower thresholds**. Always fix code to meet standards. See `quality-standards` skill for details.

### Essential Commands

| Category         | Command                      | Description             | Related Skill              |
| ---------------- | ---------------------------- | ----------------------- | -------------------------- |
| **Docker**       | `make start`                 | Start containers        | -                          |
|                  | `make sh`                    | Access PHP container    | -                          |
|                  | `make build`                 | Build containers        | -                          |
| **Quality**      | `make phpcsfixer`            | Fix code style          | -                          |
|                  | `make psalm`                 | Static analysis         | -                          |
|                  | `make phpinsights`           | Quality checks          | `complexity-management`    |
|                  | `make deptrac`               | Architecture validation | `deptrac-fixer`            |
| **Testing**      | `make unit-tests`            | Unit tests only         | `testing-workflow`         |
|                  | `make integration-tests`     | Integration tests       | `testing-workflow`         |
|                  | `make e2e-tests`             | Behat E2E tests         | `testing-workflow`         |
|                  | `make all-tests`             | All functional tests    | `testing-workflow`         |
|                  | `make infection`             | Mutation testing        | `testing-workflow`         |
| **Load Testing** | `make smoke-load-tests`      | Minimal load test       | `load-testing`             |
|                  | `make load-tests`            | All load tests          | `load-testing`             |
| **CI**           | `make ci`                    | Run all CI checks       | `ci-workflow`              |
| **Database**     | `make setup-test-db`         | Reset test MongoDB      | `database-migrations`      |
| **API Docs**     | `make generate-openapi-spec` | Export OpenAPI          | `developing-openapi-specs` |
| **Code Review**  | `make pr-comments`           | Fetch PR comments       | `code-review`              |

## Architecture Overview

This project follows **Hexagonal Architecture** with **DDD** and **CQRS** patterns. Architecture boundaries are enforced via Deptrac.

**CRITICAL RULE**: Domain layer must have NO framework dependencies.

> **For complete architectural patterns, layer rules, and directory structure, see `.claude/skills/implementing-ddd-architecture/SKILL.md`**

## Common Workflows

### Development Workflow

1. **Start Environment** → `make build` (first time), `make start`, `make install`
2. **Make Changes** → Follow architecture patterns from skills, respect layer boundaries
3. **Quality Checks** → Run relevant quality commands (see Essential Commands table)
4. **Complete Task** → Run `make ci` (MUST see "✅ CI checks successfully passed!")

### Fixing Issues Workflow

1. **Identify Issue Type** → Use `.claude/skills/SKILL-DECISION-GUIDE.md`
2. **Read Appropriate Skill** → Follow instructions exactly
3. **Apply Fix** → Use patterns from skill examples
4. **Verify** → Run relevant quality checks
5. **Complete** → Run `make ci` before finishing

### Code Review Workflow

1. **Fetch Comments** → `make pr-comments`
2. **Address Systematically** → See `.claude/skills/code-review/SKILL.md`
3. **Verify Changes** → Run quality checks after each fix
4. **Complete** → Run `make ci` before pushing

## Key Principles

1. **Skills First**: Always consult skills before making changes
2. **Quality Standards**: Never lower thresholds, always improve code
3. **Architecture Boundaries**: Respect Deptrac rules, NO frameworks in Domain
4. **Make Commands**: NEVER run PHP commands outside container
5. **CI Validation**: MUST pass `make ci` before completing tasks
6. **Documentation Sync**: Update docs when changing code (see `documentation-sync` skill)

## Getting Help

- **General guidance**: `.claude/skills/AI-AGENT-GUIDE.md`
- **Skill selection**: `.claude/skills/SKILL-DECISION-GUIDE.md`
- **Specific workflows**: `.claude/skills/{skill-name}/SKILL.md`
- **Examples**: `.claude/skills/{skill-name}/examples/`
- **Troubleshooting**: `.claude/skills/{skill-name}/reference/`

## Technology Stack

PHP 8.4+, Symfony 7, API Platform 4, MongoDB, GraphQL. See project documentation for complete technical details.

---

**For detailed implementation patterns, workflows, and examples → See modular skills in `.claude/skills/` directory.**

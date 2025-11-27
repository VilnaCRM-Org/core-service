# Repository Guidelines for AI Agents

VilnaCRM Core Service is a PHP 8.3+ microservice built with Symfony 7, API Platform 4, and GraphQL. It provides core business functionality within the VilnaCRM ecosystem using REST API and GraphQL. The project follows hexagonal architecture with DDD & CQRS patterns and includes comprehensive testing across unit, integration, and E2E test suites.

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

### Quick Skill Guide

| Task Type                  | Skill                           | When to Use                               |
| -------------------------- | ------------------------------- | ----------------------------------------- |
| **Fix Deptrac violations** | `deptrac-fixer`                 | Architecture boundary violations detected |
| **Fix complexity issues**  | `complexity-management`         | PHPInsights complexity score drops        |
| **Run CI checks**          | `ci-workflow`                   | Before committing, validating changes     |
| **Debug test failures**    | `testing-workflow`              | PHPUnit, Behat, or Infection issues       |
| **Handle PR feedback**     | `code-review`                   | Processing code review comments           |
| **Create DDD patterns**    | `implementing-ddd-architecture` | New entities, value objects, aggregates   |
| **Add CRUD endpoints**     | `api-platform-crud`             | New API resources with full CRUD          |
| **Create load tests**      | `load-testing`                  | K6 performance tests (REST/GraphQL)       |
| **Update entity schema**   | `database-migrations`           | Modifying entities, adding fields         |
| **Document APIs**          | `developing-openapi-specs`      | OpenAPI endpoint factories                |
| **Sync documentation**     | `documentation-sync`            | After any code changes                    |
| **Quality overview**       | `quality-standards`             | Understanding protected thresholds        |

> **📋 Detailed Guide**: See `.claude/skills/SKILL-DECISION-GUIDE.md` for decision trees and scenarios.

### Protected Quality Thresholds

This project enforces **strict quality thresholds** that MUST NOT be lowered:

| Tool              | Metric       | Required | Skill for Issues        |
| ----------------- | ------------ | -------- | ----------------------- |
| **PHPInsights**   | Complexity   | 94% min  | `complexity-management` |
| **PHPInsights**   | Quality      | 100%     | `complexity-management` |
| **PHPInsights**   | Architecture | 100%     | `deptrac-fixer`         |
| **PHPInsights**   | Style        | 100%     | Run `make phpcsfixer`   |
| **Deptrac**       | Violations   | 0        | `deptrac-fixer`         |
| **Psalm**         | Errors       | 0        | Fix reported issues     |
| **Test Coverage** | Lines        | 100%     | `testing-workflow`      |
| **Infection MSI** | Score        | 100%     | `testing-workflow`      |

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

### Layer Dependency Rules (CRITICAL)

The architecture enforces strict layer boundaries via Deptrac:

```text
Infrastructure → Application → Domain
      ↓              ↓           ↓
  External       Use Cases    Pure Business
```

| From Layer         | Can Depend On                                       | CANNOT Depend On |
| ------------------ | --------------------------------------------------- | ---------------- |
| **Domain**         | NOTHING (pure PHP only)                             | Everything       |
| **Application**    | Domain, Infrastructure, Symfony, API Platform, etc. | N/A              |
| **Infrastructure** | Domain, Application, Symfony, Doctrine, etc.        | N/A              |

**CRITICAL**: Domain layer must have NO framework imports (Symfony, Doctrine, API Platform, MongoDB).

> **For detailed architectural patterns, see `.claude/skills/implementing-ddd-architecture/SKILL.md`**

### Directory Structure

```text
src/
├── Core/Customer/              # Customer bounded context
│   ├── Application/            # Use Cases (Commands, Handlers, DTOs, Processors)
│   ├── Domain/                 # Pure Business Logic (NO framework imports!)
│   └── Infrastructure/         # Technical Implementation (Repositories)
├── Internal/                   # Internal services (HealthCheck)
└── Shared/                     # Shared Kernel
    ├── Application/            # Cross-cutting concerns
    ├── Domain/                 # Interfaces, abstract classes, common entities
    └── Infrastructure/         # Message Buses, Doctrine types, retry strategies
```

> **For complete structure and patterns, see `.claude/skills/implementing-ddd-architecture/SKILL.md`**

## Common Workflows

### Development Workflow

1. **Start Environment**
   ```bash
   make build          # First time only (15-30 min, NEVER CANCEL)
   make start          # Start containers (5-10 min)
   make install        # Install dependencies (3-5 min)
   ```

2. **Make Changes**
   - Follow architecture patterns from skills
   - Respect layer boundaries (Domain → NO frameworks)
   - Use skills for guidance

3. **Quality Checks**
   ```bash
   make phpcsfixer     # Fix code style
   make psalm          # Static analysis
   make deptrac        # Architecture validation
   make all-tests      # Run all tests
   make phpinsights    # Quality checks
   ```

4. **Complete Task**
   ```bash
   make ci             # MUST see "✅ CI checks successfully passed!"
   ```

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

## Environment Information

- **PHP Version**: 8.3.12 (minimum 8.2)
- **Database**: MongoDB with Doctrine ODM
- **Frameworks**: Symfony 7, API Platform 4
- **Testing**: PHPUnit, Behat, Infection, K6
- **Quality Tools**: Psalm, PHPInsights, Deptrac, PHP CS Fixer

---

**For detailed implementation patterns, workflows, and examples → See modular skills in `.claude/skills/` directory.**

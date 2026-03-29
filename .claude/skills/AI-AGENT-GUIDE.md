# AI Agent Guide to Claude Skills System

**For OpenAI Agents (GPT-4, GPT-4o, o1, CODEX), GitHub Copilot, Cursor, and other AI coding assistants**

## Overview

This repository uses a modular **Skills system** originally designed for Claude Code but structured to be **AI-agnostic**. All skills are pure markdown files that any AI agent can read and execute.

## How This Works

### For Claude Code

Claude Code automatically discovers and invokes skills using its `Skill` tool when tasks match skill descriptions.

### For OpenAI Agents & Others

You need to manually discover and read skill files, then follow their step-by-step instructions.

## Quick Start for OpenAI Agents

### Step 1: Understand Your Task

When the user requests a task, first determine which skill is most relevant.

### Step 2: Read the Decision Guide

Read `.claude/skills/SKILL-DECISION-GUIDE.md` to choose the appropriate skill:

```
Quick Decision Tree:
│
├─ Fix something broken
│   ├─ Deptrac violation → deptrac-fixer
│   ├─ High complexity → complexity-management
│   ├─ Test failures → testing-workflow
│   └─ CI checks failing → ci-workflow
│
├─ Create something new
│   ├─ Full BMAD specs from short prompt → bmad-autonomous-planning
│   ├─ New entity/value object → implementing-ddd-architecture
│   ├─ New API endpoint → api-platform-crud
│   ├─ New load test → load-testing
│   └─ New database entity → database-migrations
│
├─ Review/validate work
│   ├─ Before committing → ci-workflow
│   └─ PR feedback → code-review
│
└─ Update documentation
    └─ Any code change → documentation-sync
```

### Step 3: Read the Skill File

Each skill has a main `SKILL.md` file at `.claude/skills/{skill-name}/SKILL.md`

**Example**: For CI workflow issues, read:

```
.claude/skills/ci-workflow/SKILL.md
```

### Step 4: Follow Execution Steps

Each skill provides structured execution steps. Follow them sequentially:

**Example from ci-workflow:**

```markdown
## Execution Steps

### Step 1: Run CI

make ci

### Step 2: Check Result

- ✅ Success: "✅ CI checks successfully passed!" → Task complete
- ❌ Failure: "❌ CI checks failed:" → Go to Step 3

### Step 3: Fix Failures

[Specific fix instructions...]
```

### Step 5: Check Supporting Files

Complex skills have multi-file structure:

```
.claude/skills/{skill-name}/
├── SKILL.md              # Core workflow (start here)
├── reference/            # Detailed reference docs
│   ├── troubleshooting.md
│   ├── configuration.md
│   └── patterns.md
└── examples/             # Complete working examples
    └── example-*.md
```

**When to read supporting files:**

- Encountering errors → `reference/troubleshooting.md`
- Need detailed patterns → `reference/*.md`
- Want complete examples → `examples/*.md`

## Available Skills (15 Total)

### 🤖 Autonomous Planning Skills

| Skill                        | File                                | When to Use                                                                                                               |
| ---------------------------- | ----------------------------------- | ------------------------------------------------------------------------------------------------------------------------- |
| **Autonomous BMAD Planning** | `bmad-autonomous-planning/SKILL.md` | Create BMAD research, brief, PRD, architecture, and epics/stories from a short task description without human interaction |

### 🔧 Workflow Skills

| Skill                | File                        | When to Use                                      |
| -------------------- | --------------------------- | ------------------------------------------------ |
| **CI Workflow**      | `ci-workflow/SKILL.md`      | Run all quality checks before committing         |
| **Code Review**      | `code-review/SKILL.md`      | Address PR review comments systematically        |
| **Testing Workflow** | `testing-workflow/SKILL.md` | Run/debug unit, integration, E2E, mutation tests |

### 🏗️ Architecture & Quality Skills

| Skill                     | File                                     | When to Use                                      |
| ------------------------- | ---------------------------------------- | ------------------------------------------------ |
| **Implementing DDD**      | `implementing-ddd-architecture/SKILL.md` | Create entities, value objects, aggregates, CQRS |
| **Deptrac Fixer**         | `deptrac-fixer/SKILL.md`                 | Fix architectural boundary violations            |
| **Quality Standards**     | `quality-standards/SKILL.md`             | Overview of protected quality thresholds         |
| **Complexity Management** | `complexity-management/SKILL.md`         | Reduce cyclomatic complexity in code             |
| **OpenAPI Specs**         | `developing-openapi-specs/SKILL.md`      | Add OpenAPI documentation with processor pattern |

### 💾 Database & Documentation Skills

| Skill                   | File                           | When to Use                                      |
| ----------------------- | ------------------------------ | ------------------------------------------------ |
| **Database Migrations** | `database-migrations/SKILL.md` | Create/modify MongoDB entities with Doctrine ODM |
| **Documentation Sync**  | `documentation-sync/SKILL.md`  | Keep docs synchronized with code changes         |

### 🚀 API & Performance Skills

| Skill                 | File                         | When to Use                                  |
| --------------------- | ---------------------------- | -------------------------------------------- |
| **API Platform CRUD** | `api-platform-crud/SKILL.md` | Create complete REST API CRUD with DDD/CQRS  |
| **Load Testing**      | `load-testing/SKILL.md`      | Create K6 performance tests for REST/GraphQL |

### 📊 Observability & Performance Skills

| Skill                             | File                                     | When to Use                                               |
| --------------------------------- | ---------------------------------------- | --------------------------------------------------------- |
| **Observability Instrumentation** | `observability-instrumentation/SKILL.md` | Add structured logs, metrics, traces to new/existing code |
| **Query Performance Analysis**    | `query-performance-analysis/SKILL.md`    | Detect N+1 queries, analyze slow queries, add indexes     |

## Practical Examples

### Example 1: User asks to "fix Deptrac violations"

**Your workflow:**

1. **Identify skill**: Read `SKILL-DECISION-GUIDE.md` → Points to `deptrac-fixer`
2. **Read skill**: Open `.claude/skills/deptrac-fixer/SKILL.md`
3. **Execute**: Follow the diagnostic and fix patterns in the file
4. **Validate**: Run `make deptrac` to verify fixes

### Example 2: User asks to "add a new Customer entity"

**Your workflow:**

1. **Identify skills**: Need multiple skills:
   - `implementing-ddd-architecture` - Design the entity
   - `database-migrations` - Configure persistence
   - `api-platform-crud` - Add REST endpoints
   - `testing-workflow` - Write tests
   - `ci-workflow` - Validate everything

2. **Read each skill** in order and execute steps

3. **Use examples**: Check `.claude/skills/api-platform-crud/examples/complete-customer-crud.md` for full example

### Example 3: User asks to "run tests"

**Your workflow:**

1. **Identify skill**: `testing-workflow`
2. **Read**: `.claude/skills/testing-workflow/SKILL.md`
3. **Execute**: Run appropriate test commands (`make unit-tests`, `make integration-tests`, etc.)
4. **Debug failures**: Follow troubleshooting steps in the skill file

## Key Differences from Claude Code

| Aspect                | Claude Code              | OpenAI/Other Agents                   |
| --------------------- | ------------------------ | ------------------------------------- |
| **Discovery**         | Automatic                | Manual (read SKILL-DECISION-GUIDE.md) |
| **Invocation**        | Automatic via Skill tool | Manual (read SKILL.md file)           |
| **Execution**         | Guided by tool           | Self-guided (follow steps)            |
| **Multi-file skills** | Automatically loaded     | Read supporting files as needed       |

## Quality Standards & Protected Thresholds

**CRITICAL**: This project has **protected quality thresholds** that MUST NOT be lowered:

| Tool        | Metric       | Required           | Skill for Issues        |
| ----------- | ------------ | ------------------ | ----------------------- |
| PHPInsights | Complexity   | 93% src, 95% tests | `complexity-management` |
| PHPInsights | Quality      | 100%               | `complexity-management` |
| PHPInsights | Architecture | 100%               | `deptrac-fixer`         |
| PHPInsights | Style        | 100%               | Run `make phpcsfixer`   |
| Deptrac     | Violations   | 0                  | `deptrac-fixer`         |
| Psalm       | Errors       | 0                  | Fix reported issues     |
| Infection   | MSI          | High %             | `testing-workflow`      |

**Always improve code quality to meet standards. Never lower thresholds.**

## Common Workflows

### Before Every Commit

1. Read: `ci-workflow/SKILL.md`
2. Execute: `make ci`
3. Success criteria: Output shows "✅ CI checks successfully passed!"
4. If fails: Follow fix instructions in the skill

### Creating New Features

1. Read: `implementing-ddd-architecture/SKILL.md` - Design domain model
2. Read: `database-migrations/SKILL.md` - Configure persistence
3. Read: `api-platform-crud/SKILL.md` - Add API endpoints
4. Read: `testing-workflow/SKILL.md` - Write tests
5. Read: `documentation-sync/SKILL.md` - Update docs
6. Read: `ci-workflow/SKILL.md` - Validate everything

### Fixing Quality Issues

1. Identify issue type (Deptrac? Complexity? Tests?)
2. Read `SKILL-DECISION-GUIDE.md` to find the right skill
3. Read the specific skill file
4. Follow fix instructions
5. Run `make ci` to verify

## File Structure Reference

```
.claude/skills/
├── AI-AGENT-GUIDE.md           # This file - start here
├── SKILL-DECISION-GUIDE.md     # Decision tree for choosing skills
├── README.md                   # Skills overview
│
├── ci-workflow/
│   └── SKILL.md                # Run comprehensive CI checks
│
├── testing-workflow/
│   └── SKILL.md                # Functional testing guidance
│
├── code-review/
│   └── SKILL.md                # PR review workflow
│
├── implementing-ddd-architecture/
│   ├── SKILL.md                # Core DDD patterns
│   ├── REFERENCE.md            # Detailed workflows
│   ├── DIRECTORY-STRUCTURE.md  # File placement guide
│   └── examples/               # Working code examples
│
├── deptrac-fixer/
│   ├── SKILL.md                # Core diagnostic patterns
│   ├── REFERENCE.md            # Advanced patterns
│   └── examples/               # Fix examples
│
├── complexity-management/
│   ├── SKILL.md                # Core workflow
│   ├── refactoring-strategies.md
│   └── reference/              # Metrics, tools, monitoring
│
├── database-migrations/
│   ├── SKILL.md                # Main guide
│   ├── entity-creation-guide.md
│   ├── repository-patterns.md
│   └── reference/troubleshooting.md
│
├── api-platform-crud/
│   ├── SKILL.md                # 10-step CRUD guide
│   ├── examples/complete-customer-crud.md
│   └── reference/              # Filters, troubleshooting
│
├── load-testing/
│   ├── SKILL.md                # Core workflow
│   ├── rest-api-patterns.md
│   ├── graphql-patterns.md
│   ├── examples/               # Complete K6 examples
│   └── reference/              # Config, utils, troubleshooting
│
├── developing-openapi-specs/
│   └── SKILL.md                # OpenAPI processor patterns
│
├── documentation-sync/
│   └── SKILL.md                # Doc synchronization workflow
│
├── observability-instrumentation/
│   └── SKILL.md                # Add instrumentation to code
│
├── quality-standards/
│   └── SKILL.md                # Quality thresholds overview
│
└── query-performance-analysis/
    └── SKILL.md                # Detect and fix slow queries
```

## Tips for Effective Use

### ✅ DO

- Always start with `SKILL-DECISION-GUIDE.md` when unsure
- Read the entire SKILL.md file before executing
- Follow execution steps sequentially
- Check supporting files (`reference/`, `examples/`) when stuck
- Run `make ci` before finishing any task
- Respect protected quality thresholds

### ❌ DON'T

- Skip reading the decision guide
- Jump directly to execution without reading the full skill
- Lower quality thresholds to make checks pass
- Modify skill files without understanding the workflow
- Ignore supporting documentation when errors occur

## Getting Help

If you encounter issues:

1. **Read troubleshooting**: Most skills have `reference/troubleshooting.md`
2. **Check examples**: Look in `examples/` directory for working patterns
3. **Review AGENTS.md**: Comprehensive repository guidelines
4. **Review CLAUDE.md**: Quick reference for commands and architecture

## Integration with Existing Documentation

This skills system integrates with:

- **AGENTS.md**: Comprehensive repository guidelines (60KB)
- **CLAUDE.md**: Concise project instructions (6.5KB)
- **docs/**: User and developer documentation
- **Makefile**: All executable commands

## Conclusion

The skills system provides **modular, reusable workflows** that work across different AI agents. While Claude Code invokes them automatically, OpenAI agents and others can achieve the same results by reading and following the skill files manually.

**Start here:**

1. Read this guide (you're done! ✓)
2. Read `.claude/skills/SKILL-DECISION-GUIDE.md`
3. Pick a skill based on your task
4. Follow the skill's execution steps

Good luck! 🚀

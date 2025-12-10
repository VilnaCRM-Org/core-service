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

| Task Type                  | Skill                           | When to Use                                |
| -------------------------- | ------------------------------- | ------------------------------------------ |
| **Fix Deptrac violations** | `deptrac-fixer`                 | Architecture boundary violations detected  |
| **Fix complexity issues**  | `complexity-management`         | PHPInsights complexity score drops         |
| **Run CI checks**          | `ci-workflow`                   | Before committing, validating changes      |
| **Debug test failures**    | `testing-workflow`              | PHPUnit, Behat, or Infection issues        |
| **Handle PR feedback**     | `code-review`                   | Processing code review comments            |
| **Create DDD patterns**    | `implementing-ddd-architecture` | New entities, value objects, aggregates    |
| **Add CRUD endpoints**     | `api-platform-crud`             | New API resources with full CRUD           |
| **Create load tests**      | `load-testing`                  | K6 performance tests (REST/GraphQL)        |
| **Update entity schema**   | `database-migrations`           | Modifying entities, adding fields          |
| **Document APIs**          | `developing-openapi-specs`      | OpenAPI endpoint factories                 |
| **Develop OpenAPI layer**  | `openapi-development`           | OpenAPI processors, complexity patterns    |
| **Organize code**          | `code-organization`             | Proper class placement, naming consistency |
| **Sync documentation**     | `documentation-sync`            | After any code changes                     |
| **Quality overview**       | `quality-standards`             | Understanding protected thresholds         |

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

## What Is Core Service?

The VilnaCRM Core Service is designed to provide core business functionality within the VilnaCRM ecosystem. It implements essential domain models and business logic with REST API and GraphQL interfaces, ensuring seamless integration with other components of the CRM system.

### Key Features

- **Customer Management**: Comprehensive customer entity management with types and statuses
- **Flexibility**: REST API and GraphQL interfaces for versatile integration
- **Modern Architecture**: Built on Hexagonal Architecture, DDD, CQRS, and Event-Driven principles
- **High Quality**: 100% test coverage with mutation testing and comprehensive quality checks

### Design Principles

- **Hexagonal Architecture**: Separates core business logic from external dependencies
- **Domain-Driven Design**: Focuses on core domain logic with bounded contexts
- **CQRS**: Separates read and write operations for better performance and scalability
- **Event-Driven Architecture**: Uses domain events for loose coupling and extensibility
- **Modern PHP Stack**: Leverages latest PHP features and best practices

## Command Reference

### Quick Start

1. `make build` (15-30 min, NEVER CANCEL)
2. `make start` (5-10 min, includes MongoDB)
3. `make install` (3-5 min, PHP dependencies)
4. Verify: [API Docs](https://localhost/api/docs), [GraphQL](https://localhost/api/graphql)

### Essential Development Commands

- `make start` -- Start all services (Docker containers, MongoDB)
- `make stop` -- Stop all services
- `make sh` -- Access PHP container shell for manual commands
- `make install` -- Install PHP dependencies via Composer
- `make cache-clear` -- Clear Symfony cache
- `make logs` -- Show all service logs
- `make new-logs` -- Show live logs

### Testing Commands

- `make unit-tests` -- Run unit tests with 100% coverage requirement
- `make integration-tests` -- Test database/external services
- `make behat` -- E2E tests via BDD scenarios
- `make all-tests` -- Run complete test suite (unit, integration, e2e)
- `make setup-test-db` -- Drop and recreate test MongoDB schema
- `make tests-with-coverage` -- Generate code coverage report
- `make coverage-html` -- Generate HTML coverage report
- `make infection` -- Mutation testing with Infection (100% MSI required)

### Code Quality Commands (Run Before Every Commit)

- `make phpcsfixer` -- Auto-fix PHP code style (PSR-12)
- `make psalm` -- Static analysis for type safety
- `make phpinsights` -- Code quality analysis
- `make deptrac` -- Architecture dependency validation

### Comprehensive CI Quality Checks

#### Important: Run comprehensive CI checks before finishing any task and committing changes

##### Primary CI command

- `make ci` -- Run all comprehensive CI checks (composer validation, security analysis, code style, static analysis, architecture validation, complete test suite, mutation testing). **MUST output "✅ CI checks successfully passed!" at the end when all checks pass successfully. If any check fails, outputs "❌ CI checks failed:" with specific error details.**

##### Individual CI commands

- `make composer-validate` -- Validate composer.json and composer.lock
- `make check-requirements` -- Check Symfony requirements
- `make check-security` -- Security vulnerability analysis
- `make phpcsfixer` -- Auto-fix PHP code style (PSR-12)
- `make psalm` -- Static analysis for type safety
- `make psalm-security` -- Security taint analysis
- `make phpmd` -- PHP Mess Detector for complexity analysis
- `make phpinsights` -- Code quality analysis
- `make deptrac` -- Architecture dependency validation
- `make unit-tests` -- Unit test suite with 100% coverage
- `make integration-tests` -- Integration test suite
- `make behat` -- End-to-end BDD tests
- `make infection` -- Mutation testing with 100% MSI requirement

##### Mandatory workflow before finishing tasks

1. Make your code changes
2. Run `make ci` to execute all quality checks
3. **CRITICAL**: The `make ci` command MUST output "✅ CI checks successfully passed!" at the end
4. If you see "❌ CI checks failed:" message, you must address the reported issues and rerun `make ci`
5. **DO NOT finish the task** until you see "✅ CI checks successfully passed!" in the output
6. Fix any issues reported by the checks iteratively
7. Ensure all tests pass and code coverage is maintained
8. Commit your changes only after CI passes completely with the success message

### Load Testing Commands

- `make load-tests` -- Run complete load test suite with K6
- `make smoke-load-tests` -- Minimal load testing
- `make average-load-tests` -- Average load scenarios
- `make stress-load-tests` -- High load testing
- `make spike-load-tests` -- Extreme spike testing
- `make execute-load-tests-script scenario=<name>` -- Run specific scenario
- `make validate-load-test-naming` -- Validate naming/location of load-test scripts (run automatically in `make ci`)

#### Load Test Script Naming Rules

- Every script in `tests/Load/scripts/` must start with a lowercase character (e.g., `cleanupCustomers.js`)
- REST scenarios belong in `tests/Load/scripts/rest-api/`; GraphQL scenarios belong in `tests/Load/scripts/graphql/`
- The `validate-load-test-naming` target fails CI if any script violates these rules—update AI prompts and manual workflows accordingly

### Database Commands

- `make setup-test-db` -- Drop and recreate test MongoDB schema
- `make reset-db` -- Recreate the database schema
- `make load-fixtures` -- Load database fixtures

### Specification Generation

- `make generate-openapi-spec` -- Export OpenAPI YAML specification
- `make generate-graphql-spec` -- Export GraphQL specification
- `make validate-openapi-spec` -- Validate OpenAPI spec with Spectral
- `make openapi-diff` -- Compare OpenAPI spec against base reference

## Claude Code Skills

This repository includes comprehensive Claude Code Skills in the `.claude/skills/` directory to assist with development tasks.

### Available Skills

#### Workflow Skills

- **[ci-workflow](.claude/skills/ci-workflow/SKILL.md)**: Run comprehensive CI checks before committing
- **[code-review](.claude/skills/code-review/SKILL.md)**: Systematically retrieve and address PR code review comments
- **[testing-workflow](.claude/skills/testing-workflow/SKILL.md)**: Run and manage all test types (unit, integration, E2E, mutation, load)

#### Code Quality Skills

- **[quality-standards](.claude/skills/quality-standards/SKILL.md)**: Maintain and improve code quality without decreasing thresholds
- **[database-migrations](.claude/skills/database-migrations/SKILL.md)**: Create and manage MongoDB database migrations with Doctrine ODM
- **[documentation-sync](.claude/skills/documentation-sync/SKILL.md)**: Keep documentation synchronized with code changes

#### Performance Skills

- **[load-testing](.claude/skills/load-testing/SKILL.md)**: Create and manage K6 load tests for REST and GraphQL APIs

### Skill Structure

Skills follow Claude Code best practices with multi-file structure:

- **Main SKILL.md**: Core workflow and quick reference (<300 lines)
- **Supporting files**: Detailed patterns, examples, and reference guides
- **Examples/**: Complete working code examples
- **Reference/**: Troubleshooting and advanced topics

##### Example: The `load-testing` skill has

- `SKILL.md` (210 lines) - Core workflow
- `rest-api-patterns.md` - REST API patterns
- `graphql-patterns.md` - GraphQL patterns
- `examples/` - Complete working examples
- `reference/` - Configuration, troubleshooting, extensions

### Using Skills

Skills are **model-invoked** - Claude automatically activates them based on context. You don't need to manually invoke skills; Claude recognizes when a skill is relevant based on:

- Keywords in your request (e.g., "run tests", "create migration", "update docs")
- Current task context
- Skill descriptions

See [.claude/skills/README.md](.claude/skills/README.md) for complete skill documentation and usage patterns.

## Architecture Overview

This project follows **Hexagonal Architecture** with **DDD** and **CQRS** patterns. Architecture boundaries are enforced via Deptrac.

**CRITICAL RULE**: Domain layer must have NO framework dependencies.

> **For complete architectural patterns, layer rules, and directory structure, see `.claude/skills/implementing-ddd-architecture/SKILL.md`**

### Bounded Contexts (DDD)

The Core Service is divided into bounded contexts with predictable structure:

#### 1. Shared Context

Provides foundational support across the service:

- **Application Layer**: Cross-cutting concerns (Validators, Exception Normalizers, OpenAPI docs)
- **Domain Layer**: Interfaces for Infrastructure, abstract classes, common entities
- **Infrastructure Layer**: Message Buses, custom Doctrine types, retry strategies

#### 2. Core/Customer Context (Core Domain)

Comprehensive customer management functionality:

- **Application Layer**:
  - Commands: Commands for customer operations
  - Command Handlers: Process business operations
  - HTTP Request Processors & GraphQL Resolvers
  - Event Listeners & Subscribers
- **Domain Layer**:
  - Entities: Customer, CustomerType, CustomerStatus
  - Value Objects: CustomerUpdate, CustomerStatusUpdate
  - Domain Events: Customer-related events
  - Domain Exceptions: CustomerNotFoundException, CustomerTypeNotFoundException, CustomerStatusNotFoundException
  - Repository Interfaces
- **Infrastructure Layer**: Repository implementations (MongoDB)

#### 3. Internal Context

Provides internal services like health checks and monitoring.

### CQRS Implementation

- **Commands**: Encapsulate write operations implementing `CommandInterface`
- **Queries**: Handle read operations (separate from commands)
- **Handlers**: Process commands/queries with business logic implementing `CommandHandlerInterface`
- **Message Bus**: Routes commands/queries to appropriate handlers

### Event-Driven Architecture

- **Domain Events**: Published from Domain layer or handlers extending `DomainEvent`
- **Event Subscribers**: Handle events for system extensibility implementing `DomainEventSubscriberInterface`
- **Aggregates**: Use `AggregateRoot` to record and pull domain events

## Comprehensive Testing Strategy

### Testing Philosophy

- **100% Unit & Integration Test Coverage** - All code paths covered
- **0 Escaped Mutants** - Mutation testing with Infection ensures test quality (100% MSI)
- **End-to-End Coverage** - BDD scenarios cover all user journeys
- **Load Testing** - Performance validated under various load conditions

### Test Types & Commands

1. **Unit Tests** (`make unit-tests`):

   - Focus on individual classes/methods with mocked dependencies
   - 100% coverage requirement enforced
   - Test business logic in isolation
   - 2-3 minutes runtime

2. **Integration Tests** (`make integration-tests`):

   - Test interactions between components (database, external services)
   - Real MongoDB connections
   - 3-5 minutes runtime

3. **End-to-End Tests** (`make behat`):

   - BDD scenarios in Gherkin language in `/features` folder
   - Test complete user journeys from API to database
   - 5-10 minutes runtime

4. **Mutation Testing** (`make infection`):

   - Validates test quality by making code mutations
   - Must maintain 100% MSI (Mutation Score Indicator) with 0 escaped/uncovered mutants
   - Uses Infection framework for rigorous testing

5. **Load Testing** (K6-based):
   - **Smoke**: `make smoke-load-tests` (minimal load)
   - **Average**: `make average-load-tests` (normal patterns)
   - **Stress**: `make stress-load-tests` (high load)
   - **Spike**: `make spike-load-tests` (extreme spikes)

### Code Quality Standards

- **PHPInsights**: Instant quality checks, architecture analysis
- **Psalm**: Static analysis with security taint analysis (`make psalm-security`)
- **Deptrac**: Architecture dependency validation, prevents unwanted coupling
- **PHP CS Fixer**: PSR-12 compliance, auto-formatting
- **PHPMD**: PHP Mess Detector for cyclomatic complexity analysis

## Security & Performance

### Security Practices

- **Dependency Scanning**: Regular security vulnerability checks
- **Static Analysis**: Psalm security taint analysis
- **Input Validation**: Comprehensive validation on all API inputs
- **RFC 7807 Errors**: Standard problem+json error responses

### Performance Optimization

- **Load Testing**: Service validated under various load conditions
- **Database Optimization**: MongoDB with proper indexing
- **Container Efficiency**: Docker-based deployment with optimized PHP

## Validation

### Manual Testing Scenarios

#### Always run through at least one complete end-to-end scenario after making changes

1. **Customer Management Flow:**

   - Create a new customer via REST API: `POST /api/customers`
   - Retrieve customer via REST API: `GET /api/customers/{id}`
   - Update customer information
   - Verify customer status and type management

2. **GraphQL Operations:**

   - Test customer queries via GraphQL at [GraphQL Playground](https://localhost/api/graphql)
   - Test customer mutations via GraphQL
   - Verify proper error handling

#### Service health checks

- Verify [API Docs](https://localhost/api/docs) loads (API documentation)
- Verify [GraphQL Playground](https://localhost/api/graphql) loads (GraphQL playground)
- Check MongoDB connectivity and schema status

### Load Testing Scenarios

#### All load tests use the K6 framework

- **Smoke tests:** `make smoke-load-tests` -- minimal load validation
- **Average load:** `make average-load-tests` -- normal usage patterns
- **Stress tests:** `make stress-load-tests` -- high load testing
- **Spike tests:** `make spike-load-tests` -- extreme load spikes

## Common Tasks

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

PHP 8.3+, Symfony 7, API Platform 4, MongoDB, GraphQL. See project documentation for complete technical details.

---

**For detailed implementation patterns, workflows, and examples → See modular skills in `.claude/skills/` directory.**

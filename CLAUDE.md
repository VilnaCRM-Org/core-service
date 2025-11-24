# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

> **💡 Note**: For task-specific workflows (CI, testing, code review, quality standards), see modular skills in `.claude/skills/` directory. Skills are automatically discovered and activated when relevant.

## Quick Reference

For comprehensive project documentation, commands, and guidelines, see:

- **[AGENTS.md](./AGENTS.md)** - Complete repository guidelines, command reference, and development workflows
- **[.claude/skills/](./claude/skills/)** - Specialized workflow skills for CI, testing, code review, and quality standards

## Project Overview

VilnaCRM Core Service - A modern PHP microservice built with Symfony 7, API Platform 4, and MongoDB. This service follows Hexagonal Architecture (Ports & Adapters), Domain-Driven Design (DDD), and CQRS patterns.

## Essential Commands

**IMPORTANT**: Always use make commands or docker exec into the PHP container. Never run PHP commands directly on the host.

### Quick Start

- `make start` - Start all services (includes MongoDB)
- `make install` - Install PHP dependencies
- `make sh` - Access PHP container shell

### Development Essentials

- `make phpcsfixer` - Auto-fix code style (PSR-12)
- `make psalm` - Static analysis
- `make all-tests` - Run complete test suite
- `make ci` - Run all CI checks (must pass before commit)

### Testing

- `make unit-tests` - Unit tests (100% coverage required)
- `make integration-tests` - Integration tests
- `make behat` - E2E BDD tests
- `make infection` - Mutation testing (100% MSI required)

For complete command reference and detailed workflows, see [AGENTS.md](./AGENTS.md).

## Architecture Quick Reference

### Directory Structure

```
src/
├── {Context}/              # Bounded contexts (e.g., Customer)
│   ├── Domain/             # Entities, Value Objects, Repository interfaces
│   ├── Application/        # DTOs, Command Handlers, Event Subscribers, Processors
│   └── Infrastructure/     # Repository implementations (if needed)
└── Shared/                 # Shared kernel
    ├── Domain/             # Shared domain concepts
    ├── Application/        # Shared application services
    └── Infrastructure/     # Framework integrations
```

### Key Patterns

**CQRS & Event-Driven**:

- Commands: Implement `CommandInterface`
- Command Handlers: Implement `CommandHandlerInterface` (auto-tagged)
- Domain Events: Extend `DomainEvent`
- Event Subscribers: Implement `DomainEventSubscriberInterface` (auto-tagged)
- Aggregates: Extend `AggregateRoot` to record domain events

**Dependency Rules**:

- Domain layer: No external dependencies
- Application layer: Depends on Domain
- Infrastructure layer: Depends on Domain and Application

For detailed architecture guidelines, see [AGENTS.md](./AGENTS.md).

## Quality Standards

The project enforces strict quality standards:

- 100% unit test coverage
- 100% mutation testing score (MSI)
- PHPInsights score of 100
- Psalm level max with no errors
- PSR-12 code style compliance
- Deptrac architecture validation

**Before every commit**: Run `make ci` to ensure all quality checks pass.

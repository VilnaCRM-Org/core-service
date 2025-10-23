# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

> **💡 Note**: For task-specific workflows (CI, testing, code review, quality standards), see modular skills in `.claude/skills/` directory. Skills are automatically discovered and activated when relevant.

## Project Overview

VilnaCRM Core Service - A modern PHP microservice template built with Symfony 7, API Platform 4, and MongoDB. This service follows Hexagonal Architecture (Ports & Adapters), Domain-Driven Design (DDD), and CQRS patterns.

## Development Commands

### Docker Environment

```bash
make start          # Start docker containers
make stop           # Stop docker containers
make down           # Stop and remove containers
make build          # Build docker images from scratch
make sh             # Access PHP container shell
make logs           # Show all logs
make new-logs       # Show live logs
```

### Dependency Management

```bash
make install        # Install dependencies from composer.lock
make update         # Update dependencies per composer.json
```

### Code Quality & Static Analysis

```bash
make phpcsfixer     # Auto-fix PHP coding standards
make psalm          # Run Psalm static analysis
make psalm-security # Run Psalm security/taint analysis
make phpinsights    # Run PHP quality checks
make deptrac        # Validate architectural boundaries
make composer-validate  # Validate composer files
```

### Testing

```bash
make unit-tests          # Run unit tests only
make integration-tests   # Run integration tests only
make e2e-tests          # Run Behat end-to-end tests (alias: make behat)
make all-tests          # Run unit, integration, and e2e tests
make tests-with-coverage # Run tests with coverage report
make infection          # Run mutation testing

# Setup test database
make setup-test-db      # Drop and recreate test MongoDB schema
```

### Load Testing

```bash
make smoke-load-tests   # Minimal load test
make average-load-tests # Average load test
make stress-load-tests  # High load test
make spike-load-tests   # Spike/extreme load test
make load-tests         # Run all load tests

# AWS-based load testing
make aws-load-tests         # Execute load tests on AWS
make aws-load-tests-cleanup # Clean up AWS resources
```

### Symfony Commands

```bash
make cache-clear    # Clear Symfony cache
make cache-warmup   # Warmup cache
make commands       # List all Symfony console commands
```

### API Documentation

```bash
make generate-openapi-spec   # Export OpenAPI spec to .github/openapi-spec/spec.yaml
make generate-graphql-spec   # Export GraphQL spec to .github/graphql-spec/spec
```

## Architecture

### Directory Structure

```text
src/
├── Customer/              # Customer bounded context
│   └── Domain/
│       └── Entity/        # Domain entities
├── Internal/              # Internal services (e.g., HealthCheck)
└── Shared/                # Shared kernel
    ├── Application/       # Application layer (DTOs, transformers, processors, resolvers)
    ├── Domain/            # Domain layer (aggregates, entities, value objects, events, commands)
    └── Infrastructure/    # Infrastructure layer (repositories, bus implementations, Doctrine types)
```

### Layered Architecture (Hexagonal/DDD)

The codebase enforces strict architectural boundaries via Deptrac:

- **Domain Layer**: Pure business logic with no external dependencies. Contains:

  - Entities and Value Objects
  - Aggregates (extend `AggregateRoot` for domain events)
  - Domain Events and Commands (interfaces)
  - Repository interfaces
  - Domain Exceptions

- **Application Layer**: Use cases and orchestration. Contains:

  - Command Handlers (implement `CommandHandlerInterface`)
  - Event Subscribers (implement `DomainEventSubscriberInterface`)
  - DTOs and Transformers
  - API Platform Processors and Resolvers
  - GraphQL mutation inputs

- **Infrastructure Layer**: External concerns. Contains:
  - Repository implementations (MongoDB)
  - Message bus implementations (Symfony Messenger)
  - Custom Doctrine types (UlidType, DomainUuidType)
  - Retry strategies

**Dependency Rules**:

- Domain layer has NO dependencies on other layers
- Application layer depends on Domain and Infrastructure
- Infrastructure layer depends on Domain and Application

### CQRS & Event-Driven Design

- **Commands**: Write operations use commands implementing `CommandInterface`
- **Command Handlers**: Tagged with `app.command_handler`, automatically registered
- **Domain Events**: Extend `DomainEvent` from `Shared/Domain/Bus/Event`
- **Event Subscribers**: Implement `DomainEventSubscriberInterface`, tagged with `app.event_subscriber`
- **Aggregates**: Extend `AggregateRoot` to record and pull domain events

### Service Registration

Services are auto-configured in `config/services.yaml`:

- All classes in `src/` are auto-wired
- Command handlers tagged via `_instanceof` with `app.command_handler`
- Event subscribers tagged via `_instanceof` with `app.event_subscriber`
- OpenAPI endpoint factories tagged with `app.openapi_endpoint_factory`

### API Platform & MongoDB

- **Database**: MongoDB (Doctrine ODM)
- **Custom Types**: ULID and Domain UUID types defined in `Shared/Infrastructure/DoctrineType`
- **Mappings**: XML-based, located in `config/doctrine/`
- **Resource Classes**: Auto-discovered from configured directories (e.g., `src/Customer/Domain/Entity`)
- **Filters**: Custom filters defined in `services.yaml` (OrderFilter, SearchFilter, RangeFilter, DateFilter, BooleanFilter)

### API Formats

- Primary format: JSON-LD (`application/ld+json`)
- Error format: JSON Problem (`application/problem+json`)
- RFC 7807 compliant errors enabled
- GraphQL support via API Platform GraphQL

### Testing Structure

```
tests/
├── Unit/           # Unit tests (PHPUnit)
├── Integration/    # Integration tests (PHPUnit)
├── Behat/          # E2E BDD tests (Behat)
├── Load/           # k6 load testing scenarios
└── CLI/            # Bash script tests (bats)
```

- Unit tests: `PHPUNIT_TESTSUITE=Unit`
- Integration tests: `PHPUNIT_TESTSUITE=Integration`
- Behat contexts defined in `behat.yml.dist`
- Load tests use k6 in Docker

## Important Patterns

### Creating a New Entity

1. Define entity in `{Context}/Domain/Entity/`
2. Create XML mapping in `config/doctrine/{Entity}.orm.xml`
3. Add API Platform attributes/configuration to entity
4. Register resource class directory in `config/packages/api_platform.yaml`
5. Run schema updates if needed

### Adding a Command Handler

1. Create command implementing `CommandInterface` in `{Context}/Application/Command/`
2. Create handler implementing `CommandHandlerInterface` in `{Context}/Application/CommandHandler/`
3. Handler will be auto-tagged and registered

### Working with Aggregates

1. Extend `App\Shared\Domain\Aggregate\AggregateRoot`
2. Use `$this->record(new DomainEvent(...))` to record events
3. Call `pullDomainEvents()` to retrieve and clear events

### Custom API Filters

Define filters in `config/services.yaml` inheriting from API Platform's built-in filters. Tag with `api_platform.filter` and assign an ID.

## PHP Configuration

- **PHP Version**: 8.3.12 (minimum 8.2)
- **Extensions Required**: ctype, dom, iconv, mbstring, simplexml, xml
- **Memory Limit**: `-1` (unlimited) for coverage and infection testing

## CI/CD Checks

The project enforces high code quality standards:

- Psalm static analysis (with security/taint analysis)
- PHP CS Fixer (PSR-12 compliant)
- PHPInsights (100% score required)
- Deptrac architectural validation
- Security audits via `symfony security:check`
- Mutation testing with Infection

## Environment Variables

Key environment variables are defined in `.env` and `.env.test`:

- `APP_ENV`: Application environment (dev, test, prod)
- `DB_URL`: MongoDB connection string
- `AWS_SQS_*`: AWS SQS configuration for message queuing

## Additional Notes

- The Kernel is located at `src/Shared/Kernel.php`
- Preload file configured at `config/preload.php`
- API documentation available at `https://localhost/api/docs` after `make start`
- Captainhook configured for git hooks (see `captainhook.json`)
- Conventional commits enforced (see composer dependencies)

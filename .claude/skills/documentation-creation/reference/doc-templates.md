# Documentation Templates

This reference provides templates for each documentation file type. Adapt these templates to match your project specifics.

> **Note**: Templates below show the raw markdown source. Copy and adapt for your project.

## main.md - Project Overview

~~~markdown
# {Project Name}

## What Is {Project Name}?

Welcome to the **{Project Name}** GitHub page, which provides a comprehensive overview of our {domain} microservice. This documentation covers installation, architecture, API reference, and best practices for developers working with the service.

## Design Principles

Our approach to building {Project Name} is guided by these core principles:

1. **Simplicity** - Clear, understandable code that is easy to maintain
2. **Pragmatism** - Practical solutions over theoretical perfection
3. **Quality** - Comprehensive testing and strict code standards
4. **Documentation** - Every feature thoroughly documented

## Key Features

- **{Feature 1}**: {Description}
- **{Feature 2}**: {Description}
- **{Feature 3}**: {Description}

## Technology Stack

| Component  | Technology  | Version   |
| ---------- | ----------- | --------- |
| Language   | PHP         | {version} |
| Framework  | {framework} | {version} |
| Database   | {database}  | {version} |
| Web Server | {server}    | {version} |

## Quick Links

- [Getting Started](getting-started.md)
- [Design and Architecture](design-and-architecture.md)
- [API Endpoints](api-endpoints.md)
- [Developer Guide](developer-guide.md)
~~~

---

## getting-started.md - Installation Guide

~~~markdown
# Getting Started

## Prerequisites

Before you begin, ensure you have the following installed:

- **Docker**: version 20.10 or higher
- **Docker Compose**: version 2.0 or higher
- **Git**: any recent version
- **Make**: for running project commands

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/{org}/{repo}.git
cd {repo}
```

### 2. Configure Environment

```bash
cp .env.example .env
# Edit .env with your settings
```

### 3. Build and Start Services

```bash
make build
make up
```

### 4. Verify Installation

```bash
# Check service health
curl https://localhost/api/health

# Run tests
make unit-tests
```

## Next Steps

- Read the [Design and Architecture](design-and-architecture.md) guide
- Explore the [API Endpoints](api-endpoints.md)
- Check the [Developer Guide](developer-guide.md)
~~~

---

## design-and-architecture.md - Architecture Documentation

~~~markdown
# Design and Architecture

## Architectural Style

{Project Name} follows these architectural patterns:

### Hexagonal Architecture (Ports & Adapters)

The codebase is organized into layers:

- **Domain**: Pure business logic, no framework dependencies
- **Application**: Use cases, commands, queries, handlers
- **Infrastructure**: External integrations, database, APIs

### Domain-Driven Design (DDD)

Key DDD concepts implemented:

- **Entities**: Objects with unique identity ({Entity1}, {Entity2})
- **Value Objects**: Immutable objects defined by attributes
- **Aggregates**: Consistency boundaries
- **Repositories**: Data access abstraction

### CQRS (Command Query Responsibility Segregation)

- **Commands**: Write operations (Create{Entity}Command, Update{Entity}Command)
- **Queries**: Read operations (separate read models if applicable)
- **Command Bus**: Decoupled command handling

## Bounded Contexts

```
src/
├── Shared/              # Cross-cutting concerns
│   ├── Application/
│   ├── Domain/
│   └── Infrastructure/
├── Core/                # Core business domain
│   └── {Entity}/
│       ├── Application/
│       ├── Domain/
│       └── Infrastructure/
└── Internal/            # Internal services
    └── HealthCheck/
```

## Layer Dependencies

```
Domain ← Application ← Infrastructure
  ↑           ↑              ↑
  └───────────┴──────────────┘
       No reverse dependencies
```
~~~

---

## developer-guide.md - Development Workflow

~~~markdown
# Developer Guide

## Code Structure

### Directory Layout

```
src/
├── {Context}/
│   ├── Application/
│   │   ├── Command/         # Write commands
│   │   ├── CommandHandler/  # Command handlers
│   │   ├── DTO/             # Data Transfer Objects
│   │   ├── Processor/       # API processors
│   │   └── Transformer/     # DTO transformers
│   ├── Domain/
│   │   ├── Entity/          # Domain entities
│   │   ├── Repository/      # Repository interfaces
│   │   └── ValueObject/     # Value objects
│   └── Infrastructure/
│       └── Repository/      # Repository implementations
```

## Development Commands

| Command                  | Description             |
| ------------------------ | ----------------------- |
| `make build`             | Build Docker containers |
| `make up`                | Start all services      |
| `make down`              | Stop all services       |
| `make unit-tests`        | Run unit tests          |
| `make integration-tests` | Run integration tests   |
| `make behat`             | Run E2E tests (Behat)   |
| `make ci`                | Run full CI pipeline    |

## Code Quality

### Linting

```bash
make phpcsfixer  # Fix code style
make psalm       # Static analysis
make deptrac     # Architecture checks
```

### Testing

```bash
make unit-tests         # Fast unit tests
make integration-tests  # Integration tests
make behat              # End-to-end tests (Behat)
make infection          # Mutation testing
```
~~~

---

## api-endpoints.md - API Documentation

~~~markdown
# API Endpoints

## REST API

### {Entity} Endpoints

| Method | Endpoint               | Description         |
| ------ | ---------------------- | ------------------- |
| GET    | `/api/{entities}`      | List all {entities} |
| GET    | `/api/{entities}/{id}` | Get single {entity} |
| POST   | `/api/{entities}`      | Create {entity}     |
| PUT    | `/api/{entities}/{id}` | Replace {entity}    |
| PATCH  | `/api/{entities}/{id}` | Update {entity}     |
| DELETE | `/api/{entities}/{id}` | Delete {entity}     |

### Example Request

```bash
# Create {entity}
curl -X POST https://localhost/api/{entities} \
  -H "Content-Type: application/json" \
  -d '{"name": "Example", "description": "Test"}'
```

### Example Response

```json
{
  "@context": "/api/contexts/{Entity}",
  "@id": "/api/{entities}/123",
  "@type": "{Entity}",
  "name": "Example",
  "description": "Test"
}
```

## GraphQL API

### Endpoint

```
POST /api/graphql
```

### Query Example

```graphql
query {
  {entity}(id: "/api/{entities}/123") {
    id
    name
    description
  }
}
```

### Mutation Example

```graphql
mutation {
  create{Entity}(input: {
    name: "Example"
    description: "Test"
  }) {
    {entity} {
      id
      name
    }
  }
}
```
~~~

---

## testing.md - Testing Documentation

~~~markdown
# Testing

## Test Types

### Unit Tests

Fast, isolated tests for individual components.

```bash
make unit-tests
```

Location: `tests/Unit/`

### Integration Tests

Tests that verify component integration.

```bash
make integration-tests
```

Location: `tests/Integration/`

### E2E Tests (Behat)

Full API endpoint testing with Behat.

```bash
make behat
```

Location: `tests/Behat/`

### Mutation Tests

Verify test quality with Infection.

```bash
make infection
```

### Load Tests

Performance testing with K6.

```bash
make load-tests
make smoke-load-tests
make stress-load-tests
```

Location: `tests/Load/`

## Coverage Requirements

| Metric         | Minimum |
| -------------- | ------- |
| Line Coverage  | 100%    |
| Mutation Score | 100%    |
~~~

---

## Additional Templates

For additional documentation types (security.md, performance.md, onboarding.md, etc.), follow the same pattern:

1. Clear heading structure with H1 for title, H2 for major sections
2. Code examples with appropriate language tags
3. Tables for structured data
4. Cross-references to related docs using relative links
5. Practical commands users can copy and run

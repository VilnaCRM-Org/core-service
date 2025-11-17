---
name: api-platform-crud
description: Create complete REST API CRUD operations using API Platform 4 with DDD and CQRS patterns. Use when adding new API resources, implementing CRUD endpoints, creating DTOs, configuring operations, or setting up state processors. Follows the repository's hexagonal architecture with YAML resource configuration and command bus pattern.
---

# API Platform CRUD Skill

**Create production-ready REST API CRUD operations following DDD, CQRS, and hexagonal architecture patterns.**

This skill guides you through implementing complete CRUD (Create, Read, Update, Delete) operations using API Platform 4 with the repository's established patterns.

## When to Use This Skill

Activate automatically when:

- Creating new API resources (entities with REST endpoints)
- Implementing CRUD operations for existing entities
- Adding custom operations (beyond standard CRUD)
- Creating DTOs for input/output transformation
- Configuring filters, pagination, or serialization
- Working with state processors or providers
- Setting up GraphQL alongside REST

## Quick Start: Complete CRUD in 10 Steps

> **Template Syntax Note**: Throughout this guide, placeholders like `{Entity}`, `{Context}`, and `{entity}` should be replaced with your actual values. For example:
> - `{Entity}` → `Customer` (PascalCase class name)
> - `{Context}` → `Customer` (bounded context/module name)
> - `{entity}` → `customer` (lowercase for configs/filters)
> - `{entities}` → `customers` (plural for collection names)
>
> See `examples/complete-customer-crud.md` for a fully realized implementation using `Customer` entity.

### Step 1: Create Domain Entity

```php
// src/Core/{Context}/Domain/Entity/{Entity}.php
namespace App\Core\{Context}\Domain\Entity;

final class {Entity}
{
    private Ulid $ulid;
    private string $name;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        Ulid $ulid,
        string $name
    ) {
        $this->ulid = $ulid;
        $this->name = $name;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function update({Entity}Update $update): void
    {
        $this->name = $update->getName();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters only, no setters (immutability)
    public function getUlid(): Ulid { return $this->ulid; }
    public function getName(): string { return $this->name; }
    // ...
}
```

**NO Doctrine annotations, NO Symfony imports, NO API Platform attributes.**

### Step 2: Create Doctrine XML Mapping

```xml
<!-- config/doctrine/{Entity}.mongodb.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                         xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                         http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
    <document name="App\Core\{Context}\Domain\Entity\{Entity}" collection="{entities}">
        <field name="ulid" id="true" type="ulid" strategy="NONE"/>
        <field name="name" type="string"/>
        <field name="createdAt" type="date_immutable"/>
        <field name="updatedAt" type="date_immutable"/>
    </document>
</doctrine-mongo-mapping>
```

### Step 3: Create Input DTOs

```php
// src/Core/{Context}/Application/DTO/{Entity}Create.php
namespace App\Core\{Context}\Application\DTO;

final readonly class {Entity}Create
{
    public function __construct(
        public ?string $name = null,
    ) {
    }
}

// {Entity}Put.php - Same as Create (full replacement)
// {Entity}Patch.php - All nullable (partial update)
```

### Step 4: Configure Validation

```yaml
# config/validator/{Entity}.yaml
App\Core\{Context}\Application\DTO\{Entity}Create:
  properties:
    name:
      - NotBlank: ~
      - Length:
          max: 255
```

### Step 5: Create API Platform Resource Configuration

```yaml
# config/api_platform/resources/{entity}.yaml
App\Core\{Context}\Domain\Entity\{Entity}:
  shortName: '{Entity}'
  normalizationContext:
    groups: ['output']
  paginationPartial: true
  paginationViaCursor:
    - { field: 'ulid', direction: 'desc' }
  order: { 'ulid': 'desc' }

  exceptionToStatus:
    'App\Core\{Context}\Domain\Exception\{Entity}NotFoundException': 404

  operations:
    # READ Operations
    ApiPlatform\Metadata\GetCollection:
      description: 'Retrieves the collection of {Entity} resources'
      filters:
        - {entity}.mongodb.search
        - {entity}.mongodb.order

    ApiPlatform\Metadata\Get:
      description: 'Retrieves a {Entity} resource by its unique identifier'

    # CREATE Operation
    ApiPlatform\Metadata\Post:
      description: 'Creates a {Entity} resource'
      input: App\Core\{Context}\Application\DTO\{Entity}Create
      processor: App\Core\{Context}\Application\Processor\Create{Entity}Processor
      denormalizationContext:
        allow_extra_attributes: false

    # UPDATE Operations
    ApiPlatform\Metadata\Put:
      description: 'Replaces the {Entity} resource'
      input: App\Core\{Context}\Application\DTO\{Entity}Put
      processor: App\Core\{Context}\Application\Processor\{Entity}PutProcessor

    ApiPlatform\Metadata\Patch:
      description: 'Updates the {Entity} resource partially'
      input: App\Core\{Context}\Application\DTO\{Entity}Patch
      processor: App\Core\{Context}\Application\Processor\{Entity}PatchProcessor

    # DELETE Operation
    ApiPlatform\Metadata\Delete:
      description: 'Removes the {Entity} resource'
```

### Step 6: Configure Serialization Groups

```yaml
# config/serialization/{Entity}.yaml
App\Core\{Context}\Domain\Entity\{Entity}:
  attributes:
    ulid:
      groups: ['output']
    name:
      groups: ['output', 'write:{entity}']
    createdAt:
      groups: ['output']
    updatedAt:
      groups: ['output']
```

### Step 7: Create State Processors

```php
// src/Core/{Context}/Application/Processor/Create{Entity}Processor.php
namespace App\Core\{Context}\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Core\{Context}\Application\Command\Create{Entity}Command;
use App\Core\{Context}\Application\DTO\{Entity}Create;
use App\Core\{Context}\Application\Transformer\{Entity}Transformer;
use App\Core\{Context}\Domain\Entity\{Entity};
use App\Shared\Domain\Bus\Command\CommandBusInterface;

/**
 * @implements ProcessorInterface<{Entity}Create, {Entity}>
 */
final readonly class Create{Entity}Processor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private {Entity}Transformer $transformer,
    ) {
    }

    /**
     * @param {Entity}Create $data
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): {Entity} {
        $entity = $this->transformer->transformFromCreate($data);
        $this->commandBus->dispatch(new Create{Entity}Command($entity));
        return $entity;
    }
}
```

### Step 8: Create Command and Handler

```php
// src/Core/{Context}/Application/Command/Create{Entity}Command.php
namespace App\Core\{Context}\Application\Command;

use App\Core\{Context}\Domain\Entity\{Entity};
use App\Shared\Domain\Bus\Command\CommandInterface;

final readonly class Create{Entity}Command implements CommandInterface
{
    public function __construct(
        private {Entity} $entity,
    ) {
    }

    public function getEntity(): {Entity}
    {
        return $this->entity;
    }
}

// src/Core/{Context}/Application/CommandHandler/Create{Entity}CommandHandler.php
namespace App\Core\{Context}\Application\CommandHandler;

use App\Core\{Context}\Application\Command\Create{Entity}Command;
use App\Core\{Context}\Domain\Repository\{Entity}RepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;

final readonly class Create{Entity}CommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private {Entity}RepositoryInterface $repository,
    ) {
    }

    public function __invoke(Create{Entity}Command $command): void
    {
        $this->repository->save($command->getEntity());
    }
}
```

### Step 9: Create Repository

```php
// src/Core/{Context}/Domain/Repository/{Entity}RepositoryInterface.php
namespace App\Core\{Context}\Domain\Repository;

use App\Core\{Context}\Domain\Entity\{Entity};

interface {Entity}RepositoryInterface
{
    public function save({Entity} $entity): void;
    public function delete({Entity} $entity): void;
    public function find(string $ulid): ?{Entity};
}

// src/Core/{Context}/Infrastructure/Repository/Mongo{Entity}Repository.php
namespace App\Core\{Context}\Infrastructure\Repository;

use App\Core\{Context}\Domain\Entity\{Entity};
use App\Core\{Context}\Domain\Repository\{Entity}RepositoryInterface;

final class Mongo{Entity}Repository extends BaseRepository implements {Entity}RepositoryInterface
{
    // Inherits save(), delete(), find() from BaseRepository
}
```

### Step 10: Configure Filters (Optional)

```yaml
# config/services.yaml
services:
  app.{entity}.mongodb.order_filter:
    parent: 'api_platform.doctrine_mongodb.odm.order_filter'
    arguments:
      - ulid: 'desc'
        name: 'asc'
        createdAt: 'desc'
    tags:
      - { name: 'api_platform.filter', id: '{entity}.mongodb.order' }

  app.{entity}.mongodb.search_filter:
    parent: 'api_platform.doctrine_mongodb.odm.search_filter'
    arguments:
      - name: 'exact'
    tags:
      - { name: 'api_platform.filter', id: '{entity}.mongodb.search' }
```

## Core Architecture Pattern

```
REST Request → API Platform → Processor → DTO → Transformer → Entity → Command → Handler → Repository → MongoDB
```

**Layer Responsibilities:**

- **API Platform Config** (YAML): Defines operations, input/output, routing
- **Processors** (Application): Orchestrate request handling, dispatch commands
- **DTOs** (Application): Decouple API input from domain model
- **Transformers** (Application): Convert DTOs to domain entities
- **Commands** (Application): Encapsulate write operation intent
- **Handlers** (Application): Execute business logic, call repositories
- **Entities** (Domain): Pure business logic, no framework dependencies
- **Repositories** (Infrastructure): Persist to database

## Resource Configuration Patterns

### YAML vs PHP Attributes

This repository uses **YAML-based configuration** (not PHP attributes) to maintain clean separation:

```yaml
# config/api_platform/resources/{entity}.yaml
App\Entity\{Entity}:
  operations:
    ApiPlatform\Metadata\Get: ~
```

**Benefits:**

- Domain entities remain framework-agnostic
- API configuration is centralized and versionable
- Supports DDD/hexagonal architecture

### Operation Types

| Operation     | HTTP Method | Purpose          | Input DTO      | Processor Required    |
| ------------- | ----------- | ---------------- | -------------- | --------------------- |
| GetCollection | GET         | List resources   | None           | No (default provider) |
| Get           | GET         | Single resource  | None           | No (default provider) |
| Post          | POST        | Create resource  | {Entity}Create | Yes                   |
| Put           | PUT         | Full replacement | {Entity}Put    | Yes                   |
| Patch         | PATCH       | Partial update   | {Entity}Patch  | Yes                   |
| Delete        | DELETE      | Remove resource  | None           | No (default)          |

### Pagination Configuration

```yaml
paginationPartial: true
paginationViaCursor:
  - { field: 'ulid', direction: 'desc' }
order: { 'ulid': 'desc' }
```

### IRI Resolution in Processors

When DTOs contain references to other entities (e.g., type, status):

```php
use ApiPlatform\Metadata\IriConverterInterface;

final readonly class CreateCustomerProcessor implements ProcessorInterface
{
    public function __construct(
        private IriConverterInterface $iriConverter,
    ) {}

    public function process(mixed $data, ...): Customer
    {
        // Convert IRI string to entity
        $type = $this->iriConverter->getResourceFromIri($data->type);
        // ...
    }
}
```

## DTO Patterns

### Create DTO (POST)

```php
final readonly class CustomerCreate
{
    public function __construct(
        public ?string $initials = null,
        public ?string $email = null,
        public ?string $type = null,  // IRI string: "/api/customer_types/{ulid}"
    ) {}
}
```

### Put DTO (Full Update)

Same structure as Create - all required fields for full replacement.

### Patch DTO (Partial Update)

```php
final readonly class CustomerPatch
{
    public function __construct(
        public ?string $initials = null,  // All nullable
        public ?string $email = null,
        public ?string $type = null,
    ) {}
}
```

**Key difference**: In Patch processor, use conditional logic to preserve existing values:

```php
$newName = $data->name ?? $existingEntity->getName();
```

## Validation Strategy

### External YAML Configuration

```yaml
# config/validator/{Entity}.yaml
App\Core\{Context}\Application\DTO\{Entity}Create:
  properties:
    name:
      - NotBlank: ~
      - Length: { max: 255 }
    email:
      - Email: ~
      - App\Shared\Application\Validator\UniqueEmail: ~
```

### Custom Validators

```php
// src/Shared/Application/Validator/UniqueEmail.php
// src/Shared/Application/Validator/UniqueEmailValidator.php
```

## Exception Handling

```yaml
# In resource configuration
exceptionToStatus:
  'App\Core\Customer\Domain\Exception\CustomerNotFoundException': 404
  'App\Core\Customer\Domain\Exception\CustomerTypeNotFoundException': 400
```

Domain exceptions automatically map to HTTP status codes.

## Checklist: New CRUD Resource

- [ ] Create Domain Entity (pure PHP, no framework imports)
- [ ] Create Doctrine XML mapping in `config/doctrine/`
- [ ] Create Repository Interface in Domain layer
- [ ] Create Repository Implementation in Infrastructure layer
- [ ] Create Input DTOs (Create, Put, Patch) in Application layer
- [ ] Configure validation in `config/validator/`
- [ ] Create API Platform resource YAML in `config/api_platform/resources/`
- [ ] Configure serialization groups in `config/serialization/`
- [ ] Create State Processors for POST, PUT, PATCH
- [ ] Create Transformer for DTO → Entity conversion
- [ ] Create Commands for write operations
- [ ] Create Command Handlers
- [ ] Create Domain Exceptions
- [ ] Configure Filters in `config/services.yaml`
- [ ] Add resource directory to `api_platform.yaml` if new context
- [ ] Run `make ci` to verify all quality checks pass

## Related Skills

- **[implementing-ddd-architecture](../implementing-ddd-architecture/SKILL.md)** - DDD patterns and layer responsibilities
- **[deptrac-fixer](../deptrac-fixer/SKILL.md)** - Fix architectural violations
- **[database-migrations](../database-migrations/SKILL.md)** - MongoDB entity management
- **[developing-openapi-specs](../developing-openapi-specs/SKILL.md)** - OpenAPI specification patterns

## Quick Commands

```bash
# Verify API configuration
make cache-clear

# Generate OpenAPI spec
make generate-openapi-spec

# Test endpoints
make behat

# Validate architecture
make deptrac

# Full CI check
make ci
```

## Success Criteria

- API Platform resource configuration valid (no syntax errors)
- All CRUD operations functional (POST, GET, PUT, PATCH, DELETE)
- DTOs properly validated
- Serialization groups correctly applied
- Domain entities remain framework-agnostic
- Command bus pattern used for write operations
- All tests pass with 100% coverage
- `make ci` outputs "✅ CI checks successfully passed!"

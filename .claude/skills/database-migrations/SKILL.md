---
name: database-migrations
description: Create, manage, and apply database migrations using Doctrine ODM for MongoDB. Use when modifying entities, adding fields, managing database schema changes, creating repositories, or troubleshooting database issues.
#
# FOR OPENAI/GPT/CODEX AGENTS: Read this file and supporting guides (entity-creation-guide.md, repository-patterns.md).
# FOR CLAUDE CODE: This skill is automatically invoked when relevant.
#
---

# Database Migrations Skill

## Overview

This skill guides you through creating and managing database migrations for MongoDB using Doctrine ODM in a Hexagonal Architecture context.

## Core Principles

### 1. Domain-Driven Design

- Entities belong in **Domain layer** (`{Context}/Domain/Entity/`)
- Repository interfaces in **Domain layer** (`{Context}/Domain/Repository/`)
- Repository implementations in **Infrastructure layer** (`{Context}/Infrastructure/Repository/`)
- XML mappings are infrastructure concern (`config/doctrine/`)

**Related Skill**: [implementing-ddd-architecture](../implementing-ddd-architecture/SKILL.md) - For comprehensive DDD patterns, layer responsibilities, and Deptrac compliance

### 2. MongoDB Schema Management

- Use XML mappings for all entity metadata (not annotations)
- Define indexes in XML for performance
- Use custom types (ULID, DomainUuid) for identifiers
- Schema updates are applied via Doctrine commands

### 3. API Platform Integration

- Configure resources via YAML (`config/api_platform/resources/`)
- Register resource directories in `api_platform.yaml`
- Use DTOs and Processors for API operations

## Available Commands

```bash
# Schema Management
make doctrine-migrations-migrate        # Apply pending migrations
make doctrine-migrations-generate       # Create empty migration file
make setup-test-db                      # Drop and recreate test database

# Schema Operations
docker compose exec php bin/console doctrine:mongodb:schema:update       # Update schema
docker compose exec php bin/console doctrine:mongodb:schema:validate     # Validate schema
```

## Quick Start

### Creating a New Entity

**1. Define Entity** (Domain Layer):

```php
namespace App\Core\Customer\Domain\Entity;

final class Customer
{
    public function __construct(
        private string $id,
        private string $name,
        private string $email,
        private \DateTimeImmutable $createdAt
    ) {}

    // Getters...
}
```

**2. Create XML Mapping** (`config/doctrine/Customer.mongodb.xml`):

```xml
<document name="App\Core\Customer\Domain\Entity\Customer" collection="customers">
    <field name="id" fieldName="id" id="true" type="ulid"/>
    <field name="name" type="string"/>
    <field name="email" type="string"/>
    <field name="createdAt" fieldName="created_at" type="date_immutable"/>

    <indexes>
        <index><key name="email" order="asc"/><option name="unique" value="true"/></index>
    </indexes>
</document>
```

**3. Configure API Platform** (`config/api_platform/resources/customer.yaml`):

```yaml
App\Core\Customer\Domain\Entity\Customer:
  shortName: Customer
  operations:
    get_collection: ~
    get: ~
    post: ~
```

**4. Update Schema**:

```bash
make cache-clear
docker compose exec php bin/console doctrine:mongodb:schema:update
```

**See detailed guides**: [entity-creation-guide.md](entity-creation-guide.md)

### Modifying Existing Entities

**1. Update Entity Class** (add/modify fields)
**2. Update XML Mapping** (add field definitions)
**3. Clear Cache**: `make cache-clear`
**4. Update Schema**: `docker compose exec php bin/console doctrine:mongodb:schema:update`

**See detailed guides**: [entity-modification-guide.md](entity-modification-guide.md)

### Creating Repositories

**1. Define Interface** (Domain):

```php
interface CustomerRepositoryInterface
{
    public function save(Customer $customer): void;
    public function findById(string $id): ?Customer;
}
```

**2. Implement** (Infrastructure):

```php
final class CustomerRepository implements CustomerRepositoryInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager
    ) {}

    public function save(Customer $customer): void
    {
        $this->documentManager->persist($customer);
        $this->documentManager->flush();
    }
}
```

**3. Register in `services.yaml`**:

```yaml
App\Core\Customer\Domain\Repository\CustomerRepositoryInterface:
  alias: App\Core\Customer\Infrastructure\Repository\CustomerRepository
```

**See detailed patterns**: [repository-patterns.md](repository-patterns.md)

## MongoDB-Specific Features

### Custom Types

**ULID** (`type="ulid"`):

- Sortable, time-ordered identifiers
- Used for MongoDB \_id fields
- Auto-generated, globally unique

**DomainUuid** (`type="domain_uuid"`):

- Standard UUID format
- Used for domain identifiers
- RFC 4122 compliant

### Indexes

Define for performance and constraints:

```xml
<indexes>
    <index><key name="email" order="asc"/><option name="unique" value="true"/></index>
    <index><key name="createdAt" order="desc"/></index>
    <index><key name="status"/><key name="type"/></index>  <!-- Compound -->
</indexes>
```

### Embedded Documents

For value objects:

```xml
<embed-one field="address" target-document="App\Core\Customer\Domain\ValueObject\Address"/>
<embed-many field="tags" target-document="App\Core\Customer\Domain\ValueObject\Tag"/>
```

**See detailed guide**: [mongodb-specifics.md](mongodb-specifics.md)

## Migration Best Practices

### 1. Clean Up Empty Migrations

**MANDATORY**: Delete empty migrations immediately.

```php
// ❌ DELETE: No actual changes
public function up(Schema $schema): void { }
public function down(Schema $schema): void { }
```

### 2. Test Before Committing

1. Apply migration on dev database
2. Verify schema with `doctrine:mongodb:schema:validate`
3. Run all tests
4. Test rollback if applicable

### 3. Production Safety

```bash
# Always backup before migration
# Apply migration
make doctrine-migrations-migrate
# Verify application works
# Keep backup for potential rollback
```

## Testing with Database

### Setup Test Database

```bash
make setup-test-db  # Before integration/E2E tests
```

### Integration Test Pattern

```php
final class CustomerRepositoryTest extends IntegrationTestCase
{
    private CustomerRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getContainer()->get(CustomerRepositoryInterface::class);
    }

    public function testSaveAndRetrieveCustomer(): void
    {
        $customer = new Customer(/* unique test data */);
        $this->repository->save($customer);

        $retrieved = $this->repository->findById($customer->getId());
        $this->assertNotNull($retrieved);
    }
}
```

**Important**: Always use Faker for unique test data (emails, names, etc.)

## Troubleshooting

### Common Issues

**Database Connection Errors**:

```bash
docker compose ps mongodb
docker compose logs mongodb
```

**Schema Sync Issues**:

```bash
docker compose exec php bin/console doctrine:mongodb:schema:validate
docker compose exec php bin/console doctrine:mongodb:schema:update --force
```

**Migration Conflicts**:

```bash
docker compose exec php bin/console doctrine:migrations:status
docker compose exec php bin/console doctrine:migrations:migrate prev  # Rollback
```

**See comprehensive guide**: [reference/troubleshooting.md](reference/troubleshooting.md)

## Supporting Files

For detailed patterns, examples, and reference documentation:

- **[entity-creation-guide.md](entity-creation-guide.md)** - Complete entity creation workflow
- **[entity-modification-guide.md](entity-modification-guide.md)** - Modifying existing entities
- **[repository-patterns.md](repository-patterns.md)** - Repository implementation patterns
- **[mongodb-specifics.md](mongodb-specifics.md)** - MongoDB features and patterns
- **[reference/troubleshooting.md](reference/troubleshooting.md)** - Common issues and solutions
- **[examples/](examples/)** - Complete working examples

## Success Criteria

- Entity defined in Domain layer
- XML mapping created and valid
- API Platform resource configured
- Repository implemented following hexagonal architecture
- Schema validated successfully
- All tests pass
- Documentation updated in `docs/design-and-architecture.md`

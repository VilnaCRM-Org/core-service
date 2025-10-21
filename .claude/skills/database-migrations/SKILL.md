---
name: Database Migrations
description: Create, manage, and apply database migrations using Doctrine ODM for MongoDB. Use when modifying entities, adding fields, or managing database schema changes.
---

# Database Migrations Skill

This skill guides you through creating and managing database migrations for MongoDB using Doctrine ODM.

## When to Use This Skill

Activate this skill when:
- Adding new entities
- Modifying existing entity fields
- Changing database schema
- Setting up test database
- Deploying schema changes

## Migration Commands

### Apply Migrations
```bash
make doctrine-migrations-migrate
```

**Purpose**: Apply pending migrations to database
**Runtime**: 1-2 minutes

### Generate New Migration
```bash
make doctrine-migrations-generate
```

**Purpose**: Create a new empty migration file
**Location**: `migrations/`

### Setup Test Database
```bash
make setup-test-db
```

**Purpose**: Drop and recreate test MongoDB schema
**When to use**:
- Before running integration/E2E tests
- After schema changes
- When tests fail due to database state

## Creating a New Entity

### Step 1: Define Entity in Domain Layer

Create entity in `{Context}/Domain/Entity/`:

```php
<?php

declare(strict_types=1);

namespace App\Customer\Domain\Entity;

final class Customer
{
    private string $id;
    private string $name;
    private string $email;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        string $id,
        string $name,
        string $email
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->email = $email;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
```

### Step 2: Create XML Mapping

Create XML mapping in `config/doctrine/{Entity}.mongodb.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        https://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">

    <document name="App\Customer\Domain\Entity\Customer" collection="customers">
        <field name="id" fieldName="id" id="true" strategy="NONE" type="string"/>
        <field name="name" fieldName="name" type="string"/>
        <field name="email" fieldName="email" type="string"/>
        <field name="createdAt" fieldName="created_at" type="date_immutable"/>

        <indexes>
            <index>
                <key name="email" order="asc"/>
                <option name="unique" value="true"/>
            </index>
        </indexes>
    </document>
</doctrine-mongo-mapping>
```

### Step 3: Configure API Platform Resource

Add to `config/api_platform/resources/{resource}.yaml`:

```yaml
App\Customer\Domain\Entity\Customer:
  shortName: Customer
  description: 'Customer resource'
  operations:
    get_collection:
      class: 'ApiPlatform\Metadata\GetCollection'
      uriTemplate: '/customers'
      paginationEnabled: true
    get:
      class: 'ApiPlatform\Metadata\Get'
      uriTemplate: '/customers/{id}'
    post:
      class: 'ApiPlatform\Metadata\Post'
      uriTemplate: '/customers'
      input: 'App\Customer\Application\DTO\CreateCustomerDto'
      processor: 'App\Customer\Application\Processor\CreateCustomerProcessor'
```

### Step 4: Register Resource Directory

Update `config/packages/api_platform.yaml`:

```yaml
api_platform:
    mapping:
        paths:
            - '%kernel.project_dir%/config/api_platform'
            - '%kernel.project_dir%/src/Customer/Domain/Entity'
    # ... rest of config
```

### Step 5: Generate and Apply Migration

```bash
# Generate migration (if using migrations)
make doctrine-migrations-generate

# Or directly sync schema for MongoDB
docker compose exec php bin/console doctrine:mongodb:schema:update
```

## Modifying Existing Entities

### Step 1: Update Entity Class

Add or modify fields in entity:

```php
final class Customer
{
    // ... existing fields

    private ?string $phone = null; // New field

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }
}
```

### Step 2: Update XML Mapping

Add field mapping:

```xml
<field name="phone" fieldName="phone" type="string" nullable="true"/>
```

### Step 3: Update API Platform Configuration

Add field to serialization groups if needed:

```yaml
normalizationContext:
    groups: ['customer:read']
denormalizationContext:
    groups: ['customer:write']
```

### Step 4: Clear Cache

```bash
make cache-clear
```

### Step 5: Update Database Schema

```bash
docker compose exec php bin/console doctrine:mongodb:schema:update
```

## MongoDB-Specific Considerations

### Custom Types

The project uses custom Doctrine types:

**ULID Type** (`Shared/Infrastructure/DoctrineType/UlidType.php`):
- Used for MongoDB document IDs
- Generates sortable, unique identifiers

**Domain UUID Type** (`Shared/Infrastructure/DoctrineType/DomainUuidType.php`):
- Used for domain entity identifiers
- Maintains UUID format

Usage in XML mapping:

```xml
<field name="id" fieldName="id" id="true" strategy="NONE" type="ulid"/>
```

### Indexes

Define indexes in XML mapping for performance:

```xml
<indexes>
    <index>
        <key name="email" order="asc"/>
        <option name="unique" value="true"/>
    </index>
    <index>
        <key name="createdAt" order="desc"/>
    </index>
</indexes>
```

### Embedded Documents

For value objects or embedded documents:

```xml
<embed-one field="address" target-document="App\Customer\Domain\ValueObject\Address">
    <discriminator-field name="type"/>
</embed-one>
```

## Migration Best Practices

### Clean Up Empty Migrations

**MANDATORY**: Delete empty migrations immediately if they contain no schema changes.

Check migration content before committing:

```php
// ❌ DELETE: Empty migration with only boilerplate
public function up(Schema $schema): void
{
    // No actual schema modifications
}

public function down(Schema $schema): void
{
    // No actual schema modifications
}
```

### Test Migrations

Before committing:

1. **Apply migration** on development database
2. **Verify schema** matches expectations
3. **Run tests** to ensure no breakage
4. **Test rollback** (down migration) if applicable

### Migration Safety

For production migrations:

```bash
# 1. Backup database first
# 2. Apply migration
make doctrine-migrations-migrate

# 3. Verify application works
# 4. Keep backup for rollback if needed
```

## Repository Implementation

### Step 1: Define Repository Interface (Domain Layer)

```php
<?php

namespace App\Customer\Domain\Repository;

use App\Customer\Domain\Entity\Customer;

interface CustomerRepositoryInterface
{
    public function save(Customer $customer): void;
    public function findById(string $id): ?Customer;
    public function findByEmail(string $email): ?Customer;
}
```

### Step 2: Implement Repository (Infrastructure Layer)

```php
<?php

namespace App\Customer\Infrastructure\Repository;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

final class CustomerRepository implements CustomerRepositoryInterface
{
    private DocumentRepository $repository;

    public function __construct(
        private readonly DocumentManager $documentManager
    ) {
        $this->repository = $documentManager->getRepository(Customer::class);
    }

    public function save(Customer $customer): void
    {
        $this->documentManager->persist($customer);
        $this->documentManager->flush();
    }

    public function findById(string $id): ?Customer
    {
        return $this->repository->find($id);
    }

    public function findByEmail(string $email): ?Customer
    {
        return $this->repository->findOneBy(['email' => $email]);
    }
}
```

### Step 3: Register Repository (services.yaml)

```yaml
services:
    App\Customer\Infrastructure\Repository\CustomerRepository:
        arguments:
            $documentManager: '@doctrine_mongodb.odm.document_manager'

    App\Customer\Domain\Repository\CustomerRepositoryInterface:
        alias: App\Customer\Infrastructure\Repository\CustomerRepository
```

## Testing with Database

### Setup Test Database Before Tests

```bash
make setup-test-db
```

### Integration Test Example

```php
<?php

namespace App\Tests\Integration\Customer;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Tests\Integration\IntegrationTestCase;

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
        $customer = new Customer(
            id: $this->faker->uuid(),
            name: $this->faker->name(),
            email: $this->faker->unique()->email()
        );

        $this->repository->save($customer);

        $retrieved = $this->repository->findById($customer->getId());

        $this->assertNotNull($retrieved);
        $this->assertEquals($customer->getName(), $retrieved->getName());
    }
}
```

## Troubleshooting

### Database Connection Errors

```bash
# Check database container is running
docker compose ps database

# Check logs
docker compose logs database

# Restart database
make down && make start
```

### Schema Sync Issues

```bash
# Force schema update
docker compose exec php bin/console doctrine:mongodb:schema:update --force

# Validate schema
docker compose exec php bin/console doctrine:mongodb:schema:validate
```

### Migration Conflicts

```bash
# Check migration status
docker compose exec php bin/console doctrine:migrations:status

# Skip problematic migration (if safe)
docker compose exec php bin/console doctrine:migrations:version --add VERSION

# Rollback last migration
docker compose exec php bin/console doctrine:migrations:migrate prev
```

## Success Criteria

- Entity properly defined in Domain layer
- XML mapping created and valid
- API Platform resource configured
- Repository interface and implementation created
- Migration generated and applied successfully
- Tests pass with new schema
- Documentation updated in `docs/design-and-architecture.md`

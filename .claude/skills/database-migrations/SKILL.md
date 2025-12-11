---
name: database-migrations
description: Create, manage, and apply database migrations using Doctrine ODM for MongoDB. Use when modifying entities, adding fields, managing database schema changes, creating repositories, or troubleshooting database issues.
---

# Database Migrations Skill

## Context (Input)

- New entity needs database persistence
- Existing entity requires schema changes (add/modify/remove fields)
- MongoDB repository needs implementation
- Database schema validation fails
- Need to set up indexes for performance

## Task (Function)

Create entities with XML mapping and repositories following hexagonal architecture and MongoDB best practices.

**Success Criteria**: `make setup-test-db` runs without errors, schema validates, all tests pass.

---

## Core Principles

### Domain-Driven Design

- **Entities**: Domain layer (`{Context}/Domain/Entity/`)
- **Repository Interfaces**: Domain layer (`{Context}/Domain/Repository/`)
- **Repository Implementations**: Infrastructure layer (`{Context}/Infrastructure/Repository/`)
- **XML Mappings**: Infrastructure concern (`config/doctrine/`)

**See**: [implementing-ddd-architecture](../implementing-ddd-architecture/SKILL.md) for DDD patterns.

### MongoDB with Doctrine ODM

- Use **XML mappings** for all entity metadata (not annotations/attributes)
- Define indexes in XML for performance
- Use custom types (ULID, DomainUuid) for identifiers
- Schema updates applied via Doctrine commands

---

## Quick Start

### Creating a New Entity

#### Step 1: Create Entity (Domain Layer)

```php
// src/Core/{Context}/Domain/Entity/{Entity}.php
namespace App\Core\{Context}\Domain\Entity;

final class Customer
{
    public function __construct(
        private string $id,
        private string $name,
        private string $email,
        private \DateTimeImmutable $createdAt
    ) {}

    // Getters only - no setters (immutability)
}
```

#### Step 2: Create XML Mapping

```xml
<!-- config/doctrine/Customer.mongodb.xml -->
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

#### Step 3: Configure API Platform

```yaml
# config/api_platform/resources/customer.yaml
App\Core\Customer\Domain\Entity\Customer:
  shortName: Customer
  operations:
    get_collection: ~
    get: ~
    post: ~
```

#### Step 4: Update Schema

```bash
make cache-clear
docker compose exec php bin/console doctrine:mongodb:schema:update
```

**See**: [entity-creation-guide.md](entity-creation-guide.md) for complete workflow.

---

### Modifying Existing Entities

1. **Update Entity Class** (add/modify fields)
2. **Update XML Mapping** (add field definitions)
3. **Clear Cache**: `make cache-clear`
4. **Update Schema**: `docker compose exec php bin/console doctrine:mongodb:schema:update`

**See**: [entity-modification-guide.md](entity-modification-guide.md)

---

### Creating Repositories

#### Step 1: Define Interface (Domain)

```php
// Domain/Repository/CustomerRepositoryInterface.php
interface CustomerRepositoryInterface
{
    public function save(Customer $customer): void;
    public function findById(string $id): ?Customer;
}
```

#### Step 2: Implement (Infrastructure)

```php
// Infrastructure/Repository/CustomerRepository.php
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

**Step 3: Register in `services.yaml`**

```yaml
App\Core\Customer\Domain\Repository\CustomerRepositoryInterface:
  alias: App\Core\Customer\Infrastructure\Repository\CustomerRepository
```

**See**: [repository-patterns.md](repository-patterns.md)

---

## MongoDB-Specific Features

### Custom Types

| Type          | Usage               | Purpose                            |
| ------------- | ------------------- | ---------------------------------- |
| `ulid`        | MongoDB `_id` field | Sortable, time-ordered identifiers |
| `domain_uuid` | Domain identifiers  | Standard UUID format (RFC 4122)    |

```xml
<field name="ulid" id="true" type="ulid"/>
<field name="customerId" type="domain_uuid"/>
```

### Indexes

```xml
<indexes>
    <!-- Unique index -->
    <index><key name="email" order="asc"/><option name="unique" value="true"/></index>

    <!-- Performance index -->
    <index><key name="createdAt" order="desc"/></index>

    <!-- Compound index -->
    <index><key name="status"/><key name="type"/></index>
</indexes>
```

### Embedded Documents

For Value Objects:

```xml
<embed-one field="address" target-document="App\Core\Customer\Domain\ValueObject\Address"/>
<embed-many field="tags" target-document="App\Core\Customer\Domain\ValueObject\Tag"/>
```

**See**: [mongodb-specifics.md](mongodb-specifics.md)

---

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

---

## Constraints (Parameters)

### NEVER

- Use Doctrine annotations/attributes in Domain entities
- Modify existing migrations after they're applied
- Skip XML mapping validation
- Leave empty migration files in codebase
- Commit without testing schema changes
- Skip `make setup-test-db` before integration tests

### ALWAYS

- Create XML mappings for all entity metadata
- Keep Domain entities framework-agnostic
- Define indexes for frequently queried fields
- Test migrations on dev database before committing
- Run `make setup-test-db` to verify schema
- Use Faker for unique test data (emails, names, etc.)
- Register resource directories in `api_platform.yaml`

---

## Format (Output)

### Expected Schema Validation Output

```bash
$ docker compose exec php bin/console doctrine:mongodb:schema:validate
The MongoDB schema is valid.
```

### Expected Test DB Setup Output

```bash
$ make setup-test-db
Database dropped and recreated successfully
```

---

## Verification Checklist

After entity/migration changes:

- [ ] Entity defined in Domain layer (no framework imports)
- [ ] XML mapping created in `config/doctrine/`
- [ ] API Platform resource configured (if needed)
- [ ] Repository interface in Domain layer
- [ ] Repository implementation in Infrastructure layer
- [ ] Repository registered in `services.yaml`
- [ ] Schema validates: `doctrine:mongodb:schema:validate`
- [ ] Test database setup works: `make setup-test-db`
- [ ] All integration tests pass
- [ ] `make deptrac` passes (no violations)
- [ ] `make ci` passes

---

## Related Skills

- [implementing-ddd-architecture](../implementing-ddd-architecture/SKILL.md) - DDD patterns and repository interfaces
- [api-platform-crud](../api-platform-crud/SKILL.md) - Configuring API Platform resources
- [deptrac-fixer](../deptrac-fixer/SKILL.md) - Fixing architectural violations

---

## Quick Commands

```bash
# Validate schema
docker compose exec php bin/console doctrine:mongodb:schema:validate

# Update schema
docker compose exec php bin/console doctrine:mongodb:schema:update

# Setup test database
make setup-test-db

# Clear cache after config changes
make cache-clear
```

---

## Reference Documentation

Detailed guides and examples:

- **[entity-creation-guide.md](entity-creation-guide.md)** - Complete entity creation workflow
- **[entity-modification-guide.md](entity-modification-guide.md)** - Modifying existing entities
- **[repository-patterns.md](repository-patterns.md)** - Repository implementation patterns
- **[mongodb-specifics.md](mongodb-specifics.md)** - MongoDB features and patterns
- **[reference/troubleshooting.md](reference/troubleshooting.md)** - Common issues and solutions
- **[examples/](examples/)** - Complete working examples

---

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
2. Verify schema: `doctrine:mongodb:schema:validate`
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

---

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
        $customer = new Customer(/* unique test data with Faker */);
        $this->repository->save($customer);

        $retrieved = $this->repository->findById($customer->getId());
        $this->assertNotNull($retrieved);
    }
}
```

**Important**: Always use Faker for unique test data.

---

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

**See**: [reference/troubleshooting.md](reference/troubleshooting.md) for comprehensive guide.

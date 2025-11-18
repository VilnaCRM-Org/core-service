---
name: deptrac-fixer
description: Diagnose and fix Deptrac architectural violations automatically. Use when Deptrac reports dependency violations, layers are incorrectly coupled, or when refactoring code to respect hexagonal architecture boundaries. Never modifies deptrac.yaml - always fixes the code to match the architecture.
#
# FOR OPENAI/GPT/CODEX AGENTS: Read this file and follow the "Diagnostic Workflow" section. Check examples/ for fix patterns.
# FOR CLAUDE CODE: This skill is automatically invoked when relevant.
#
---

# Deptrac Fixer Skill

**Automatically diagnose and fix architectural boundary violations without modifying Deptrac configuration.**

This skill provides a systematic approach to resolving Deptrac violations by refactoring code to respect layer boundaries.

## When to Use This Skill

Activate automatically when:

- `make deptrac` reports violations
- Code review shows architectural concerns
- Adding dependencies that might violate layer rules
- Refactoring code between layers
- Creating new classes that interact across layers
- Any error message containing "must not depend on"

**To understand DDD architecture patterns** (why layers exist, how to design new entities), see [implementing-ddd-architecture skill](../implementing-ddd-architecture/SKILL.md).

## Core Principle

**The architecture is sacred. NEVER modify `deptrac.yaml` to bypass violations - always fix the code.**

## Quick Start: Fix a Violation

### 1. Run Deptrac

```bash
make deptrac
```

### 2. Parse the Violation Message

```
Domain must not depend on Symfony
  src/Customer/Domain/Entity/Customer.php:15
    uses Symfony\Component\Validator\Constraints as Assert
```

**Extract**:

- **Violating Layer**: Domain
- **Forbidden Dependency**: Symfony
- **File**: `src/Customer/Domain/Entity/Customer.php:15`
- **Import**: `Symfony\Component\Validator\Constraints`

### 3. Apply the Fix Pattern

See [Violation Patterns](#common-violation-patterns) below.

### 4. Verify

```bash
make deptrac
```

Expected: Zero violations.

## Layer Dependency Rules

```
Infrastructure → Application → Domain
      ↓              ↓           ↓
  External       Use Cases    Pure Business
```

| From Layer         | Can Depend On                                       | CANNOT Depend On |
| ------------------ | --------------------------------------------------- | ---------------- |
| **Domain**         | NOTHING                                             | Everything       |
| **Application**    | Domain, Infrastructure, Symfony, API Platform, etc. | N/A              |
| **Infrastructure** | Domain, Application, Symfony, Doctrine, etc.        | N/A              |

## Common Violation Patterns

### Pattern 1: Domain → Symfony (Validation)

**Violation**:

```
Domain must not depend on Symfony
  src/Customer/Domain/Entity/Customer.php
    uses Symfony\Component\Validator\Constraints as Assert
```

**Cause**: Using Symfony validation attributes in domain entity.

**Fix Strategy**: Remove validation from Domain, use YAML config in Application layer

```php
// BEFORE (WRONG) - Domain with Symfony validation
namespace App\Customer\Domain\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Customer
{
    #[Assert\Email]  // ❌ Symfony in Domain!
    private string $email;
}

// AFTER (CORRECT) - Pure Domain entity
namespace App\Customer\Domain\Entity;

class Customer
{
    private string $email;  // ✅ Pure PHP, no framework

    public function __construct(string $email)
    {
        $this->email = $email;
    }
}

// Application DTO with YAML validation
// Application/DTO/CustomerCreate.php
namespace App\Core\Customer\Application\DTO;

final class CustomerCreate
{
    public string $email;
}

// config/validator/Customer.yaml
// App\Core\Customer\Application\DTO\CustomerCreate:
//   properties:
//     email:
//       - NotBlank: { message: 'not.blank' }
//       - Email: { message: 'email.invalid' }
```

### Pattern 2: Domain → Doctrine (Annotations)

**Violation**:

```
Domain must not depend on Doctrine
  src/Product/Domain/Entity/Product.php
    uses Doctrine\ODM\MongoDB\Mapping\Annotations as ODM
```

**Cause**: Using Doctrine mapping attributes in domain entity.

**Fix Strategy**: Use XML mappings instead

```php
// BEFORE (WRONG)
namespace App\Product\Domain\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'products')]
class Product
{
    #[ODM\Id(type: 'ulid', strategy: 'NONE')]
    private Ulid $id;
}

// AFTER (CORRECT)
namespace App\Product\Domain\Entity;

class Product
{
    private Ulid $id;
    // Pure PHP, no Doctrine imports
}
```

**XML Mapping** (`config/doctrine/Product.mongodb.xml`):

```xml
<doctrine-mongo-mapping>
    <document name="App\Product\Domain\Entity\Product" collection="products">
        <field name="id" type="ulid" id="true" strategy="NONE"/>
    </document>
</doctrine-mongo-mapping>
```

### Pattern 3: Domain → API Platform

**Violation**:

```
Domain must not depend on ApiPlatform
  src/Customer/Domain/Entity/Customer.php
    uses ApiPlatform\Metadata\ApiResource
```

**Cause**: API Platform attributes in domain.

**Fix Strategy A**: Configure API in YAML

```yaml
# config/packages/api_platform.yaml
resources:
  App\Customer\Domain\Entity\Customer:
    operations:
      get:
        method: GET
```

**Fix Strategy B**: Use DTOs in Application layer

```php
// Application/DTO/CustomerResource.php
namespace App\Customer\Application\DTO;

use ApiPlatform\Metadata\ApiResource;

#[ApiResource(operations: [...])]
final class CustomerResource
{
    public string $id;
    public string $email;
}
```

### Pattern 4: Domain → External Library

**Violation**:

```
Domain must not depend on MongoDB\BSON
  src/Customer/Domain/Entity/Customer.php
    uses MongoDB\BSON\ObjectId
```

**Cause**: Using MongoDB-specific types in domain.

**Fix Strategy**: Use domain Value Objects

```php
// BEFORE (WRONG)
use MongoDB\BSON\ObjectId;

class Customer
{
    private ObjectId $id;
}

// AFTER (CORRECT)
use App\Shared\Domain\ValueObject\Ulid;

class Customer
{
    private Ulid $id; // Framework-agnostic
}
```

### Pattern 5: Infrastructure → Application Handler

**Violation**:

```
Infrastructure must not depend on Application (direct handler call)
  src/Customer/Infrastructure/EventListener/CustomerListener.php
    uses App\Customer\Application\CommandHandler\SendEmailHandler
```

**Cause**: Direct handler dependency instead of using bus.

**Fix Strategy**: Use Command/Event Bus

```php
// BEFORE (WRONG)
namespace App\Customer\Infrastructure\EventListener;

use App\Customer\Application\CommandHandler\SendEmailHandler;

class CustomerListener
{
    public function __construct(
        private SendEmailHandler $handler
    ) {}
}

// AFTER (CORRECT)
use App\Shared\Domain\Bus\Command\CommandBusInterface;

class CustomerListener
{
    public function __construct(
        private CommandBusInterface $commandBus
    ) {}

    public function handle(): void
    {
        $this->commandBus->dispatch(new SendEmailCommand(...));
    }
}
```

## Diagnostic Workflow

### Step 1: Identify All Violations

```bash
make deptrac 2>&1 | grep "must not depend on"
```

### Step 2: Categorize by Type

Group violations by pattern:

- Domain → Framework (most critical)
- Infrastructure → Application
- Application → Infrastructure (rare but possible)

### Step 3: Fix in Order

**Priority**:

1. Domain → external dependencies
2. Infrastructure → Application
3. Other layer violations

### Step 4: Verify Incrementally

After each fix:

```bash
make deptrac
```

## Refactoring Checklist

Before refactoring to fix violation:

- [ ] Identify exact import causing violation
- [ ] Determine correct layer for the dependency
- [ ] Choose appropriate fix pattern
- [ ] Check if Value Object needed
- [ ] Check if XML mapping needed
- [ ] Check if Bus pattern needed
- [ ] Run `make deptrac` after fix
- [ ] Run `make unit-tests` to ensure no breaks

## Anti-Patterns

### DON'T: Modify deptrac.yaml

```yaml
# NEVER DO THIS
ruleset:
  Domain:
    - Symfony # Adding exception to silence violation
```

### DON'T: Wrap Framework Code

```php
// WRONG: Still depends on Symfony
namespace App\Customer\Domain\Service;

use Symfony\Component\Validator\Validator\ValidatorInterface;

class DomainValidator
{
    public function __construct(private ValidatorInterface $validator) {}
}
```

### DON'T: Move Entire Class to Wrong Layer

```php
// WRONG: Moving domain entity to application layer
namespace App\Customer\Application\Entity;

class Customer {} // Loses domain purity
```

## Advanced Patterns

### Creating Framework-Agnostic Interfaces

When domain needs external services:

```php
// Domain layer - interface only
namespace App\Customer\Domain\Service;

interface EmailValidatorInterface
{
    public function isValid(string $email): bool;
}

// Infrastructure layer - implementation
namespace App\Customer\Infrastructure\Service;

use App\Customer\Domain\Service\EmailValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SymfonyEmailValidator implements EmailValidatorInterface
{
    public function __construct(private ValidatorInterface $validator) {}

    public function isValid(string $email): bool
    {
        // Use Symfony validator here
    }
}
```

### Value Object Factory Pattern

```php
// Application layer can use framework validation
namespace App\Customer\Application\Factory;

use App\Customer\Domain\ValueObject\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class EmailFactory
{
    public function __construct(private ValidatorInterface $validator) {}

    public function createFromInput(string $input): Email
    {
        // Additional Symfony validation if needed
        $violations = $this->validator->validate($input, [...]);

        if (count($violations) > 0) {
            throw new InvalidInputException(...);
        }

        return new Email($input); // Domain VO validates itself too
    }
}
```

## Directory Structure Reference

When fixing violations, you need to know WHERE files should go. See [CODELY-STRUCTURE.md](CODELY-STRUCTURE.md) for:

- Complete CodelyTV directory hierarchy (`ls -la` style)
- Our project structure (adapted from CodelyTV)
- **Violation Fix Map** - exact file movements for each violation type
- Real-world examples from CodelyTV's php-ddd-example

**Quick reference**:

```
Domain Layer (NO framework imports!)
├── Entity/      → Aggregate roots and entities
├── ValueObject/ → Self-validating value objects (Email.php, Money.php)
├── Event/       → Domain events (CustomerCreated.php)
├── Repository/  → Interfaces ONLY (CustomerRepositoryInterface.php)
└── Exception/   → Domain exceptions (InvalidEmailException.php)

Application Layer (CAN use Symfony, API Platform)
├── Command/        → CreateCustomerCommand.php
├── CommandHandler/ → CreateCustomerHandler.php
├── EventSubscriber/→ SendEmailOnCustomerCreated.php
├── DTO/            → CustomerInput.php (validation via config/validator/)
└── Processor/      → CreateCustomerProcessor.php

Infrastructure Layer (Implements Domain interfaces)
├── Repository/     → MongoDBCustomerRepository.php
└── config/doctrine/→ Customer.mongodb.xml (XML mappings)
```

## Related Skills

- **[implementing-ddd-architecture](../implementing-ddd-architecture/SKILL.md)** - Full DDD patterns and layer responsibilities
- **[quality-standards](../quality-standards/SKILL.md)** - Overall code quality management
- **[complexity-management](../complexity-management/SKILL.md)** - Reducing complexity while fixing violations

## Success Criteria

- `make deptrac` reports zero violations
- All domain classes have no framework imports
- Business logic remains in domain layer
- Handlers orchestrate without business logic
- Validation handled in Application layer via YAML config
- XML mappings for Doctrine configuration

## Quick Commands

```bash
# Run Deptrac analysis
make deptrac

# Check specific layer violations
make deptrac 2>&1 | grep "Domain must not"

# Verify after fixes
make deptrac && make unit-tests
```

---

**Remember**: The architecture serves the domain. Fix the code, never the rules.

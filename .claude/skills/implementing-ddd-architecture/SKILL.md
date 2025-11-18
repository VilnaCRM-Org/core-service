---
name: implementing-ddd-architecture
description: Design and implement DDD patterns (entities, value objects, aggregates, CQRS). Use when creating new domain objects, implementing bounded contexts, designing repository interfaces, or learning proper layer separation. For fixing existing Deptrac violations, use the deptrac-fixer skill instead.
#
# FOR OPENAI/GPT/CODEX AGENTS: Read this entire file and the supporting files (REFERENCE.md, DIRECTORY-STRUCTURE.md, examples/).
# FOR CLAUDE CODE: This skill is automatically invoked when relevant.
#
---

# Implementing DDD Architecture

**Primary documentation for Domain-Driven Design and Hexagonal Architecture in this project.**

This skill enforces architectural patterns inspired by [CodelyTV's php-ddd-example](https://github.com/CodelyTV/php-ddd-example), ensuring proper layer separation, dependency flow, and business domain alignment.

## When to Use This Skill

Activate automatically when:

- Creating/modifying entities, value objects, or aggregates
- Implementing bounded contexts or modules
- Designing new domain models from scratch
- Designing repository interfaces and implementations
- Implementing CQRS patterns (Commands/Handlers)
- Working with domain events and event subscribers
- Learning proper layer separation (Domain/Application/Infrastructure)
- Code review for architectural compliance

**For fixing existing Deptrac violations**, use [deptrac-fixer skill](../deptrac-fixer/SKILL.md) - it provides step-by-step fix patterns.

## Core Principle

**🚨 The architecture defines the project. NEVER modify `deptrac.yaml` to bypass violations - always fix the code. 🚨**

## Quick Reference: Layer Dependency Rules

```
Infrastructure → Application → Domain
         ↓            ↓           ↓
     External      Use Cases   Business Logic
```

| Layer              | Can Depend On                                 | Cannot Depend On | Contains                                                      |
| ------------------ | --------------------------------------------- | ---------------- | ------------------------------------------------------------- |
| **Domain**         | NOTHING                                       | Everything       | Entities, Value Objects, Repository Interfaces, Domain Events |
| **Application**    | Domain, Infrastructure, Symfony, API Platform | N/A              | Command Handlers, Event Subscribers, DTOs, Transformers       |
| **Infrastructure** | Domain, Application, Symfony, Doctrine        | N/A              | Repository Implementations, Message Bus, Doctrine Types       |

## Critical Rules

### 1. Domain Layer Purity

- ❌ **NO** external dependencies (Symfony, Doctrine, API Platform)
- ❌ **NO** framework imports or attributes
- ❌ **NO** validation in Domain (handle in Application DTOs with YAML config)
- ✅ Pure PHP business logic only
- ✅ Define repository interfaces (not implementations)

### 2. Deptrac Violations

**When Deptrac fails:**

1. Read violation message carefully
2. Identify incorrect dependency
3. Refactor code to correct layer
4. **NEVER change `deptrac.yaml`**

**Common fixes:**

- Domain → Symfony: Remove validation from Domain, use YAML config in Application
- Domain → Doctrine: Use XML mappings in `config/doctrine/`
- Domain → API Platform: Move to Application layer or YAML config
- Infrastructure → Handler: Use Command/Event Bus instead

### 3. Rich Domain Models (Not Anemic)

❌ **Wrong (Anemic)**:

```php
class Customer {
    public function setName(string $name): void { $this->name = $name; }
}
```

✅ **Correct (Rich)**:

```php
class Customer extends AggregateRoot {
    public function changeName(string $newName): void {
        $this->ensureNameIsValid($newName);
        $this->name = $newName;
        $this->record(new CustomerNameChanged($this->id, $newName));
    }
}
```

### 4. Validation Pattern

**Domain Layer** - Pure entities with NO validation (no Symfony dependencies)
**Application Layer** - YAML validation config at `config/validator/{Entity}.yaml` for DTOs

❌ **Wrong**:

```php
// Domain/Entity/Customer.php - NEVER use framework attributes in Domain!
class Customer {
    #[Assert\Email] // ❌ Framework dependency in domain!
    private string $email;
}
```

✅ **Correct (Domain Layer)**:

```php
// Domain/Entity/Customer.php - Pure PHP, no validation
namespace App\Customer\Domain\Entity;

class Customer {
    private string $email; // Pure property, no framework dependencies

    public function __construct(string $email)
    {
        $this->email = $email;
    }
}
```

✅ **Correct (Application Layer)**:

```php
// Application/DTO/CustomerCreate.php - Clean DTO
namespace App\Core\Customer\Application\DTO;

final class CustomerCreate
{
    public string $email;
    public string $initials;
}
```

```yaml
# config/validator/Customer.yaml - Validation configuration
App\Core\Customer\Application\DTO\CustomerCreate:
  properties:
    email:
      - NotBlank: { message: 'not.blank' }
      - Email: { message: 'email.invalid' }
      - App\Shared\Application\Validator\UniqueEmail: ~
    initials:
      - NotBlank: { message: 'not.blank' }
      - App\Shared\Application\Validator\Initials: ~
```

## CQRS Pattern Quick Start

### Commands (Write Operations)

```php
// Application/Command/CreateCustomerCommand.php
final readonly class CreateCustomerCommand implements CommandInterface {
    public function __construct(
        public Ulid $id,
        public string $email,
        public string $name
    ) {}
}
```

### Command Handlers (Orchestration)

```php
// Application/CommandHandler/CreateCustomerHandler.php
final readonly class CreateCustomerHandler implements CommandHandlerInterface {
    public function __invoke(CreateCustomerCommand $command): void {
        // Transform to domain objects
        $customer = new Customer(
            $command->id,
            new Email($command->email),
            $command->name
        );

        // Delegate to repository
        $this->repository->save($customer);

        // Events auto-dispatched after flush
    }
}
```

**Auto-registration** in `config/services.yaml`:

```yaml
_instanceof:
  App\Shared\Domain\Bus\Command\CommandHandlerInterface:
    tags: ['app.command_handler']
```

## Repository Pattern (Hexagonal)

### Interface (Domain Layer)

```php
// Domain/Repository/CustomerRepositoryInterface.php
interface CustomerRepositoryInterface {
    public function save(Customer $customer): void;
    public function findById(Ulid $id): ?Customer;
}
```

### Implementation (Infrastructure Layer)

```php
// Infrastructure/Repository/CustomerRepository.php
final class CustomerRepository implements CustomerRepositoryInterface {
    public function __construct(private DocumentManager $dm) {}

    public function save(Customer $customer): void {
        $this->dm->persist($customer);
        $this->dm->flush();
    }
}
```

## Domain Events Pattern

### Recording Events in Aggregates

```php
class Customer extends AggregateRoot {
    public function __construct(Ulid $id, Email $email, string $name) {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;

        // Record event - dispatched after persistence
        $this->record(new CustomerCreated($this->id, $this->email));
    }
}
```

### Event Subscribers (Application Layer)

```php
// Application/EventSubscriber/SendWelcomeEmailOnCustomerCreated.php
final readonly class SendWelcomeEmailOnCustomerCreated implements DomainEventSubscriberInterface {
    public static function subscribedTo(): array {
        return [CustomerCreated::class];
    }

    public function __invoke(CustomerCreated $event): void {
        $this->emailService->sendWelcome($event->customerId);
    }
}
```

## Step-by-Step Workflows

### Creating a New Entity

For detailed workflow with all steps, see [REFERENCE.md - Creating New Entity](REFERENCE.md#creating-a-new-entity).

**Quick steps:**

1. Create entity in `{Context}/Domain/Entity/` (pure PHP)
2. Define repository interface in `{Context}/Domain/Repository/`
3. Create XML mapping in `config/doctrine/{Entity}.orm.xml`
4. Implement repository in `{Context}/Infrastructure/Repository/`
5. Create Command and Handler in `{Context}/Application/`
6. Run `make deptrac` to verify

### Fixing Deptrac Violations

For complete examples and solutions, see [REFERENCE.md - Fixing Violations](REFERENCE.md#fixing-deptrac-violations).

**Quick process:**

1. Run `make deptrac`
2. Read violation message
3. Identify wrong dependency
4. Refactor to correct layer
5. Re-run `make deptrac`

## Quality Checklist

Before completing any task:

- [ ] `make deptrac` passes with zero violations
- [ ] Domain layer has NO framework imports
- [ ] Business logic is in Domain entities/VOs
- [ ] Handlers only orchestrate (no business logic)
- [ ] Value Objects validate themselves
- [ ] Commands implement `CommandInterface`
- [ ] Handlers implement `CommandHandlerInterface`
- [ ] Repository interfaces in Domain, implementations in Infrastructure
- [ ] Doctrine mappings use XML, not annotations
- [ ] Aggregates extend `AggregateRoot` and use `record()` for events

## Anti-Patterns to Avoid

### 1. Business Logic in Handlers

❌ Don't put validation/business rules in handlers
✅ Delegate to domain methods

### 2. Framework Dependencies in Domain

❌ No Symfony, Doctrine, API Platform in domain
✅ Pure PHP with Value Objects

### 3. Anemic Domain Models

❌ No getters/setters without behavior
✅ Rich models with business methods

### 4. Modifying Deptrac

❌ NEVER change `deptrac.yaml` to silence violations
✅ Always fix the code architecture

### 5. Not Using Value Objects

❌ String primitives with scattered validation
✅ Value Objects that validate themselves

## Detailed Resources

For comprehensive documentation, complete code examples, and detailed patterns:

- **[REFERENCE.md](REFERENCE.md)** - Detailed layer responsibilities, complete workflows, pattern explanations
- **[DIRECTORY-STRUCTURE.md](DIRECTORY-STRUCTURE.md)** - Where to place files based on CodelyTV patterns
- **[examples/](examples/)** - Working code examples:
  - `01-entity-example.php` - Complete rich domain entity
  - `02-value-object-examples.php` - Value Object patterns
  - `03-cqrs-pattern-example.php` - Complete CQRS flow
  - `04-fixing-deptrac-violations.php` - Before/after fixes for violations
  - `README.md` - Examples overview and usage guide

## Related Skills

- **[deptrac-fixer](../deptrac-fixer/SKILL.md)** - Diagnose and fix Deptrac violations automatically
- **[quality-standards](../quality-standards/SKILL.md)** - Maintain code quality without decreasing thresholds
- **[database-migrations](../database-migrations/SKILL.md)** - Entity creation and modification with MongoDB

## Project References

- **CLAUDE.md** (project root) - Development commands and make targets
- **deptrac.yaml** (project root) - Layer definitions and rules
- [CodelyTV PHP DDD Example](https://github.com/CodelyTV/php-ddd-example) - Reference implementation

## CodelyTV Architecture Pattern

This project follows the **CodelyTV DDD structure**:

```
src/
├── {BoundedContext}/              # e.g., Mooc, Backoffice, Customer
│   ├── {Module}/                  # e.g., Courses, Videos, Orders
│   │   ├── Application/           # Use cases (Create/, Find/, Update/)
│   │   ├── Domain/                # Pure business (Entity, ValueObject, Event)
│   │   └── Infrastructure/        # Technical (Repository implementations)
│   └── Shared/                    # Shared within context
└── Shared/                        # Shared kernel (cross-context)
```

See **[DIRECTORY-STRUCTURE.md](DIRECTORY-STRUCTURE.md)** for detailed file placement guide.

---

**Remember**: Respect architectural boundaries. The architecture exists to serve the business domain and maintain long-term maintainability.

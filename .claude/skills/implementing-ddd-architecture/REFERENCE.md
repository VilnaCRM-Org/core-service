# DDD Architecture Reference Guide

**Detailed patterns, workflows, and implementation guidelines for Domain-Driven Design architecture.**

This document provides comprehensive explanations and step-by-step workflows referenced from [SKILL.md](SKILL.md).

## Table of Contents

- [Layer Responsibilities](#layer-responsibilities)
- [Creating a New Entity](#creating-a-new-entity)
- [Fixing Deptrac Violations](#fixing-deptrac-violations)
- [Complete Pattern Examples](#complete-pattern-examples)
- [Doctrine Configuration](#doctrine-configuration)
- [Event-Driven Architecture Details](#event-driven-architecture-details)
- [Repository Pattern Details](#repository-pattern-details)
- [Anti-Patterns Deep Dive](#anti-patterns-deep-dive)

---

## Layer Responsibilities

### Domain Layer: Pure Business Logic

**Purpose**: Encapsulate core business logic and business rules.

**Allowed Dependencies**: NONE (pure PHP only)

**Contains**:

- **Entities**: Objects with identity (e.g., `Customer`, `Product`, `Order`)
- **Value Objects**: Immutable objects without identity (e.g., `Email`, `Money`, `Address`)
- **Aggregates**: Clusters of entities treated as a single unit (extend `AggregateRoot`)
- **Domain Events**: Interfaces representing business events (e.g., `CustomerCreated`, `OrderPlaced`)
- **Repository Interfaces**: Contracts for data persistence
- **Domain Exceptions**: Business rule violations (e.g., `InvalidEmailException`)
- **Factory Interfaces**: Complex object creation contracts

**Strict Rules**:

- ❌ NO Symfony components (`Symfony\*`)
- ❌ NO Doctrine annotations/attributes (`Doctrine\*`)
- ❌ NO API Platform decorators (`ApiPlatform\*`)
- ❌ NO framework-specific code
- ❌ NO persistence concerns
- ❌ NO HTTP/API concerns
- ✅ Pure business logic ONLY

**Example Domain Entity**:

```php
// src/Customer/Domain/Entity/Customer.php
namespace App\Customer\Domain\Entity;

use App\Shared\Domain\Aggregate\AggregateRoot;
use App\Customer\Domain\ValueObject\Email;
use App\Customer\Domain\Event\CustomerCreated;

class Customer extends AggregateRoot
{
    private Ulid $id;
    private Email $email;
    private string $name;

    public function __construct(Ulid $id, Email $email, string $name)
    {
        $this->id = $id;
        $this->email = $email;
        $this->ensureNameIsValid($name);
        $this->name = $name;

        // Record domain event
        $this->record(new CustomerCreated($this->id, $this->email));
    }

    public function changeName(string $newName): void
    {
        $this->ensureNameIsValid($newName);
        $this->name = $newName;
    }

    private function ensureNameIsValid(string $name): void
    {
        if (empty(trim($name))) {
            throw new InvalidCustomerNameException(
                "Customer name cannot be empty"
            );
        }
    }

    // Pure getters, no business logic in getters
    public function id(): Ulid { return $this->id; }
    public function email(): Email { return $this->email; }
    public function name(): string { return $this->name; }
}
```

### Application Layer: Use Case Orchestration

**Purpose**: Orchestrate use cases and coordinate between Domain and Infrastructure.

**Allowed Dependencies**: Domain, Infrastructure, Symfony, API Platform, GraphQL, Logging

**Contains**:

- **Command Handlers**: Execute write operations (implement `CommandHandlerInterface`)
- **Event Subscribers**: React to domain events (implement `DomainEventSubscriberInterface`)
- **DTOs**: Data transfer between layers (validation via YAML config at `config/validator/`)
- **Transformers**: Convert between representations
- **API Platform Processors**: Handle API operations
- **GraphQL Resolvers**: Handle GraphQL queries/mutations
- **Message Handlers**: Process async messages
- **Factory Implementations**: Build domain objects from external data

**Rules**:

- ❌ NO business logic (delegate to Domain)
- ✅ Orchestrate workflows
- ✅ Transform data between layers
- ✅ Handle application-level concerns (validation, transformation)
- ✅ Use dependency injection

**Example Command Handler**:

```php
// src/Customer/Application/CommandHandler/CreateCustomerHandler.php
namespace App\Customer\Application\CommandHandler;

use App\Customer\Application\Command\CreateCustomerCommand;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Customer\Domain\ValueObject\Email;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;

final readonly class CreateCustomerHandler implements CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository
    ) {}

    public function __invoke(CreateCustomerCommand $command): void
    {
        // Transform primitive data to domain value objects
        $email = new Email($command->email);

        // Call domain factory/constructor - business logic is in the domain
        $customer = new Customer(
            $command->id,
            $email,
            $command->name
        );

        // Persist - infrastructure concern
        $this->repository->save($customer);

        // Domain events are automatically dispatched after flush
    }
}
```

### Infrastructure Layer: Technical Implementation

**Purpose**: Implement technical details and external integrations.

**Allowed Dependencies**: Domain, Application, Symfony, Doctrine, Logging

**Contains**:

- **Repository Implementations**: Concrete persistence logic (Doctrine ODM)
- **Message Bus Implementations**: Command/Event bus with Symfony Messenger
- **Doctrine Types**: Custom database types (e.g., `UlidType`, `DomainUuidType`)
- **External Service Integrations**: APIs, message queues, email services
- **Event Listeners**: Doctrine lifecycle listeners
- **Retry Strategies**: For message handling

**Rules**:

- ✅ Implement interfaces from Domain
- ✅ Handle persistence details
- ✅ Manage external communications
- ❌ NO business logic

**Example Repository Implementation**:

```php
// src/Customer/Infrastructure/Repository/CustomerRepository.php
namespace App\Customer\Infrastructure\Repository;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\ValueObject\Ulid;
use Doctrine\ODM\MongoDB\DocumentManager;

final class CustomerRepository implements CustomerRepositoryInterface
{
    public function __construct(
        private DocumentManager $documentManager
    ) {}

    public function save(Customer $customer): void
    {
        $this->documentManager->persist($customer);
        $this->documentManager->flush();
        // Domain events are dispatched via Doctrine listeners after flush
    }

    public function findById(Ulid $id): ?Customer
    {
        return $this->documentManager->find(Customer::class, $id);
    }
}
```

---

## Creating a New Entity

**Complete workflow for adding a new entity to the system.**

### Step 1: Identify Bounded Context

**Questions to answer**:

- Does this entity belong to an existing context (e.g., `Customer`, `Catalog`, `Order`)?
- Or do you need to create a new bounded context?
- What is the business domain for this entity?

**Example**: Adding a `Product` entity → belongs in `Catalog` context

### Step 2: Design Domain Model (Domain Layer)

**Location**: `src/{Context}/Domain/Entity/{Entity}.php`

**Tasks**:

1. Create the entity class
2. Identify Value Objects needed (e.g., `Money`, `ProductName`)
3. Define business rules and invariants
4. Decide if it's an Aggregate Root (extends `AggregateRoot`)
5. Design business methods (not setters!)

**Example**:

```php
// src/Catalog/Domain/Entity/Product.php
namespace App\Catalog\Domain\Entity;

use App\Catalog\Domain\ValueObject\Money;
use App\Catalog\Domain\ValueObject\ProductName;
use App\Catalog\Domain\Event\ProductCreated;
use App\Shared\Domain\Aggregate\AggregateRoot;
use App\Shared\Domain\ValueObject\Ulid;

class Product extends AggregateRoot
{
    private Ulid $id;
    private ProductName $name;
    private Money $price;
    private \DateTimeImmutable $createdAt;

    private function __construct(
        Ulid $id,
        ProductName $name,
        Money $price,
        \DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->price = $price;
        $this->createdAt = $createdAt;
    }

    // Named constructor - expresses intent
    public static function create(Ulid $id, ProductName $name, Money $price): self
    {
        $product = new self($id, $name, $price, new \DateTimeImmutable());
        $product->record(new ProductCreated($product->id, $product->name, $product->price));
        return $product;
    }

    // Business method - not a setter
    public function changePrice(Money $newPrice): void
    {
        if ($newPrice->isNegative()) {
            throw new InvalidProductPriceException("Price cannot be negative");
        }

        $this->price = $newPrice;
        $this->record(new ProductPriceChanged($this->id, $newPrice));
    }

    // Getters
    public function id(): Ulid { return $this->id; }
    public function name(): ProductName { return $this->name; }
    public function price(): Money { return $this->price; }
}
```

### Step 3: Define Repository Interface (Domain Layer)

**Location**: `src/{Context}/Domain/Repository/{Entity}RepositoryInterface.php`

**Tasks**:

1. Define `save()` method
2. Define `findById()` method
3. Add custom finders as needed (e.g., `findByName()`, `findByCriteria()`)

**Example**:

```php
// src/Catalog/Domain/Repository/ProductRepositoryInterface.php
namespace App\Catalog\Domain\Repository;

use App\Catalog\Domain\Entity\Product;
use App\Shared\Domain\ValueObject\Ulid;

interface ProductRepositoryInterface
{
    public function save(Product $product): void;

    public function findById(Ulid $id): ?Product;

    public function findByName(ProductName $name): ?Product;
}
```

### Step 4: Create Doctrine Mapping (Infrastructure Config)

**Location**: `config/doctrine/{Entity}.orm.xml`

**Tasks**:

1. Create XML mapping file
2. Map entity fields to database
3. Map Value Object embeds
4. Define ID strategy

**Example**:

```xml
<!-- config/doctrine/Product.orm.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping">
    <document name="App\Catalog\Domain\Entity\Product" collection="products">
        <field name="id" type="ulid" id="true" strategy="NONE"/>
        <field name="name" type="string"/>
        <embed-one target-document="App\Catalog\Domain\ValueObject\Money" field="price"/>
        <field name="createdAt" type="datetime_immutable"/>
    </document>
</doctrine-mapping>
```

### Step 5: Implement Repository (Infrastructure Layer)

**Location**: `src/{Context}/Infrastructure/Repository/{Entity}Repository.php`

**Tasks**:

1. Implement the repository interface
2. Inject Doctrine `DocumentManager`
3. Implement persistence methods

**Example**:

```php
// src/Catalog/Infrastructure/Repository/ProductRepository.php
namespace App\Catalog\Infrastructure\Repository;

use App\Catalog\Domain\Entity\Product;
use App\Catalog\Domain\Repository\ProductRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;

final class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(private DocumentManager $dm) {}

    public function save(Product $product): void
    {
        $this->dm->persist($product);
        $this->dm->flush();
    }

    public function findById(Ulid $id): ?Product
    {
        return $this->dm->find(Product::class, $id);
    }
}
```

### Step 6: Create Commands (Application Layer)

**Location**: `src/{Context}/Application/Command/{Action}{Entity}Command.php`

**Tasks**:

1. Create command implementing `CommandInterface`
2. Make it readonly and immutable
3. Use primitive types or Ulid for properties

**Example**:

```php
// src/Catalog/Application/Command/CreateProductCommand.php
namespace App\Catalog\Application\Command;

use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Shared\Domain\ValueObject\Ulid;

final readonly class CreateProductCommand implements CommandInterface
{
    public function __construct(
        public Ulid $id,
        public string $name,
        public int $priceInCents,
        public string $currency
    ) {}
}
```

### Step 7: Create Command Handlers (Application Layer)

**Location**: `src/{Context}/Application/CommandHandler/{Action}{Entity}Handler.php`

**Tasks**:

1. Create handler implementing `CommandHandlerInterface`
2. Inject repository
3. Transform command data to domain objects
4. Call domain methods
5. Persist via repository

**Example** (shown above in Application Layer section)

### Step 8: Define Domain Events (Domain Layer)

**Location**: `src/{Context}/Domain/Event/{Entity}{Action}.php`

**Example**:

```php
// src/Catalog/Domain/Event/ProductCreated.php
namespace App\Catalog\Domain\Event;

use App\Shared\Domain\Bus\Event\DomainEvent;

final readonly class ProductCreated extends DomainEvent
{
    public function __construct(
        public Ulid $productId,
        public ProductName $name,
        public Money $price,
        ?string $eventId = null,
        ?string $occurredOn = null
    ) {
        parent::__construct($eventId, $occurredOn);
    }

    public static function eventName(): string
    {
        return 'product.created';
    }
}
```

### Step 9: Create Event Subscribers (Application Layer)

**Location**: `src/{Context}/Application/EventSubscriber/{Action}On{Event}.php`

**Example**:

```php
// src/Catalog/Application/EventSubscriber/NotifyWarehouseOnProductCreated.php
namespace App\Catalog\Application\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

final readonly class NotifyWarehouseOnProductCreated implements DomainEventSubscriberInterface
{
    public function __construct(
        private WarehouseService $warehouseService
    ) {}

    public static function subscribedTo(): array
    {
        return [ProductCreated::class];
    }

    public function __invoke(ProductCreated $event): void
    {
        $this->warehouseService->notifyNewProduct($event->productId);
    }
}
```

### Step 10: Verify Architecture

**Run Deptrac**:

```bash
make deptrac
```

**Expected**: Zero violations. If violations exist, fix the code (never change `deptrac.yaml`).

**Run Tests**:

```bash
make unit-tests
make integration-tests
```

---

## Fixing Deptrac Violations

**Complete guide to diagnosing and fixing architectural violations.**

### Violation Process

1. **Run Deptrac**: `make deptrac`
2. **Read Output Carefully**: Understand what's wrong
3. **Identify Problem**: Which layer, which dependency?
4. **Plan Refactor**: How to fix the architecture?
5. **Implement Fix**: Move/refactor code
6. **Verify**: Re-run `make deptrac`

### Common Violation Patterns

#### Violation Type 1: Domain → Symfony (Validators)

**Symptom**:

```
Domain must not depend on Symfony
src/Customer/Domain/Entity/Customer.php:15
  uses Symfony\Component\Validator\Constraints as Assert
```

**Problem Code**:

```php
namespace App\Customer\Domain\Entity;

use Symfony\Component\Validator\Constraints as Assert;

class Customer
{
    #[Assert\Email]
    private string $email;

    #[Assert\NotBlank]
    private string $name;
}
```

**Solution**: Extract validation to Value Objects

```php
// Domain entity
namespace App\Customer\Domain\Entity;

use App\Customer\Domain\ValueObject\Email;

class Customer
{
    private Email $email; // Value Object validates itself
    private string $name;

    public function __construct(Email $email, string $name)
    {
        $this->email = $email;
        $this->ensureNameIsValid($name);
        $this->name = $name;
    }

    private function ensureNameIsValid(string $name): void
    {
        if (empty(trim($name))) {
            throw new InvalidCustomerNameException();
        }
    }
}

// Value Object
namespace App\Customer\Domain\ValueObject;

final readonly class Email
{
    public function __construct(private string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException("Invalid email: {$value}");
        }
    }

    public function value(): string { return $this->value; }
}
```

**Optional**: If Symfony validation needed for API input, use DTOs in Application layer:

```php
// Application/DTO/CreateCustomerDTO.php
namespace App\Customer\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateCustomerDTO
{
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    public string $name;
}
```

#### Violation Type 2: Domain → Doctrine (Annotations)

**Symptom**:

```
Domain must not depend on Doctrine
src/Product/Domain/Entity/Product.php:10
  uses Doctrine\ODM\MongoDB\Mapping\Annotations as ODM
```

**Problem Code**:

```php
namespace App\Product\Domain\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'products')]
class Product
{
    #[ODM\Id(type: 'ulid', strategy: 'NONE')]
    private Ulid $id;

    #[ODM\Field(type: 'string')]
    private string $name;
}
```

**Solution**: Use XML mappings

```php
// Pure domain entity (NO Doctrine imports)
namespace App\Product\Domain\Entity;

class Product
{
    private Ulid $id;
    private string $name;

    // Pure business logic
}
```

```xml
<!-- config/doctrine/Product.orm.xml -->
<doctrine-mapping>
    <document name="App\Product\Domain\Entity\Product" collection="products">
        <field name="id" type="ulid" id="true" strategy="NONE"/>
        <field name="name" type="string"/>
    </document>
</doctrine-mapping>
```

#### Violation Type 3: Domain → API Platform (Attributes)

**Symptom**:

```
Domain must not depend on ApiPlatform
src/Customer/Domain/Entity/Customer.php:8
  uses ApiPlatform\Metadata\ApiResource
```

**Problem Code**:

```php
namespace App\Customer\Domain\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

#[ApiResource(operations: [new Get()])]
class Customer
{
    private Ulid $id;
}
```

**Solution Option 1**: Configure in YAML

```php
// Pure domain entity
namespace App\Customer\Domain\Entity;

class Customer
{
    private Ulid $id;
}
```

```yaml
# config/packages/api_platform.yaml
resources:
  App\Customer\Domain\Entity\Customer:
    operations:
      get:
        method: GET
```

**Solution Option 2**: Use DTOs in Application layer

```php
// Application/DTO/CustomerDTO.php
namespace App\Customer\Application\DTO;

use ApiPlatform\Metadata\ApiResource;

#[ApiResource(operations: [...])]
final class CustomerDTO
{
    public string $id;
    public string $name;
}
```

#### Violation Type 4: Infrastructure → Application (Handler)

**Symptom**:

```
Infrastructure must not depend on Application (Command Handler)
src/Customer/Infrastructure/EventListener/CustomerListener.php:25
```

**Problem Code**:

```php
namespace App\Customer\Infrastructure\EventListener;

use App\Customer\Application\CommandHandler\SendWelcomeEmailHandler;

class CustomerListener
{
    public function __construct(
        private SendWelcomeEmailHandler $handler // Direct dependency!
    ) {}

    public function postPersist(LifecycleEventArgs $args): void
    {
        ($this->handler)(new SendWelcomeEmailCommand(...));
    }
}
```

**Solution**: Use Command Bus

```php
namespace App\Customer\Infrastructure\EventListener;

use App\Shared\Domain\Bus\Command\CommandBusInterface;

class CustomerListener
{
    public function __construct(
        private CommandBusInterface $commandBus // Use bus instead
    ) {}

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->commandBus->dispatch(new SendWelcomeEmailCommand(...));
    }
}
```

**Better Solution**: Use Domain Events

```php
// Domain entity records event
class Customer extends AggregateRoot
{
    public function __construct(...)
    {
        // ...
        $this->record(new CustomerCreated($this->id));
    }
}

// Application subscriber reacts
class SendWelcomeEmailOnCustomerCreated implements DomainEventSubscriberInterface
{
    public static function subscribedTo(): array
    {
        return [CustomerCreated::class];
    }

    public function __invoke(CustomerCreated $event): void
    {
        // Send email
    }
}
```

---

## Complete Pattern Examples

See [examples/ directory](examples/) for complete, working code examples:

- **01-entity-example.php**: Complete rich domain entity with business logic
- **02-value-object-examples.php**: Multiple Value Object patterns
- **03-cqrs-pattern-example.php**: Complete CQRS flow (Command → Handler → Repository)
- **04-fixing-deptrac-violations.php**: Before/after code for all violation types

---

## Doctrine Configuration

### XML Mapping Structure

**Location**: `config/doctrine/{EntityName}.orm.xml`

**Basic Template**:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                  https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <document name="App\{Context}\Domain\Entity\{Entity}" collection="{collection_name}">
        <!-- ID field -->
        <field name="id" type="ulid" id="true" strategy="NONE"/>

        <!-- Simple fields -->
        <field name="name" type="string"/>
        <field name="createdAt" type="datetime_immutable"/>

        <!-- Embedded Value Objects -->
        <embed-one target-document="App\{Context}\Domain\ValueObject\Email" field="email"/>

        <!-- References -->
        <reference-one target-document="App\{OtherContext}\Domain\Entity\Other" field="other"/>
    </document>
</doctrine-mapping>
```

### Custom Doctrine Types

**Location**: `src/Shared/Infrastructure/DoctrineType/`

**Example: Ulid Type**:

```php
namespace App\Shared\Infrastructure\DoctrineType;

use Doctrine\ODM\MongoDB\Types\Type;
use Symfony\Component\Uid\Ulid;

final class UlidType extends Type
{
    public function convertToPHPValue($value): Ulid
    {
        return Ulid::fromString($value);
    }

    public function convertToDatabaseValue($value): string
    {
        return (string) $value;
    }
}
```

**Register in `config/doctrine.yaml`**:

```yaml
doctrine_mongodb:
  types:
    ulid: App\Shared\Infrastructure\DoctrineType\UlidType
```

---

## Event-Driven Architecture Details

### Domain Event Structure

Domain events extend `DomainEvent` base class:

```php
namespace App\Shared\Domain\Bus\Event;

abstract readonly class DomainEvent
{
    private string $eventId;
    private string $occurredOn;

    public function __construct(?string $eventId = null, ?string $occurredOn = null)
    {
        $this->eventId = $eventId ?? Ulid::random()->toString();
        $this->occurredOn = $occurredOn ?? (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    abstract public static function eventName(): string;

    public function eventId(): string { return $this->eventId; }
    public function occurredOn(): string { return $this->occurredOn; }
}
```

### Event Subscriber Auto-Registration

**In `config/services.yaml`**:

```yaml
_instanceof:
  App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface:
    tags: ['app.event_subscriber']

App\Shared\Infrastructure\Bus\Event\EventBusFactory:
  arguments:
    - !tagged_iterator app.event_subscriber
```

### Event Dispatching Flow

1. Domain entity records event: `$this->record($event)`
2. Event stored in aggregate: `$this->domainEvents[]`
3. Handler calls repository: `$repository->save($entity)`
4. Repository persists: `$documentManager->flush()`
5. Infrastructure listener pulls events: `$entity->pullDomainEvents()`
6. Events dispatched to subscribers via Symfony Messenger

---

## Repository Pattern Details

### Repository Method Naming

Use consistent, descriptive names:

- `save(Entity $entity): void` - Persist (create/update)
- `findById(Ulid $id): ?Entity` - Find by ID, nullable
- `findByEmail(Email $email): ?Entity` - Find by value object
- `findAll(): array` - Get all entities
- `findByCriteria(Criteria $criteria): Collection` - Complex queries
- `delete(Entity $entity): void` - Remove entity

### Criteria Pattern

For complex queries, use Specification/Criteria pattern:

```php
interface ProductRepositoryInterface
{
    public function findByCriteria(Criteria $criteria): ProductCollection;
}

// Usage
$criteria = new Criteria(
    filters: ['status' => 'published', 'price' => ['$gt' => 1000]],
    orderBy: ['createdAt' => 'DESC'],
    limit: 10
);

$products = $repository->findByCriteria($criteria);
```

---

## Anti-Patterns Deep Dive

### 1. Business Logic in Command Handlers

**Why it's wrong**: Handlers are for orchestration, not business rules.

**Example (WRONG)**:

```php
class UpdateCustomerStatusHandler
{
    public function __invoke(UpdateCustomerStatusCommand $cmd): void
    {
        $customer = $this->repository->findById($cmd->customerId);

        // Business logic in handler - WRONG!
        if ($customer->getStatus() === 'active' && $cmd->newStatus === 'active') {
            throw new CustomerAlreadyActiveException();
        }

        $customer->setStatus($cmd->newStatus);
        $this->repository->save($customer);
    }
}
```

**Correct Approach**:

```php
// Handler orchestrates
class UpdateCustomerStatusHandler
{
    public function __invoke(UpdateCustomerStatusCommand $cmd): void
    {
        $customer = $this->repository->findById($cmd->customerId);

        // Delegate to domain method
        if ($cmd->newStatus === 'active') {
            $customer->activate();
        } else {
            $customer->deactivate();
        }

        $this->repository->save($customer);
    }
}

// Domain entity contains business logic
class Customer extends AggregateRoot
{
    public function activate(): void
    {
        if ($this->status->isActive()) {
            throw new CustomerAlreadyActiveException();
        }
        $this->status = CustomerStatus::active();
        $this->record(new CustomerActivated($this->id));
    }
}
```

### 2. Anemic Domain Models

**Why it's wrong**: Domain becomes a data bag; logic scatters across handlers.

**Example (WRONG)**:

```php
class Order
{
    private array $items = [];

    public function getItems(): array { return $this->items; }
    public function setItems(array $items): void { $this->items = $items; }
}

// Business logic in handler
class AddOrderItemHandler
{
    public function __invoke(AddOrderItemCommand $cmd): void
    {
        $order = $this->repository->findById($cmd->orderId);
        $items = $order->getItems();

        // Validation in handler - WRONG
        if (count($items) >= 100) {
            throw new TooManyItemsException();
        }

        $items[] = new OrderItem($cmd->productId, $cmd->quantity);
        $order->setItems($items);
    }
}
```

**Correct Approach**:

```php
class Order extends AggregateRoot
{
    private array $items = [];

    // Business method with validation
    public function addItem(OrderItem $item): void
    {
        if (count($this->items) >= 100) {
            throw new TooManyItemsException("Order cannot have more than 100 items");
        }

        $this->items[] = $item;
        $this->record(new OrderItemAdded($this->id, $item));
    }

    public function items(): array { return $this->items; }
}

// Handler delegates
class AddOrderItemHandler
{
    public function __invoke(AddOrderItemCommand $cmd): void
    {
        $order = $this->repository->findById($cmd->orderId);
        $item = new OrderItem($cmd->productId, $cmd->quantity);

        $order->addItem($item); // Delegation

        $this->repository->save($order);
    }
}
```

### 3. Not Using Value Objects

**Why it's wrong**: Validation duplicated, primitive obsession, weak types.

**Example (WRONG)**:

```php
class Customer
{
    private string $email;

    public function __construct(string $email)
    {
        // Validation scattered
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException();
        }
        $this->email = $email;
    }

    public function changeEmail(string $newEmail): void
    {
        // Duplicate validation
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException();
        }
        $this->email = $newEmail;
    }
}
```

**Correct Approach**:

```php
// Value Object encapsulates validation
final readonly class Email
{
    public function __construct(private string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException("Invalid email: {$value}");
        }
    }

    public function value(): string { return $this->value; }
    public function domain(): string { return explode('@', $this->value)[1]; }
}

// Entity uses Value Object
class Customer
{
    private Email $email;

    public function __construct(Email $email)
    {
        $this->email = $email; // Already validated!
    }

    public function changeEmail(Email $newEmail): void
    {
        $this->email = $newEmail; // Already validated!
        $this->record(new CustomerEmailChanged($this->id, $newEmail));
    }
}
```

---

## Summary

This reference guide provides detailed explanations for implementing DDD architecture correctly. Always remember:

1. **Domain is sacred** - No external dependencies
2. **Handlers orchestrate** - Business logic in domain
3. **Use Value Objects** - Self-validating, type-safe
4. **Respect Deptrac** - Fix code, never config
5. **Rich models** - Behavior, not just data

For working code examples, see the [examples/ directory](examples/).

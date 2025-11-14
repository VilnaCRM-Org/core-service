<?php

declare(strict_types=1);

/**
 * Example: Fixing Common Deptrac Violations
 *
 * This file shows BEFORE and AFTER code for common architectural violations
 * and how to properly fix them.
 *
 * REMEMBER: NEVER change deptrac.yaml to bypass violations!
 * Always fix the code to respect architectural boundaries.
 */

// ============================================================================
// VIOLATION 1: Domain Entity Depending on Symfony Validator
// ============================================================================

/*
 * VIOLATION MESSAGE:
 * Domain must not depend on Symfony
 * src/Customer/Domain/Entity/Customer.php:15
 *   uses Symfony\Component\Validator\Constraints as Assert
 */

// ❌ WRONG - Domain depending on Symfony framework
namespace App\Customer\Domain\Entity;

use Symfony\Component\Validator\Constraints as Assert; // Framework in Domain!

class Customer
{
    #[Assert\Email] // Symfony validation in domain entity
    private string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    private string $name;
}

// ✅ CORRECT - Use Value Objects for validation
namespace App\Customer\Domain\Entity;

use App\Customer\Domain\ValueObject\Email; // Domain value object
use App\Customer\Domain\ValueObject\CustomerName; // Domain value object

class Customer
{
    private Email $email; // Email validates itself in constructor
    private CustomerName $name; // CustomerName validates itself in constructor

    public function __construct(Ulid $id, Email $email, CustomerName $name)
    {
        $this->id = $id;
        $this->email = $email; // Already validated by Email VO constructor
        $this->name = $name; // Already validated by CustomerName VO constructor
    }
}

// Value Objects handle validation
namespace App\Customer\Domain\ValueObject;

final readonly class Email
{
    private string $value;

    public function __construct(string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailException("Invalid email: {$value}");
        }
        $this->value = strtolower(trim($value));
    }

    public function value(): string { return $this->value; }
}

final readonly class CustomerName
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if (empty($trimmed)) {
            throw new InvalidCustomerNameException("Name cannot be empty");
        }
        if (strlen($trimmed) < 3 || strlen($trimmed) > 255) {
            throw new InvalidCustomerNameException("Name must be 3-255 characters");
        }
        $this->value = $trimmed;
    }

    public function value(): string { return $this->value; }
}

// If you need Symfony validation for API input, use DTOs in Application layer
namespace App\Customer\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert; // OK in Application layer

final class CreateCustomerDTO
{
    #[Assert\Email] // Symfony validation in DTO is fine
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $name;
}

// ============================================================================
// VIOLATION 2: Domain Entity with Doctrine Annotations
// ============================================================================

/*
 * VIOLATION MESSAGE:
 * Domain must not depend on Doctrine
 * src/Product/Domain/Entity/Product.php:10
 *   uses Doctrine\ODM\MongoDB\Mapping\Annotations as ODM
 */

// ❌ WRONG - Doctrine annotations in Domain entity
namespace App\Product\Domain\Entity;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM; // Doctrine in Domain!

#[ODM\Document(collection: 'products')] // Persistence concern in domain
class Product
{
    #[ODM\Id(type: 'ulid', strategy: 'NONE')]
    private Ulid $id;

    #[ODM\Field(type: 'string')]
    private string $name;

    #[ODM\Field(type: 'int')]
    private int $priceInCents;
}

// ✅ CORRECT - Pure domain entity with XML mappings
namespace App\Product\Domain\Entity;

// NO Doctrine imports - pure PHP!
class Product extends AggregateRoot
{
    private Ulid $id;
    private string $name;
    private int $priceInCents;

    // Pure business logic, no persistence concerns
    public function changePrice(int $newPriceInCents): void
    {
        if ($newPriceInCents < 0) {
            throw new InvalidPriceException("Price cannot be negative");
        }
        $this->priceInCents = $newPriceInCents;
        $this->record(new ProductPriceChanged($this->id, $newPriceInCents));
    }
}

/*
 * Doctrine mapping in config/doctrine/Product.orm.xml:
 *
 * <?xml version="1.0" encoding="UTF-8"?>
 * <doctrine-mapping>
 *     <document name="App\Product\Domain\Entity\Product" collection="products">
 *         <field name="id" type="ulid" id="true" strategy="NONE"/>
 *         <field name="name" type="string"/>
 *         <field name="priceInCents" type="int"/>
 *     </document>
 * </doctrine-mapping>
 */

// ============================================================================
// VIOLATION 3: Domain Entity with API Platform Attributes
// ============================================================================

/*
 * VIOLATION MESSAGE:
 * Domain must not depend on ApiPlatform
 * src/Customer/Domain/Entity/Customer.php:8
 *   uses ApiPlatform\Metadata\ApiResource
 */

// ❌ WRONG - API Platform in Domain entity
namespace App\Customer\Domain\Entity;

use ApiPlatform\Metadata\ApiResource; // API concern in Domain!
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;

#[ApiResource( // HTTP/API concern in domain
    operations: [
        new Get(),
        new Post()
    ]
)]
class Customer
{
    private Ulid $id;
    private string $name;
}

// ✅ CORRECT - Configure API Platform in Application layer or YAML

// Option 1: Pure domain entity
namespace App\Customer\Domain\Entity;

// NO API Platform imports - pure domain
class Customer extends AggregateRoot
{
    private Ulid $id;
    private string $name;

    public function changeName(string $newName): void
    {
        $this->ensureNameIsValid($newName);
        $this->name = $newName;
    }

    private function ensureNameIsValid(string $name): void
    {
        if (empty(trim($name))) {
            throw new InvalidCustomerNameException();
        }
    }
}

// Option 2A: API Platform config in config/packages/api_platform.yaml
/*
 * resources:
 *     App\Customer\Domain\Entity\Customer:
 *         operations:
 *             get:
 *                 method: GET
 *             post:
 *                 method: POST
 */

// Option 2B: Or use DTO with API Platform attributes (Application layer)
namespace App\Customer\Application\DTO;

use ApiPlatform\Metadata\ApiResource; // OK in Application layer
use ApiPlatform\Metadata\Post;

#[ApiResource(
    operations: [new Post(processor: CreateCustomerProcessor::class)]
)]
final class CreateCustomerDTO
{
    public string $name;
    public string $email;
}

// ============================================================================
// VIOLATION 4: Infrastructure Calling Application Handler Directly
// ============================================================================

/*
 * VIOLATION MESSAGE:
 * Infrastructure must not depend on Application (Command Handler)
 * src/Customer/Infrastructure/EventListener/CustomerListener.php:25
 */

// ❌ WRONG - Infrastructure calling handler directly
namespace App\Customer\Infrastructure\EventListener;

use App\Customer\Application\CommandHandler\SendWelcomeEmailHandler; // Wrong!
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;

class CustomerListener
{
    public function __construct(
        private SendWelcomeEmailHandler $handler // Direct dependency on handler
    ) {}

    public function postPersist(LifecycleEventArgs $args): void
    {
        $customer = $args->getObject();
        if ($customer instanceof Customer) {
            // Calling handler directly - wrong layer dependency!
            ($this->handler)(new SendWelcomeEmailCommand($customer->id()));
        }
    }
}

// ✅ CORRECT - Use Command Bus or Event Bus
namespace App\Customer\Infrastructure\EventListener;

use App\Shared\Domain\Bus\Command\CommandBusInterface; // Use bus
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;

class CustomerListener
{
    public function __construct(
        private CommandBusInterface $commandBus // Depend on bus, not handler
    ) {}

    public function postPersist(LifecycleEventArgs $args): void
    {
        $customer = $args->getObject();
        if ($customer instanceof Customer) {
            // Dispatch command via bus - bus finds the handler
            $this->commandBus->dispatch(
                new SendWelcomeEmailCommand($customer->id())
            );
        }
    }
}

// Even better - use Domain Events
namespace App\Customer\Domain\Entity;

class Customer extends AggregateRoot
{
    public function __construct(Ulid $id, Email $email, string $name)
    {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;

        // Record domain event - will be dispatched by infrastructure
        $this->record(new CustomerCreated($this->id, $this->email));
    }
}

// Event subscriber in Application layer handles it
namespace App\Customer\Application\EventSubscriber;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;

class SendWelcomeEmailOnCustomerCreated implements DomainEventSubscriberInterface
{
    public static function subscribedTo(): array
    {
        return [CustomerCreated::class];
    }

    public function __invoke(CustomerCreated $event): void
    {
        // Send email using event data
        $this->emailService->sendWelcome($event->customerId, $event->email);
    }
}

// ============================================================================
// VIOLATION 5: Anemic Domain Model (Not a Deptrac violation, but still wrong!)
// ============================================================================

// ❌ WRONG - Business logic in Application layer
namespace App\Customer\Application\CommandHandler;

class UpdateCustomerStatusHandler implements CommandHandlerInterface
{
    public function __invoke(UpdateCustomerStatusCommand $command): void
    {
        $customer = $this->repository->findById($command->customerId);

        // Business rules in handler - WRONG!
        if ($customer->getStatus() === 'active' && $command->newStatus === 'active') {
            throw new CustomerAlreadyActiveException();
        }

        if ($command->newStatus === 'inactive') {
            // More business logic in handler
            $customer->setStatus('inactive');
            $customer->setDeactivatedAt(new \DateTimeImmutable());
        }

        $this->repository->save($customer);
    }
}

// ✅ CORRECT - Business logic in Domain entity
namespace App\Customer\Domain\Entity;

class Customer extends AggregateRoot
{
    private CustomerStatus $status;
    private ?\DateTimeImmutable $deactivatedAt = null;

    // Business logic in domain method
    public function activate(): void
    {
        if ($this->status->isActive()) {
            throw new CustomerAlreadyActiveException(
                "Customer {$this->id} is already active"
            );
        }

        $this->status = CustomerStatus::active();
        $this->deactivatedAt = null;
        $this->record(new CustomerActivated($this->id));
    }

    public function deactivate(): void
    {
        if ($this->status->isInactive()) {
            throw new CustomerAlreadyInactiveException(
                "Customer {$this->id} is already inactive"
            );
        }

        $this->status = CustomerStatus::inactive();
        $this->deactivatedAt = new \DateTimeImmutable();
        $this->record(new CustomerDeactivated($this->id));
    }
}

// Handler only orchestrates
namespace App\Customer\Application\CommandHandler;

class UpdateCustomerStatusHandler implements CommandHandlerInterface
{
    public function __invoke(UpdateCustomerStatusCommand $command): void
    {
        $customer = $this->repository->findById($command->customerId);

        // Delegate to domain - business logic is there
        if ($command->newStatus === 'active') {
            $customer->activate();
        } else {
            $customer->deactivate();
        }

        $this->repository->save($customer);
    }
}

// ============================================================================
// COMPLETE WORKFLOW: Fixing a Violation
// ============================================================================

/*
 * STEP 1: Run Deptrac
 * $ make deptrac
 *
 * STEP 2: Read the violation carefully
 * Example output:
 * ---------------------------------------------------------------
 * Violation: Domain must not depend on Symfony
 * File: src/Customer/Domain/Entity/Customer.php:15
 * Violating code: uses Symfony\Component\Validator\Constraints as Assert
 * ---------------------------------------------------------------
 *
 * STEP 3: Understand the problem
 * - Customer entity is in Domain layer
 * - It's importing Symfony (framework)
 * - Domain must have NO external dependencies
 *
 * STEP 4: Plan the refactor
 * - Move validation to Value Objects
 * - If needed for API, create DTO in Application layer
 *
 * STEP 5: Refactor the code
 * - Create Email and CustomerName value objects
 * - Update Customer entity to use VOs
 * - Remove Symfony imports from Domain
 *
 * STEP 6: Verify the fix
 * $ make deptrac
 *
 * STEP 7: Ensure tests still pass
 * $ make unit-tests
 */

// ============================================================================
// KEY PRINCIPLES RECAP
// ============================================================================

/*
 * 1. NEVER MODIFY DEPTRAC.YAML TO FIX VIOLATIONS
 *    - deptrac.yaml defines the architecture
 *    - Violations mean code is in wrong layer
 *    - Fix the code, not the rules
 *
 * 2. LAYER DEPENDENCY RULES
 *    Domain → NOTHING (pure PHP)
 *    Application → Domain, Infrastructure, Symfony, ApiPlatform
 *    Infrastructure → Domain, Application, Symfony, Doctrine
 *
 * 3. DOMAIN LAYER IS SACRED
 *    - No framework imports
 *    - No persistence annotations
 *    - No HTTP/API concerns
 *    - Only business logic
 *
 * 4. USE VALUE OBJECTS FOR VALIDATION
 *    - Self-validating in constructor
 *    - Immutable
 *    - Type-safe
 *    - Reusable
 *
 * 5. USE DTOS FOR API VALIDATION
 *    - In Application layer
 *    - Can use Symfony validators
 *    - Transform to domain VOs in handlers
 *
 * 6. BUSINESS LOGIC BELONGS IN DOMAIN
 *    - Not in handlers
 *    - Not in repositories
 *    - Not in controllers/processors
 *    - In entities and value objects
 *
 * 7. USE BUSES FOR CROSS-LAYER COMMUNICATION
 *    - Command Bus for commands
 *    - Event Bus for events
 *    - Don't call handlers directly
 */

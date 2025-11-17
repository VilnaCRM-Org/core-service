<?php

declare(strict_types=1);

/**
 * Example 3: Fixing Domain → API Platform Attribute Violations
 *
 * VIOLATION:
 * Domain must not depend on ApiPlatform
 *   src/Customer/Domain/Entity/Customer.php:8
 *     uses ApiPlatform\Metadata\ApiResource
 */

// ============================================================================
// BEFORE (WRONG) - Domain entity with API Platform attributes
// ============================================================================

namespace App\Customer\Domain\Entity;

use ApiPlatform\Metadata\ApiResource;       // VIOLATION!
use ApiPlatform\Metadata\Get;               // VIOLATION!
use ApiPlatform\Metadata\GetCollection;     // VIOLATION!
use ApiPlatform\Metadata\Post;              // VIOLATION!
use ApiPlatform\Metadata\Put;               // VIOLATION!
use ApiPlatform\Metadata\Delete;            // VIOLATION!
use ApiPlatform\Metadata\ApiProperty;       // VIOLATION!
use ApiPlatform\Metadata\ApiFilter;         // VIOLATION!
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;  // VIOLATION!
use Symfony\Component\Serializer\Annotation\Groups; // Also a violation!
use App\Shared\Domain\ValueObject\Ulid;

#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete(),
    ],
    normalizationContext: ['groups' => ['customer:read']],
    denormalizationContext: ['groups' => ['customer:write']],
    paginationItemsPerPage: 30
)]
#[ApiFilter(SearchFilter::class, properties: ['email' => 'exact', 'name' => 'partial'])]
class CustomerBefore
{
    #[ApiProperty(identifier: true)]
    #[Groups(['customer:read'])]
    private Ulid $id;

    #[Groups(['customer:read', 'customer:write'])]
    private string $email;

    #[Groups(['customer:read', 'customer:write'])]
    private string $name;

    #[Groups(['customer:read'])]
    private \DateTimeImmutable $createdAt;
}

// ============================================================================
// AFTER - OPTION 1: YAML Configuration (Recommended)
// ============================================================================

namespace App\Customer\Domain\Entity;

use App\Customer\Domain\ValueObject\Email;
use App\Customer\Domain\ValueObject\CustomerName;
use App\Shared\Domain\Aggregate\AggregateRoot;
use App\Shared\Domain\ValueObject\Ulid;

/**
 * Pure domain entity - NO API Platform imports!
 */
final class Customer extends AggregateRoot
{
    private Ulid $id;
    private Email $email;
    private CustomerName $name;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        Ulid $id,
        Email $email,
        CustomerName $name,
        \DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
        $this->createdAt = $createdAt;
    }

    // Pure business methods...

    public function id(): Ulid
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function name(): CustomerName
    {
        return $this->name;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

// ============================================================================
// YAML CONFIGURATION - config/api_platform/resources/Customer.yaml
// ============================================================================

/*
# config/api_platform/resources/Customer.yaml

resources:
  App\Customer\Domain\Entity\Customer:
    shortName: Customer
    description: Customer resource

    operations:
      ApiPlatform\Metadata\Get:
        uriTemplate: /customers/{id}
        requirements:
          id: .+

      ApiPlatform\Metadata\GetCollection:
        uriTemplate: /customers
        paginationItemsPerPage: 30

      ApiPlatform\Metadata\Post:
        uriTemplate: /customers
        processor: App\Customer\Application\Processor\CreateCustomerProcessor

      ApiPlatform\Metadata\Put:
        uriTemplate: /customers/{id}
        processor: App\Customer\Application\Processor\UpdateCustomerProcessor

      ApiPlatform\Metadata\Delete:
        uriTemplate: /customers/{id}

    normalizationContext:
      groups: ['customer:read']

    denormalizationContext:
      groups: ['customer:write']

# config/packages/api_platform.yaml

api_platform:
  mapping:
    paths:
      - '%kernel.project_dir%/config/api_platform'
  defaults:
    pagination_enabled: true
    pagination_items_per_page: 30
*/

// ============================================================================
// SERIALIZATION GROUPS - config/serialization/Customer.yaml
// ============================================================================

/*
# config/serialization/Customer.yaml

App\Customer\Domain\Entity\Customer:
  attributes:
    id:
      groups: ['customer:read']
    email:
      groups: ['customer:read', 'customer:write']
    name:
      groups: ['customer:read', 'customer:write']
    createdAt:
      groups: ['customer:read']
*/

// ============================================================================
// AFTER - OPTION 2: Application Layer DTO (For complex transformations)
// ============================================================================

namespace App\Customer\Application\DTO;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiProperty;
use App\Customer\Application\Processor\CreateCustomerProcessor;
use App\Customer\Application\Processor\UpdateCustomerProcessor;
use App\Customer\Application\Provider\CustomerProvider;
use App\Customer\Application\Provider\CustomerCollectionProvider;

/**
 * Application layer DTO - CAN use API Platform!
 * This is a representation for the API, not the domain entity.
 */
#[ApiResource(
    shortName: 'Customer',
    operations: [
        new Get(
            provider: CustomerProvider::class
        ),
        new GetCollection(
            provider: CustomerCollectionProvider::class,
            paginationItemsPerPage: 30
        ),
        new Post(
            processor: CreateCustomerProcessor::class,
            input: CreateCustomerInput::class
        ),
        new Put(
            processor: UpdateCustomerProcessor::class,
            input: UpdateCustomerInput::class
        ),
        new Delete(),
    ]
)]
final class CustomerResource
{
    #[ApiProperty(identifier: true)]
    public string $id;

    public string $email;

    public string $name;

    public \DateTimeImmutable $createdAt;

    /**
     * Factory method to create DTO from domain entity
     */
    public static function fromEntity(\App\Customer\Domain\Entity\Customer $customer): self
    {
        $dto = new self();
        $dto->id = $customer->id()->value();
        $dto->email = $customer->email()->value();
        $dto->name = $customer->name()->value();
        $dto->createdAt = $customer->createdAt();

        return $dto;
    }
}

// ============================================================================
// INPUT DTO - For write operations
// ============================================================================

namespace App\Customer\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateCustomerInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    public string $name;
}

final class UpdateCustomerInput
{
    #[Assert\Email]
    public ?string $email = null;

    #[Assert\Length(min: 2, max: 100)]
    public ?string $name = null;
}

// ============================================================================
// STATE PROVIDER - Application Layer
// ============================================================================

namespace App\Customer\Application\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Customer\Application\DTO\CustomerResource;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\ValueObject\Ulid;

final readonly class CustomerProvider implements ProviderInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?CustomerResource
    {
        $id = new Ulid($uriVariables['id']);
        $customer = $this->repository->findById($id);

        if ($customer === null) {
            return null;
        }

        return CustomerResource::fromEntity($customer);
    }
}

// ============================================================================
// STATE PROCESSOR - Application Layer
// ============================================================================

namespace App\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Customer\Application\Command\CreateCustomerCommand;
use App\Customer\Application\DTO\CreateCustomerInput;
use App\Customer\Application\DTO\CustomerResource;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\ValueObject\Ulid;

final readonly class CreateCustomerProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CustomerRepositoryInterface $repository
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CustomerResource
    {
        /** @var CreateCustomerInput $data */
        $id = Ulid::random();

        $command = new CreateCustomerCommand(
            $id,
            $data->email,
            $data->name
        );

        $this->commandBus->dispatch($command);

        // Fetch and return created entity
        $customer = $this->repository->findById($id);

        return CustomerResource::fromEntity($customer);
    }
}

// ============================================================================
// KEY DIFFERENCES:
//
// OPTION 1 (YAML):
// - Simpler, less code
// - Direct mapping of domain entity
// - Good for simple CRUD
// - Serialization groups in YAML
//
// OPTION 2 (DTO):
// - More control over representation
// - Clear separation between domain and API
// - Custom transformations
// - Different input/output shapes
// - Recommended for complex APIs
//
// BOTH OPTIONS:
// - Domain entity remains pure
// - No API Platform imports in domain
// - Deptrac passes with zero violations
// ============================================================================

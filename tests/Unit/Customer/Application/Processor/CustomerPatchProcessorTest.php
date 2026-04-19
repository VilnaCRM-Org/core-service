<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Application\DTO\CustomerPatch;
use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Core\Customer\Application\Processor\CustomerPatchProcessor;
use App\Core\Customer\Application\Resolver\CustomerPatchUpdateResolver;
use App\Core\Customer\Application\Resolver\CustomerReferenceResolverInterface;
use App\Core\Customer\Application\Resolver\CustomerUpdateScalarResolver;
use App\Core\Customer\Application\Transformer\CustomerRelationTransformer;
use App\Core\Customer\Application\Transformer\CustomerRelationTransformerInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerNotFoundException;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Application\Extractor\PatchUlidExtractor;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Ulid;

final class CustomerPatchProcessorTest extends UnitTestCase
{
    private CommandBusInterface|MockObject $commandBus;
    private UpdateCustomerCommandFactoryInterface|MockObject $factory;
    private CustomerReferenceResolverInterface|MockObject $referenceResolver;
    private CustomerRelationTransformerInterface $relationTransformer;
    private CustomerRepositoryInterface|MockObject $repository;
    private CustomerPatchUpdateResolver $patchUpdateResolver;
    private PatchUlidExtractor $patchUlidExtractor;
    private CustomerPatchProcessor $processor;
    private UlidFactory $ulidFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->factory = $this
            ->createMock(UpdateCustomerCommandFactoryInterface::class);
        $this->referenceResolver = $this->createMock(
            CustomerReferenceResolverInterface::class
        );
        $this->repository = $this
            ->createMock(CustomerRepositoryInterface::class);
        $this->ulidFactory = new UlidFactory();

        $this->relationTransformer = new CustomerRelationTransformer(
            $this->referenceResolver
        );

        $this->patchUpdateResolver = new CustomerPatchUpdateResolver(
            new CustomerUpdateScalarResolver(),
            $this->relationTransformer
        );
        $this->patchUlidExtractor = new PatchUlidExtractor();
        $this->processor = new CustomerPatchProcessor(
            $this->repository,
            $this->commandBus,
            $this->factory,
            $this->patchUpdateResolver,
            $this->patchUlidExtractor,
            $this->ulidFactory
        );
    }

    public function testProcessUpdatesAndDispatchesCommand(): void
    {
        [$dto, $operation, $uriVars, $customer] = $this->prepareProcessData(
            'Original Name',
            'original@example.com',
            '+123456789',
            'Original Source',
            true,
            $this->createDto()
        );
        $result = $this->processor->process($dto, $operation, $uriVars);
        $this->assertSame($customer, $result);
    }

    public function testProcessPreservesExistingValuesWhenNull(): void
    {
        $dto = $this->createEmptyDto();
        $exData = [
            'initials' => 'Original Name',
            'email' => 'original@example.com',
            'phone' => '+123456789',
            'leadSource' => 'Original Source',
            'confirmed' => true,
        ];
        [$dto, $operation, $uriVars, $customer] = $this
            ->prepareProcessPreserveData($dto, $exData);
        $result = $this->processor->process($dto, $operation, $uriVars);
        $this->assertSame($customer, $result);
    }

    public function testProcessWithPartialData(): void
    {
        $partial = new CustomerPatch(
            id: null,
            initials: 'New Name',
            email: null,
            phone: null,
            leadSource: 'New Source',
            type: null,
            status: null,
            confirmed: null,
        );

        $exData = [
            'initials' => 'Original Name',
            'email' => 'original@example.com',
            'phone' => '+123456789',
            'leadSource' => 'Original Source',
            'confirmed' => true,
        ];
        [$dto, $operation, $uriVars, $customer] = $this
            ->prepareProcessPreserveData($partial, $exData);
        $result = $this->processor->process($dto, $operation, $uriVars);
        $this->assertSame($customer, $result);
    }

    public function testProcessThrowsExceptionWhenCustomerNotFound(): void
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);
        $ulidStr = (string) $this->faker->ulid();
        $ulid = new Ulid($ulidStr);
        $uriVars = ['ulid' => $ulidStr];

        $this->repository->expects($this->once())
            ->method('findFresh')
            ->with((string) $ulid)
            ->willReturn(null);

        $this->expectException(CustomerNotFoundException::class);
        $this->processor->process($dto, $operation, $uriVars);
    }

    public function testProcessWithGraphQLPathExtractsUlidFromIri(): void
    {
        $ulidStr = (string) $this->faker->ulid();
        $iri = sprintf('/api/customers/%s', $ulidStr);
        $initials = $this->faker->randomElement(['Mr', 'Ms', 'Mrs', 'Dr']);
        $email = $this->faker->email();
        $phone = $this->faker->phoneNumber();
        $leadSource = $this->faker->word();
        $confirmed = $this->faker->boolean();
        $dto = new CustomerPatch(
            id: $iri,
            initials: $initials,
            email: $email,
            phone: $phone,
            leadSource: $leadSource,
            type: null,
            status: null,
            confirmed: $confirmed,
        );

        $operation = $this->createMock(Operation::class);
        $customer = $this->createMock(Customer::class);
        $customerType = $this->createMock(CustomerType::class);
        $customerStatus = $this->createMock(CustomerStatus::class);
        $ulid = new Ulid($ulidStr);
        $command = $this->createMock(UpdateCustomerCommand::class);

        $this->setupRepository($ulid, $customer);
        $this->setupCustomer(
            $customer,
            $initials,
            $email,
            $phone,
            $leadSource,
            $confirmed,
            $customerType,
            $customerStatus
        );

        $this->factory->expects($this->once())
            ->method('create')
            ->willReturn($command);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $result = $this->processor->process($dto, $operation);

        $this->assertSame($customer, $result);
    }

    public function testProcessThrowsExceptionWhenNoUlidProvided(): void
    {
        $dto = new CustomerPatch(
            id: null,
            initials: $this->faker->randomElement(['Mr', 'Ms', 'Mrs', 'Dr']),
            email: $this->faker->email(),
            phone: $this->faker->phoneNumber(),
            leadSource: $this->faker->word(),
            type: null,
            status: null,
            confirmed: $this->faker->boolean(),
        );

        $operation = $this->createMock(Operation::class);

        $this->expectException(CustomerNotFoundException::class);

        $this->processor->process($dto, $operation);
    }

    private function setupRepository(Ulid $ulid, Customer $customer): void
    {
        $this->repository->expects($this->once())
            ->method('findFresh')
            ->with((string) $ulid)
            ->willReturn($customer);
    }

    private function setupCustomer(
        MockObject $customer,
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        bool $confirmed,
        ?CustomerType $type = null,
        ?CustomerStatus $status = null
    ): void {
        $type ??= $this->createMock(CustomerType::class);
        $status ??= $this->createMock(CustomerStatus::class);

        $customer->method('getInitials')->willReturn($initials);
        $customer->method('getEmail')->willReturn($email);
        $customer->method('getPhone')->willReturn($phone);
        $customer->method('getLeadSource')->willReturn($leadSource);
        $customer->method('isConfirmed')->willReturn($confirmed);
        $customer->method('getType')->willReturn($type);
        $customer->method('getStatus')->willReturn($status);
    }

    private function setupReferenceResolver(
        CustomerPatch $dto,
        CustomerType $resolvedType,
        CustomerStatus $resolvedStatus
    ): void {
        if ($dto->type !== null) {
            $this->referenceResolver
                ->expects(self::once())
                ->method('resolveType')
                ->with($dto->type)
                ->willReturn($resolvedType);
        }

        if ($dto->status !== null) {
            $this->referenceResolver
                ->expects(self::once())
                ->method('resolveStatus')
                ->with($dto->status)
                ->willReturn($resolvedStatus);
        }
    }

    private function isUpdateValid(
        object $update,
        CustomerPatch $dto,
        CustomerType $type,
        CustomerStatus $status,
        Customer $customer
    ): bool {
        $expected = [
            'newInitials' => $dto->initials ?? $customer->getInitials(),
            'newEmail' => $dto->email ?? $customer->getEmail(),
            'newPhone' => $dto->phone ?? $customer->getPhone(),
            'newLeadSource' => $dto->leadSource ?? $customer->getLeadSource(),
            'newType' => $type,
            'newStatus' => $status,
            'newConfirmed' => $dto->confirmed ?? $customer->isConfirmed(),
        ];
        return get_object_vars($update) === $expected;
    }

    private function setupDependencies(
        CustomerPatch $dto,
        CustomerType $type,
        CustomerStatus $status,
        Customer $customer,
        UpdateCustomerCommand $command
    ): void {
        $this->factory->expects($this->once())
            ->method('create')
            ->with(
                $customer,
                $this->callback(fn ($update) => $this
                    ->isUpdateValid($update, $dto, $type, $status, $customer))
            )
            ->willReturn($command);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }

    private function createDto(): CustomerPatch
    {
        return new CustomerPatch(
            id: null,
            initials: $this->faker->name(),
            email: $this->faker->email(),
            phone: $this->faker->phoneNumber(),
            leadSource: $this->faker->word(),
            type: '/api/customer_types/' . $this->faker->ulid(),
            status: '/api/customer_statuses/' . $this->faker->ulid(),
            confirmed: $this->faker->boolean(),
        );
    }

    private function createEmptyDto(): CustomerPatch
    {
        return new CustomerPatch(
            id: null,
            initials: null,
            email: null,
            phone: null,
            leadSource: null,
            type: null,
            status: null,
            confirmed: null,
        );
    }

    /**
     * @return array{CustomerPatch, Operation, array<string,string>, Customer}
     */
    private function prepareProcessData(
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        bool $confirmed,
        CustomerPatch $dto
    ): array {
        $operation = $this->createMock(Operation::class);
        $ulidStr = (string) $this->faker->ulid();
        $ulid = new Ulid($ulidStr);
        $uriVars = ['ulid' => $ulidStr];
        $currentType = $this->createMock(CustomerType::class);
        $currentStatus = $this->createMock(CustomerStatus::class);
        $resolvedType = $this->createMock(CustomerType::class);
        $resolvedStatus = $this->createMock(CustomerStatus::class);
        $customer = $this->createMock(Customer::class);
        $command = $this->createMock(UpdateCustomerCommand::class);
        $this->setupCustomer(
            $customer,
            $initials,
            $email,
            $phone,
            $leadSource,
            $confirmed,
            $currentType,
            $currentStatus
        );
        $this->setupRepository($ulid, $customer);
        $this->setupReferenceResolver(
            $dto,
            $resolvedType,
            $resolvedStatus
        );
        $this->setupDependencies(
            $dto,
            $resolvedType,
            $resolvedStatus,
            $customer,
            $command
        );
        return [$dto, $operation, $uriVars, $customer];
    }

    /**
     * @param array<string, string|bool> $exData
     *
     * @return array{CustomerPatch, Operation, array<string, string>, Customer}
     */
    private function prepareProcessPreserveData(
        CustomerPatch $dto,
        array $exData
    ): array {
        [$operation, $uriVars, $ulid] = $this->createOperationContext();
        $existingType = $this->createMock(CustomerType::class);
        $existingStatus = $this->createMock(CustomerStatus::class);
        $resolvedType = $this->createMock(CustomerType::class);
        $resolvedStatus = $this->createMock(CustomerStatus::class);
        $customer = $this->createMock(Customer::class);
        $this->setupCustomer(
            $customer,
            $exData['initials'],
            $exData['email'],
            $exData['phone'],
            $exData['leadSource'],
            $exData['confirmed'],
            $existingType,
            $existingStatus,
        );
        $this->setupRepository($ulid, $customer);
        $this->setupReferenceResolver(
            $dto,
            $resolvedType,
            $resolvedStatus
        );
        $this->expectUpdateCommand(
            $dto,
            $dto->type === null ? $existingType : $resolvedType,
            $dto->status === null ? $existingStatus : $resolvedStatus,
            $customer
        );
        return [$dto, $operation, $uriVars, $customer];
    }

    /**
     * @return array{Operation, array<string, string>, Ulid}
     */
    private function createOperationContext(): array
    {
        $operation = $this->createMock(Operation::class);
        $ulidStr = (string) $this->faker->ulid();
        $ulid = new Ulid($ulidStr);
        $uriVars = ['ulid' => $ulidStr];
        return [$operation, $uriVars, $ulid];
    }

    private function expectUpdateCommand(
        CustomerPatch $dto,
        CustomerType $type,
        CustomerStatus $status,
        Customer $customer
    ): void {
        $command = $this->createMock(UpdateCustomerCommand::class);
        $this->factory->expects($this->once())
            ->method('create')
            ->with(
                $customer,
                $this->callback(
                    fn ($update) => $this->isUpdateValid(
                        $update,
                        $dto,
                        $type,
                        $status,
                        $customer
                    )
                )
            )
            ->willReturn($command);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }
}

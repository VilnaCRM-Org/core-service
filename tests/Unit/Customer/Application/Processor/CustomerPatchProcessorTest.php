<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Application\DTO\CustomerPatch;
use App\Core\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Core\Customer\Application\Processor\CustomerPatchProcessor;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerNotFoundException;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CustomerPatchProcessorTest extends UnitTestCase
{
    private CommandBusInterface|MockObject $commandBus;
    private UpdateCustomerCommandFactoryInterface|MockObject $factory;
    private IriConverterInterface|MockObject $iriConverter;
    private CustomerRepositoryInterface|MockObject $repository;
    private CustomerPatchProcessor $processor;
    private UlidFactory $ulidFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeMocks();
        $this->processor = new CustomerPatchProcessor(
            $this->repository,
            $this->commandBus,
            $this->factory,
            $this->iriConverter,
            $this->ulidFactory
        );
    }

    public function testProcessUpdatesAndDispatchesCommand(): void
    {
        [$dto, $operation, $uriVars, $customer] = $this->prepareTestData();
        $result = $this->processor->process($dto, $operation, $uriVars);
        $this->assertSame($customer, $result);
    }

    public function testProcessPreservesExistingValuesWhenNull(): void
    {
        $dto = $this->createEmptyDto();
        [$dto, $operation, $uriVars, $customer] = $this
            ->preparePreserveData($dto);
        $result = $this->processor->process($dto, $operation, $uriVars);
        $this->assertSame($customer, $result);
    }

    public function testProcessWithPartialData(): void
    {
        $partial = $this->createPartialDto();
        [$dto, $operation, $uriVars, $customer] = $this
            ->preparePreserveData($partial);
        $result = $this->processor->process($dto, $operation, $uriVars);
        $this->assertSame($customer, $result);
    }

    public function testProcessThrowsExceptionWhenCustomerNotFound(): void
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);
        $ulidStr = (string) $this->faker->ulid();
        $ulid = $this->ulidFactory->create($ulidStr);
        $uriVars = ['ulid' => $ulidStr];

        $this->repository->expects($this->once())
            ->method('find')
            ->with($ulid)
            ->willReturn(null);

        $this->expectException(CustomerNotFoundException::class);
        $this->processor->process($dto, $operation, $uriVars);
    }

    private function initializeMocks(): void
    {
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->factory = $this
            ->createMock(UpdateCustomerCommandFactoryInterface::class);
        $this->iriConverter = $this->createMock(IriConverterInterface::class);
        $this->repository = $this
            ->createMock(CustomerRepositoryInterface::class);
        $this->ulidFactory = new UlidFactory();
    }

    private function setupRepository(string $ulidStr, Customer $customer): void
    {
        $ulid = $this->ulidFactory->create($ulidStr);
        $this->repository->expects($this->once())
            ->method('find')
            ->with($ulid)
            ->willReturn($customer);
    }

    /**
     * @param array<string, string|bool> $data
     */
    private function setupCustomer(
        MockObject $customer,
        array $data,
        ?CustomerType $type = null,
        ?CustomerStatus $status = null
    ): void {
        $customer->method('getInitials')->willReturn($data['initials']);
        $customer->method('getEmail')->willReturn($data['email']);
        $customer->method('getPhone')->willReturn($data['phone']);
        $customer->method('getLeadSource')->willReturn($data['leadSource']);
        $customer->method('isConfirmed')->willReturn($data['confirmed']);

        if ($type !== null) {
            $customer->method('getType')->willReturn($type);
        }
        if ($status !== null) {
            $customer->method('getStatus')->willReturn($status);
        }
    }

    private function setupIriConverter(
        CustomerPatch $dto,
        CustomerType $type,
        CustomerStatus $status
    ): void {
        $this->iriConverter->expects($this->atLeastOnce())
            ->method('getResourceFromIri')
            ->willReturnCallback(
                fn (string $iri) => $this->resolveIri(
                    $iri,
                    $dto,
                    $type,
                    $status
                )
            );
    }

    private function resolveIri(
        string $iri,
        CustomerPatch $dto,
        CustomerType $type,
        CustomerStatus $status
    ): CustomerType|CustomerStatus {
        $mapping = [$dto->type => $type, $dto->status => $status];
        return $mapping[$iri] ??
            throw new \InvalidArgumentException('Unexpected IRI');
    }

    private function createDto(): CustomerPatch
    {
        return new CustomerPatch(
            initials: $this->faker->name(),
            email: $this->faker->email(),
            phone: $this->faker->phoneNumber(),
            leadSource: $this->faker->word(),
            type: '/api/customer_types/' . $this->faker->ulid(),
            status: '/api/customer_statuses/' . $this->faker->ulid(),
            confirmed: $this->faker->boolean()
        );
    }

    private function createEmptyDto(): CustomerPatch
    {
        return new CustomerPatch(
            initials: null,
            email: null,
            phone: null,
            leadSource: null,
            type: null,
            status: null,
            confirmed: null
        );
    }

    private function createPartialDto(): CustomerPatch
    {
        return new CustomerPatch(
            initials: 'New Name',
            email: null,
            phone: null,
            leadSource: 'New Source',
            type: null,
            status: null,
            confirmed: null,
        );
    }

    /**
     * @param array<string, string|bool> $data
     */
    private function createMockCustomer(array $data): MockObject
    {
        $customer = $this->createMock(Customer::class);
        $this->setupCustomer($customer, $data);
        return $customer;
    }

    /**
     * @return array<CustomerType|CustomerStatus>
     */
    private function createTestMocks(): array
    {
        return [
            $this->createMock(CustomerType::class),
            $this->createMock(CustomerStatus::class),
        ];
    }

    private function createUpdateCommand(
        CustomerPatch $dto,
        CustomerType $type,
        CustomerStatus $status,
        Customer $customer
    ): UpdateCustomerCommand {
        $customerUpdate = new CustomerUpdate(
            newInitials: $dto->initials ?? $customer->getInitials(),
            newEmail: $dto->email ?? $customer->getEmail(),
            newPhone: $dto->phone ?? $customer->getPhone(),
            newLeadSource: $dto->leadSource ?? $customer->getLeadSource(),
            newType: $dto->type ? $type : $customer->getType(),
            newStatus: $dto->status ? $status : $customer->getStatus(),
            newConfirmed: $dto->confirmed ?? $customer->isConfirmed(),
        );

        return new UpdateCustomerCommand($customer, $customerUpdate);
    }

    private function setupFactoryAndBus(UpdateCustomerCommand $command): void
    {
        $this->factory->expects($this->once())
            ->method('create')
            ->willReturn($command);
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }

    /**
     * @return array{CustomerPatch, Operation, array<string,string>, Customer}
     */
    private function prepareTestData(): array
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);
        $ulidStr = (string) $this->faker->ulid();
        $uriVars = ['ulid' => $ulidStr];

        [$type, $status] = $this->createTestMocks();
        $customerData = $this->getDefaultCustomerData();
        $customer = $this->createMockCustomer($customerData);

        $this->setupRepository($ulidStr, $customer);
        $this->setupIriConverter($dto, $type, $status);

        $command = $this->createUpdateCommand($dto, $type, $status, $customer);
        $this->setupFactoryAndBus($command);

        return [$dto, $operation, $uriVars, $customer];
    }

    /**
     * @return array{CustomerPatch, Operation, array<string, string>, Customer}
     */
    private function preparePreserveData(CustomerPatch $dto): array
    {
        $operation = $this->createMock(Operation::class);
        $ulidStr = (string) $this->faker->ulid();
        $uriVars = ['ulid' => $ulidStr];

        [$type, $status] = $this->createTestMocks();
        $customerData = $this->getDefaultCustomerData();
        $customer = $this->createMockCustomer($customerData);
        $this->setupCustomer($customer, $customerData, $type, $status);

        $this->setupRepository($ulidStr, $customer);

        $command = $this->createUpdateCommand($dto, $type, $status, $customer);
        $this->setupFactoryAndBus($command);

        return [$dto, $operation, $uriVars, $customer];
    }

    /**
     * @return array<string, string|bool>
     */
    private function getDefaultCustomerData(): array
    {
        return [
            'initials' => 'Original Name',
            'email' => 'original@example.com',
            'phone' => '+123456789',
            'leadSource' => 'Original Source',
            'confirmed' => true,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use App\Customer\Application\Command\UpdateCustomerCommand;
use App\Customer\Application\DTO\CustomerPatchDto;
use App\Customer\Application\Factory\UpdateCustomerCommandFactoryInterface;
use App\Customer\Application\Processor\CustomerPatchProcessor;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Exception\CustomerNotFoundException;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Ulid;

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
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->factory = $this
            ->createMock(UpdateCustomerCommandFactoryInterface::class);
        $this->iriConverter = $this->createMock(IriConverterInterface::class);
        $this->repository = $this
            ->createMock(CustomerRepositoryInterface::class);
        $this->ulidFactory = new UlidFactory();
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
        $partial = new CustomerPatchDto(
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
            ->method('find')
            ->with((string) $ulid)
            ->willReturn(null);

        $this->expectException(CustomerNotFoundException::class);
        $this->processor->process($dto, $operation, $uriVars);
    }

    private function setupRepository(Ulid $ulid, Customer $customer): void
    {
        $this->repository->expects($this->once())
            ->method('find')
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
        $customer->method('getInitials')->willReturn($initials);
        $customer->method('getEmail')->willReturn($email);
        $customer->method('getPhone')->willReturn($phone);
        $customer->method('getLeadSource')->willReturn($leadSource);
        $customer->method('isConfirmed')->willReturn($confirmed);
        array_map(
            static fn (array $cfg) => $customer
                ->method($cfg['method'])->willReturn($cfg['value']),
            array_filter(
                [
                    ['value' => $type, 'method' => 'getType'],
                    ['value' => $status, 'method' => 'getStatus'],
                ],
                static fn (array $cfg) => $cfg['value'] !== null
            )
        );
    }

    private function setupIriConverter(
        CustomerPatchDto $dto,
        CustomerType $type,
        CustomerStatus $status
    ): void {
        $this->iriConverter->expects($this->atLeastOnce())
            ->method('getResourceFromIri')
            ->willReturnCallback(fn (string $iri) => $this
                ->resolveIri($iri, $dto, $type, $status));
    }

    private function resolveIri(
        string $iri,
        CustomerPatchDto $dto,
        CustomerType $type,
        CustomerStatus $status
    ): CustomerType|CustomerStatus {
        $mapping = array_filter(
            [
                $dto->type => $type,
                $dto->status => $status,
            ],
            static fn ($_, $key) => $key !== null,
            ARRAY_FILTER_USE_BOTH
        );
        return $mapping[$iri] ??
            throw new \InvalidArgumentException('Unexpected IRI');
    }

    private function isUpdateValid(
        object $update,
        CustomerPatchDto $dto,
        CustomerType $type,
        CustomerStatus $status,
        Customer $customer
    ): bool {
        $expected = [
            'newInitials' => $dto->initials ?? $customer->getInitials(),
            'newEmail' => $dto->email ?? $customer->getEmail(),
            'newPhone' => $dto->phone ?? $customer->getPhone(),
            'newLeadSource' => $dto->leadSource ?? $customer->getLeadSource(),
            'newType' => $dto->type ? $type : $customer->getType(),
            'newStatus' => $dto->status ? $status : $customer->getStatus(),
            'newConfirmed' => $dto->confirmed ?? $customer->isConfirmed(),
        ];
        return get_object_vars($update) === $expected;
    }

    private function setupDependencies(
        CustomerPatchDto $dto,
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

    private function createDto(): CustomerPatchDto
    {
        return new CustomerPatchDto(
            initials: $this->faker->name(),
            email: $this->faker->email(),
            phone: $this->faker->phoneNumber(),
            leadSource: $this->faker->word(),
            type: '/api/customer_types/' . $this->faker->ulid(),
            status: '/api/customer_statuses/' . $this->faker->ulid(),
            confirmed: $this->faker->boolean()
        );
    }

    private function createEmptyDto(): CustomerPatchDto
    {
        return new CustomerPatchDto(
            initials: null,
            email: null,
            phone: null,
            leadSource: null,
            type: null,
            status: null,
            confirmed: null
        );
    }

    /**
     * @return array<string, CustomerPatchDto>
     */
    private function prepareProcessData(
        string $initials,
        string $email,
        string $phone,
        string $leadSource,
        bool $confirmed,
        CustomerPatchDto $dto
    ): array {
        $operation = $this->createMock(Operation::class);
        $ulidStr = (string) $this->faker->ulid();
        $ulid = new Ulid($ulidStr);
        $uriVars = ['ulid' => $ulidStr];
        $type = $this->createMock(CustomerType::class);
        $status = $this->createMock(CustomerStatus::class);
        $customer = $this->createMock(Customer::class);
        $command = $this->createMock(UpdateCustomerCommand::class);
        $this->setupCustomer(
            $customer,
            $initials,
            $email,
            $phone,
            $leadSource,
            $confirmed
        );
        $this->setupRepository($ulid, $customer);
        $this->setupIriConverter($dto, $type, $status);
        $this->setupDependencies($dto, $type, $status, $customer, $command);
        return [$dto, $operation, $uriVars, $customer];
    }

    /**
     * @param array<CustomerPatchDto> $exData
     *
     * @return array<CustomerPatchDto, string>
     */
    private function prepareProcessPreserveData(
        CustomerPatchDto $dto,
        array $exData
    ): array {
        [$operation, $uriVars, $ulid] = $this->createOperationContext();
        $exType = $this->createMock(CustomerType::class);
        $exStatus = $this->createMock(CustomerStatus::class);
        $customer = $this->createMock(Customer::class);
        $this->setupCustomer(
            $customer,
            $exData['initials'],
            $exData['email'],
            $exData['phone'],
            $exData['leadSource'],
            $exData['confirmed'],
            $exType,
            $exStatus,
        );
        $this->setupRepository($ulid, $customer);
        $this->expectUpdateCommand($dto, $exType, $exStatus, $customer);
        return [$dto, $operation, $uriVars, $customer];
    }

    /**
     * @return array<Operation, Ulid, string>
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
        CustomerPatchDto $dto,
        CustomerType $type,
        CustomerStatus $status,
        Customer $customer
    ): UpdateCustomerCommand {
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
        return $command;
    }
}

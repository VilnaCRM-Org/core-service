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
use App\Shared\Application\Validator\StringFieldValidator;
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
        $fieldResolver = new StringFieldValidator();
        $this->processor = new CustomerPatchProcessor(
            $this->repository,
            $this->commandBus,
            $this->factory,
            $this->iriConverter,
            $this->ulidFactory,
            $fieldResolver
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
        $partial = new CustomerPatch();
        $partial->initials = 'New Name';
        $partial->email = null;
        $partial->phone = null;
        $partial->leadSource = 'New Source';
        $partial->type = null;
        $partial->status = null;
        $partial->confirmed = null;

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

    public function testProcessWithGraphQLPathExtractsUlidFromIri(): void
    {
        $ulidStr = (string) $this->faker->ulid();
        $iri = sprintf('/api/customers/%s', $ulidStr);
        $dto = new CustomerPatch();
        $dto->id = $iri;
        $dto->initials = $this->faker->randomElement(['Mr', 'Ms', 'Mrs', 'Dr']);
        $dto->email = $this->faker->email();
        $dto->phone = $this->faker->phoneNumber();
        $dto->leadSource = $this->faker->word();
        $dto->type = null;
        $dto->status = null;
        $dto->confirmed = $this->faker->boolean();

        $operation = $this->createMock(Operation::class);
        $customer = $this->createMock(Customer::class);
        $ulid = new Ulid($ulidStr);
        $command = $this->createMock(UpdateCustomerCommand::class);

        $this->setupRepository($ulid, $customer);
        $this->setupCustomer(
            $customer,
            $dto->initials,
            $dto->email,
            $dto->phone,
            $dto->leadSource,
            $dto->confirmed
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
        $dto = new CustomerPatch();
        $dto->id = null;
        $dto->initials = $this->faker->randomElement(['Mr', 'Ms', 'Mrs', 'Dr']);
        $dto->email = $this->faker->email();
        $dto->phone = $this->faker->phoneNumber();
        $dto->leadSource = $this->faker->word();
        $dto->type = null;
        $dto->status = null;
        $dto->confirmed = $this->faker->boolean();

        $operation = $this->createMock(Operation::class);

        $this->expectException(CustomerNotFoundException::class);

        $this->processor->process($dto, $operation);
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
        CustomerPatch $dto,
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
        CustomerPatch $dto,
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
            'newType' => $dto->type ? $type : $customer->getType(),
            'newStatus' => $dto->status ? $status : $customer->getStatus(),
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
        $dto = new CustomerPatch();
        $dto->initials = $this->faker->name();
        $dto->email = $this->faker->email();
        $dto->phone = $this->faker->phoneNumber();
        $dto->leadSource = $this->faker->word();
        $dto->type = '/api/customer_types/' . $this->faker->ulid();
        $dto->status = '/api/customer_statuses/' . $this->faker->ulid();
        $dto->confirmed = $this->faker->boolean();
        return $dto;
    }

    private function createEmptyDto(): CustomerPatch
    {
        $dto = new CustomerPatch();
        $dto->initials = null;
        $dto->email = null;
        $dto->phone = null;
        $dto->leadSource = null;
        $dto->type = null;
        $dto->status = null;
        $dto->confirmed = null;
        return $dto;
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
     * @param array<string, string|bool> $exData
     *
     * @return array{CustomerPatch, Operation, array<string, string>, Customer}
     */
    private function prepareProcessPreserveData(
        CustomerPatch $dto,
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

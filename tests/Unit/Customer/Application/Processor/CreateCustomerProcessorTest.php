<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Application\DTO\CustomerCreate;
use App\Core\Customer\Application\Factory\CreateCustomerFactoryInterface;
use App\Core\Customer\Application\Processor\CreateCustomerProcessor;
use App\Core\Customer\Application\Resolver\CustomerReferenceResolver;
use App\Core\Customer\Application\Transformer\CustomerTransformerInterface;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateCustomerProcessorTest extends UnitTestCase
{
    private CommandBusInterface|MockObject $commandBus;
    private CreateCustomerFactoryInterface|MockObject $factory;
    private CustomerReferenceResolver|MockObject $referenceResolver;
    private CustomerTransformerInterface|MockObject $transformer;
    private CreateCustomerProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->factory = $this->createMock(
            CreateCustomerFactoryInterface::class
        );
        $this->referenceResolver = $this->createMock(CustomerReferenceResolver::class);
        $this->transformer = $this->createMock(
            CustomerTransformerInterface::class
        );

        $this->processor = new CreateCustomerProcessor(
            $this->commandBus,
            $this->factory,
            $this->referenceResolver,
            $this->transformer,
        );
    }

    public function testProcessCreatesAndDispatchesCommand(): void
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);

        $type = $this->createMock(CustomerType::class);
        $status = $this->createMock(CustomerStatus::class);
        $customerEntity = $this->createMock(Customer::class);

        $this->setupReferenceResolver($dto, $type, $status);
        $this->testTransformerIsCalled($dto, $type, $status, $customerEntity);
        $this->testFactoryAndDispatchAreCalled($customerEntity);

        $result = $this->processor->process($dto, $operation);

        $this->assertSame($customerEntity, $result);
    }

    private function createDto(): CustomerCreate
    {
        return new CustomerCreate(
            initials: $this->faker->name(),
            email: $this->faker->email(),
            phone: $this->faker->phoneNumber(),
            leadSource: $this->faker->word(),
            type: '/api/customer_types/' . $this->faker->ulid(),
            status: '/api/customer_statuses/' . $this->faker->ulid(),
            confirmed: $this->faker->boolean(),
        );
    }

    private function setupReferenceResolver(
        CustomerCreate $dto,
        CustomerType $type,
        CustomerStatus $status
    ): void {
        $this->referenceResolver
            ->expects(self::once())
            ->method('resolveType')
            ->with($dto->type)
            ->willReturn($type);
        $this->referenceResolver
            ->expects(self::once())
            ->method('resolveStatus')
            ->with($dto->status)
            ->willReturn($status);
    }

    private function testFactoryAndDispatchAreCalled(
        MockObject|Customer $customerEntity
    ): void {
        $command = new CreateCustomerCommand($customerEntity);
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($customerEntity)
            ->willReturn($command);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }

    private function testTransformerIsCalled(
        CustomerCreate $dto,
        MockObject|CustomerType $type,
        MockObject|CustomerStatus $status,
        MockObject|Customer $customerEntity
    ): void {
        $this->transformer
            ->expects($this->once())
            ->method('transform')
            ->with(
                $dto->initials,
                $dto->email,
                $dto->phone,
                $dto->leadSource,
                $type,
                $status,
                $dto->confirmed,
            )
            ->willReturn($customerEntity);
    }
}

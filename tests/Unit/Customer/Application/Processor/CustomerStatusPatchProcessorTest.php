<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Core\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Core\Customer\Application\DTO\StatusPatch;
use App\Core\Customer\Application\Factory\UpdateStatusCommandFactoryInterface;
use App\Core\Customer\Application\Processor\CustomerStatusPatchProcessor;
use App\Core\Customer\Application\Resolver\CustomerStatusResolver;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CustomerStatusPatchProcessorTest extends UnitTestCase
{
    private CommandBusInterface|MockObject $commandBus;
    private UpdateStatusCommandFactoryInterface|MockObject $factory;
    private CustomerStatusResolver|MockObject $resolver;
    private CustomerStatusPatchProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->factory = $this
            ->createMock(UpdateStatusCommandFactoryInterface::class);
        $this->resolver = $this->createMock(CustomerStatusResolver::class);

        $this->processor = new CustomerStatusPatchProcessor(
            $this->commandBus,
            $this->factory,
            $this->resolver,
        );
    }

    public function testProcessDispatchesCommandWithResolvedStatus(): void
    {
        $dto = new StatusPatch($this->faker->word(), null);
        $operation = $this->createMock(Operation::class);
        $customerStatus = $this->createMock(CustomerStatus::class);
        $command = $this->createMock(UpdateCustomerStatusCommand::class);

        $customerStatus
            ->method('getValue')
            ->willReturn($this->faker->word());

        $this->resolver
            ->expects($this->once())
            ->method('resolve')
            ->with($dto, [], $operation)
            ->willReturn($customerStatus);

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($customerStatus, $this->callback(
                static fn ($update) => $update->value === $dto->value
            ))
            ->willReturn($command);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $result = $this->processor->process($dto, $operation);

        $this->assertSame($customerStatus, $result);
    }

    public function testProcessPreservesExistingValueWhenNewValueIsEmpty(): void
    {
        $dto = new StatusPatch('', null);
        $operation = $this->createMock(Operation::class);
        $customerStatus = $this->createMock(CustomerStatus::class);
        $existingValue = $this->faker->word();

        $customerStatus
            ->method('getValue')
            ->willReturn($existingValue);

        $this->resolver
            ->expects($this->once())
            ->method('resolve')
            ->willReturn($customerStatus);

        // When value is empty string, no command should be dispatched (proper PATCH semantics)
        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $result = $this->processor->process($dto, $operation);

        $this->assertSame($customerStatus, $result);
    }

    public function testProcessThrowsExceptionWhenResolverFails(): void
    {
        $dto = new StatusPatch($this->faker->word(), null);
        $operation = $this->createMock(Operation::class);

        $this->resolver
            ->expects($this->once())
            ->method('resolve')
            ->willThrowException(new CustomerStatusNotFoundException());

        $this->factory->expects($this->never())->method('create');
        $this->commandBus->expects($this->never())->method('dispatch');

        $this->expectException(CustomerStatusNotFoundException::class);

        $this->processor->process($dto, $operation);
    }

    public function testProcessThrowsExceptionWhenDataIsInvalid(): void
    {
        $operation = $this->createMock(Operation::class);

        $this->expectException(\InvalidArgumentException::class);

        $this->processor->process(new \stdClass(), $operation);
    }
}

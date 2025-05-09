<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Core\Customer\Application\Command\CreateStatusCommand;
use App\Core\Customer\Application\DTO\StatusCreate;
use App\Core\Customer\Application\Factory\CreateStatusFactoryInterface;
use App\Core\Customer\Application\Processor\CreateStatusProcessor;
use App\Core\Customer\Application\Transformer\CreateStatusTransformer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateStatusProcessorTest extends UnitTestCase
{
    private CommandBusInterface|MockObject $commandBus;
    private CreateStatusFactoryInterface|MockObject $factory;
    private CreateStatusTransformer|MockObject $transformer;
    private CreateStatusProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->factory = $this->createMock(CreateStatusFactoryInterface::class);
        $this->transformer = $this->createMock(CreateStatusTransformer::class);

        $this->processor = new CreateStatusProcessor(
            $this->commandBus,
            $this->factory,
            $this->transformer
        );
    }

    public function testProcessCreatesAndDispatchesCommand(): void
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);

        // This is the CustomerStatus instance the transformer will return
        $status = $this->createMock(CustomerStatus::class);
        // This is the command that the factory will create
        $command = $this->createMock(CreateStatusCommand::class);

        // 1) Transformer should be invoked with the DTO’s value and return our $status
        $this->transformer
            ->expects($this->once())
            ->method('transform')
            ->with($dto->value)
            ->willReturn($status);

        // 2) Factory should be invoked with the transformed status and return our $command
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($status)
            ->willReturn($command);

        // 3) Command bus should dispatch the command
        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($command);

        // Execute
        $result = $this->processor->process($dto, $operation);

        // Assert we get back the same CustomerStatus instance
        $this->assertSame($status, $result);
    }

    private function createDto(): StatusCreate
    {
        return new StatusCreate($this->faker->word());
    }
}

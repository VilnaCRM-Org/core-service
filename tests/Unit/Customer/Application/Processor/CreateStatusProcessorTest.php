<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Customer\Application\Command\CreateStatusCommand;
use App\Customer\Application\Command\CreateStatusCommandResponse;
use App\Customer\Application\DTO\StatusCreate;
use App\Customer\Application\Factory\CreateStatusFactoryInterface;
use App\Customer\Application\Processor\CreateStatusProcessor;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateStatusProcessorTest extends UnitTestCase
{
    private CommandBusInterface|MockObject $commandBus;
    private CreateStatusFactoryInterface|MockObject $factory;
    private CreateStatusProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->factory = $this->createMock(CreateStatusFactoryInterface::class);
        $this->processor = new CreateStatusProcessor(
            $this->commandBus,
            $this->factory
        );
    }

    public function testProcessCreatesAndDispatchesCommand(): void
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);
        $command = $this->createMock(CreateStatusCommand::class);
        $status = $this->createMock(CustomerStatus::class);

        $this->factory->expects($this->once())
            ->method('create')
            ->with($dto->value)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $command->expects($this->once())
            ->method('getResponse')
            ->willReturn(new CreateStatusCommandResponse($status));

        $result = $this->processor->process($dto, $operation);

        $this->assertSame($status, $result);
    }

    private function createDto(): StatusCreate
    {
        return new StatusCreate(
            $this->faker->word()
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Core\Customer\Application\Command\CreateStatusCommand;
use App\Core\Customer\Application\DTO\StatusCreate;
use App\Core\Customer\Application\Factory\CreateStatusFactoryInterface;
use App\Core\Customer\Application\Processor\CreateStatusProcessor;
use App\Core\Customer\Application\Transformer\StatusTransformerInterface;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateStatusProcessorTest extends UnitTestCase
{
    private CommandBusInterface|MockObject $commandBus;
    private CreateStatusFactoryInterface|MockObject $factory;
    private StatusTransformerInterface|MockObject $transformer;
    private CreateStatusProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->factory = $this->createMock(CreateStatusFactoryInterface::class);
        $this->transformer = $this->createMock(
            StatusTransformerInterface::class
        );

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

        $status = $this->createMock(CustomerStatus::class);
        $command = $this->createMock(CreateStatusCommand::class);

        $this->transformer
            ->expects($this->once())
            ->method('transform')
            ->with($dto->value)
            ->willReturn($status);

        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($status)
            ->willReturn($command);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $result = $this->processor->process($dto, $operation);

        $this->assertSame($status, $result);
    }

    private function createDto(): StatusCreate
    {
        return new StatusCreate($this->faker->word());
    }
}

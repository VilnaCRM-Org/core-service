<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Customer\Application\Command\CreateTypeCommand;
use App\Customer\Application\Command\CreateTypeCommandResponse;
use App\Customer\Application\DTO\TypeCreateDto;
use App\Customer\Application\Factory\CreateTypeFactoryInterface;
use App\Customer\Application\Processor\CreateTypeProcessor;
use App\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateTypeProcessorTest extends UnitTestCase
{
    private CommandBusInterface|MockObject $commandBus;
    private CreateTypeFactoryInterface|MockObject $factory;
    private CreateTypeProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->factory = $this->createMock(CreateTypeFactoryInterface::class);
        $this->processor = new CreateTypeProcessor(
            $this->commandBus,
            $this->factory
        );
    }

    public function testProcessCreatesAndDispatchesCommand(): void
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);
        $command = $this->createMock(CreateTypeCommand::class);
        $customerType = $this->createMock(CustomerType::class);

        $this->factory->expects($this->once())
            ->method('create')
            ->with($dto->value)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $command->expects($this->once())
            ->method('getResponse')
            ->willReturn(new CreateTypeCommandResponse($customerType));

        $result = $this->processor->process($dto, $operation);

        $this->assertSame($customerType, $result);
    }

    private function createDto(): TypeCreateDto
    {
        return new TypeCreateDto(
            $this->faker->word()
        );
    }
}

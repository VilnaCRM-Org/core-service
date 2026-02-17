<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Core\Customer\Application\Command\CreateTypeCommand;
use App\Core\Customer\Application\DTO\TypeCreate;
use App\Core\Customer\Application\Factory\CreateTypeFactoryInterface;
use App\Core\Customer\Application\Processor\CreateTypeProcessor;
use App\Core\Customer\Application\Transformer\TypeTransformerInterface;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateTypeProcessorTest extends UnitTestCase
{
    private CommandBusInterface|MockObject $commandBus;
    private CreateTypeFactoryInterface|MockObject $factory;
    private TypeTransformerInterface|MockObject $transformer;
    private CreateTypeProcessor $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->factory = $this->createMock(CreateTypeFactoryInterface::class);
        $this->transformer = $this->createMock(TypeTransformerInterface::class);

        $this->processor = new CreateTypeProcessor(
            $this->commandBus,
            $this->factory,
            $this->transformer
        );
    }

    public function testProcessTransformsCreatesAndDispatchesCommand(): void
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);
        $command = $this->createMock(CreateTypeCommand::class);
        $customerType = $this->createMock(CustomerType::class);

        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($dto->value)
            ->willReturn($customerType);

        $this->factory->expects($this->once())
            ->method('create')
            ->with($customerType)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $result = $this->processor->process($dto, $operation);

        $this->assertSame($customerType, $result);
    }

    private function createDto(): TypeCreate
    {
        $dto = new TypeCreate();
        $dto->value = $this->faker->word();
        return $dto;
    }
}

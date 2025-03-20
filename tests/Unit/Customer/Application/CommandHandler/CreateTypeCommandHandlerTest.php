<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Customer\Application\Command\CreateTypeCommand;
use App\Customer\Application\Command\CreateTypeCommandResponse;
use App\Customer\Application\Transformer\CreateTypeTransformer;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Customer\Application\CommandHandler\CreateTypeCommandHandler;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateTypeCommandHandlerTest extends UnitTestCase
{
    private CreateTypeTransformer|MockObject $transformer;
    private TypeRepositoryInterface|MockObject $repository;
    private CreateTypeCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = $this->createMock(CreateTypeTransformer::class);
        $this->repository = $this->createMock(TypeRepositoryInterface::class);
        $this->handler = new CreateTypeCommandHandler($this->transformer, $this->repository);
    }

    public function testInvokeCreatesAndSavesType(): void
    {
        $command = $this->createCommand();
        $type = $this->createMock(CustomerType::class);

        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($command)
            ->willReturn($type);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($type);

        $this->handler->__invoke($command);

        $response = $command->getResponse();
        $this->assertInstanceOf(CreateTypeCommandResponse::class, $response);
        $this->assertSame($type, $response->customerType);
    }

    private function createCommand(): CreateTypeCommand
    {
        return new CreateTypeCommand(
            $this->faker->word()
        );
    }
} 
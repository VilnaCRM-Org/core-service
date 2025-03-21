<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Customer\Application\Command\CreateStatusCommand;
use App\Customer\Application\Command\CreateStatusCommandResponse;
use App\Customer\Application\CommandHandler\CreateStatusCommandHandler;
use App\Customer\Application\Transformer\CreateStatusTransformer;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateStatusCommandHandlerTest extends UnitTestCase
{
    private CreateStatusTransformer|MockObject $transformer;
    private StatusRepositoryInterface|MockObject $repository;
    private CreateStatusCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = $this->createMock(CreateStatusTransformer::class);
        $this->repository = $this->createMock(StatusRepositoryInterface::class);
        $this->handler = new CreateStatusCommandHandler($this->transformer, $this->repository);
    }

    public function testInvokeCreatesAndSavesStatus(): void
    {
        $command = $this->createCommand();
        $status = $this->createMock(CustomerStatus::class);

        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($command)
            ->willReturn($status);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($status);

        $this->handler->__invoke($command);

        $response = $command->getResponse();
        $this->assertInstanceOf(CreateStatusCommandResponse::class, $response);
        $this->assertSame($status, $response->customerStatus);
    }

    private function createCommand(): CreateStatusCommand
    {
        return new CreateStatusCommand(
            $this->faker->word()
        );
    }
}

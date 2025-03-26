<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Customer\Application\DTO\CustomerStatusPutDto;
use App\Customer\Application\Factory\UpdateStatusCommandFactoryInterface;
use App\Customer\Application\Processor\CustomerStatusPutProcessor;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CustomerStatusPutProcessorTest extends UnitTestCase
{
    private StatusRepositoryInterface|MockObject $repository;
    private CommandBusInterface|MockObject $commandBus;
    private UpdateStatusCommandFactoryInterface|MockObject $factory;
    private UlidFactory|MockObject $ulidFactory;
    private CustomerStatusPutProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(StatusRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->factory = $this->createMock(UpdateStatusCommandFactoryInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);

        $this->processor = new CustomerStatusPutProcessor(
            $this->repository,
            $this->commandBus,
            $this->factory,
            $this->ulidFactory,
        );
    }

    public function testProcessUpdatesAndDispatchesCommand(): void
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);
        $ulid = '01HX5Z5Y5Z5Y5Z5Y5Z5Y5Z5Y5Z';
        $customerStatus = $this->createMock(CustomerStatus::class);
        $command = $this
            ->createMock(UpdateCustomerStatusCommand::class);
        $ulidMock = $this->createMock(Ulid::class);

        $this->setupRepository($ulid, $customerStatus, $ulidMock);
        $this->setupUlidFactory($ulid, $ulidMock);
        $this->setupFactoryAndCommandBus($customerStatus, $dto->value, $command);

        $result = $this->processor->process($dto, $operation, ['ulid' => $ulid]);

        $this->assertSame($customerStatus, $result);
    }

    public function testProcessThrowsExceptionWhenCustomerStatusNotFound(): void
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);
        $ulid = '01HX5Z5Y5Z5Y5Z5Y5Z5Y5Z5Y5Z';
        $ulidMock = $this->createMock(Ulid::class);

        $this->setupRepository($ulid, null, $ulidMock);
        $this->setupUlidFactory($ulid, $ulidMock);

        $this->expectException(CustomerStatusNotFoundException::class);
        $this->expectExceptionMessage('Customer status not found');

        $this->processor->process($dto, $operation, ['ulid' => $ulid]);
    }

    private function createDto(): CustomerStatusPutDto
    {
        return new CustomerStatusPutDto(
            $this->faker->word()
        );
    }

    private function setupRepository(string $ulid, ?CustomerStatus $customerStatus, Ulid $ulidMock): void
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with($ulidMock)
            ->willReturn($customerStatus);
    }

    private function setupUlidFactory(string $ulid, Ulid $ulidMock): void
    {
        $this->ulidFactory
            ->expects($this->once())
            ->method('create')
            ->with($ulid)
            ->willReturn($ulidMock);
    }

    private function setupFactoryAndCommandBus(
        CustomerStatus $customerStatus,
        string $value,
        UpdateCustomerStatusCommand $command
    ): void {
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($customerStatus, $this->callback(function ($update) use ($value) {
                return $update->value === $value;
            }))
            ->willReturn($command);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Customer\Application\DTO\StatusPatch;
use App\Customer\Application\Factory\UpdateStatusCommandFactoryInterface;
use App\Customer\Application\Processor\CustomerStatusPatchProcessor;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Exception\CustomerStatusNotFoundException;
use App\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CustomerStatusPatchProcessorTest extends UnitTestCase
{
    private StatusRepositoryInterface|MockObject $repository;
    private CommandBusInterface|MockObject $commandBus;
    private UpdateStatusCommandFactoryInterface|MockObject $factory;
    private UlidFactory|MockObject $ulidFactory;
    private CustomerStatusPatchProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(StatusRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->factory = $this
            ->createMock(UpdateStatusCommandFactoryInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);

        $this->processor = new CustomerStatusPatchProcessor(
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
        $ulid = (string) $this->faker->ulid();
        $customerStatus = $this->createMock(CustomerStatus::class);
        $command = $this->createMock(UpdateCustomerStatusCommand::class);
        $ulidMock = $this->createMock(Ulid::class);

        $this->setupRepository($customerStatus, $ulidMock);
        $this->setupUlidFactory($ulid, $ulidMock);
        $this->setupDependencies($customerStatus, $dto->value, $command);
        $this->setupCustomerStatus($customerStatus, $dto->value);

        $result = $this->processor
            ->process($dto, $operation, ['ulid' => $ulid]);

        $this->assertSame($customerStatus, $result);
    }

    public function testProcessPreservesExistingValueWhenNewValueIsEmpty(): void
    {
        $existingValue = $this->faker->word();
        $dto = $this->createDtoWithEmptyValue();
        $operation = $this->createMock(Operation::class);
        $ulid = (string) $this->faker->ulid();
        $customerStatus = $this->createMock(CustomerStatus::class);
        $command = $this->createMock(UpdateCustomerStatusCommand::class);
        $ulidMock = $this->createMock(Ulid::class);

        $this->setupRepository($customerStatus, $ulidMock);
        $this->setupUlidFactory($ulid, $ulidMock);
        $this->setupDependencies($customerStatus, $existingValue, $command);
        $this->setupCustomerStatus($customerStatus, $existingValue);

        $result = $this->processor
            ->process($dto, $operation, ['ulid' => $ulid]);

        $this->assertSame($customerStatus, $result);
    }

    public function testProcessThrowsExceptionWhenCustomerStatusNotFound(): void
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);
        $ulid = (string) $this->faker->ulid();
        $ulidMock = $this->createMock(Ulid::class);

        $this->setupRepository(null, $ulidMock);
        $this->setupUlidFactory($ulid, $ulidMock);

        $this->expectException(CustomerStatusNotFoundException::class);
        $this->expectExceptionMessage('Customer status not found');

        $this->processor->process($dto, $operation, ['ulid' => $ulid]);
    }

    private function createDto(): StatusPatch
    {
        return new StatusPatch(
            $this->faker->word()
        );
    }

    private function createDtoWithEmptyValue(): StatusPatch
    {
        return new StatusPatch('');
    }

    private function setupRepository(
        ?CustomerStatus $customerStatus,
        Ulid $ulidMock
    ): void {
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

    private function setupCustomerStatus(
        CustomerStatus $customerStatus,
        string $value
    ): void {
        $customerStatus
            ->method('getValue')
            ->willReturn($value);
    }

    private function setupDependencies(
        CustomerStatus $customerStatus,
        string $value,
        UpdateCustomerStatusCommand $command
    ): void {
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($customerStatus, $this
                ->callback(static function ($update) use ($value) {
                    return $update->value === $value;
                }))
            ->willReturn($command);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($command);
    }
}

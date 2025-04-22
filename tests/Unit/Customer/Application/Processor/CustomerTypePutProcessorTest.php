<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Customer\Application\Command\UpdateCustomerTypeCommand;
use App\Customer\Application\DTO\TypePutDto;
use App\Customer\Application\Factory\UpdateCustomerTypeCommandFactoryInterface;
use App\Customer\Application\Processor\CustomerTypePutProcessor;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Exception\CustomerTypeNotFoundException;
use App\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CustomerTypePutProcessorTest extends UnitTestCase
{
    private TypeRepositoryInterface|MockObject $repository;
    private CommandBusInterface|MockObject $commandBus;
    private UpdateCustomerTypeCommandFactoryInterface|MockObject $factory;
    private UlidFactory|MockObject $ulidFactory;
    private CustomerTypePutProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(TypeRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->factory = $this
            ->createMock(UpdateCustomerTypeCommandFactoryInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);

        $this->processor = new CustomerTypePutProcessor(
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
        $customerType = $this->createMock(CustomerType::class);
        $command = $this->createMock(UpdateCustomerTypeCommand::class);
        $ulidMock = $this->createMock(Ulid::class);

        $this->setupRepository($customerType, $ulidMock);
        $this->setupUlidFactory($ulid, $ulidMock);
        $this->setupFactoryAndCommandBus($customerType, $dto->value, $command);

        $result = $this->processor
            ->process($dto, $operation, ['ulid' => $ulid]);

        $this->assertSame($customerType, $result);
    }

    public function testProcessThrowsExceptionWhenCustomerTypeNotFound(): void
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);
        $ulid = (string) $this->faker->ulid();
        $ulidMock = $this->createMock(Ulid::class);

        $this->setupRepository(null, $ulidMock);
        $this->setupUlidFactory($ulid, $ulidMock);

        $this->expectException(CustomerTypeNotFoundException::class);
        $this->expectExceptionMessage('Customer type not found');

        $this->processor->process($dto, $operation, ['ulid' => $ulid]);
    }

    private function createDto(): TypePutDto
    {
        return new TypePutDto(
            $this->faker->word()
        );
    }

    private function setupRepository(
        ?CustomerType $customerType,
        Ulid $ulidMock
    ): void {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with($ulidMock)
            ->willReturn($customerType);
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
        CustomerType $customerType,
        string $value,
        UpdateCustomerTypeCommand $command
    ): void {
        $this->factory
            ->expects($this->once())
            ->method('create')
            ->with($customerType, $this
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

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Core\Customer\Application\Command\UpdateCustomerTypeCommand;
use App\Core\Customer\Application\DTO\TypePatch;
use App\Core\Customer\Application\Factory\UpdateTypeCommandFactoryInterface;
use App\Core\Customer\Application\Processor\CustomerTypePatchProcessor;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Exception\CustomerTypeNotFoundException;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\ValueObject\Ulid;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CustomerTypePatchProcessorTest extends UnitTestCase
{
    private TypeRepositoryInterface|MockObject $repository;
    private CommandBusInterface|MockObject $commandBus;
    private UpdateTypeCommandFactoryInterface|MockObject $factory;
    private UlidFactory|MockObject $ulidFactory;
    private CustomerTypePatchProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(TypeRepositoryInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->factory = $this
            ->createMock(UpdateTypeCommandFactoryInterface::class);
        $this->ulidFactory = $this->createMock(UlidFactory::class);

        $this->processor = new CustomerTypePatchProcessor(
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
        $this->setupDependencies($customerType, $dto->value, $command);
        $this->setupCustomerType($customerType, $dto->value);

        $result = $this->processor
            ->process($dto, $operation, ['ulid' => $ulid]);

        $this->assertSame($customerType, $result);
    }

    public function testProcessPreservesExistingValueWhenNewValueIsEmpty(): void
    {
        $existingValue = $this->faker->word();
        $dto = $this->createDtoWithEmptyValue();
        $operation = $this->createMock(Operation::class);
        $ulid = (string) $this->faker->ulid();
        $customerType = $this->createMock(CustomerType::class);
        $ulidMock = $this->createMock(Ulid::class);

        $this->setupRepository($customerType, $ulidMock);
        $this->setupUlidFactory($ulid, $ulidMock);
        $this->setupCustomerType($customerType, $existingValue);

        // When value is empty string, no command should be dispatched (proper PATCH semantics)
        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

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

        $this->processor->process($dto, $operation, ['ulid' => $ulid]);
    }

    public function testProcessWithGraphQLPathExtractsUlidFromIri(): void
    {
        $ulid = (string) $this->faker->ulid();
        $iri = sprintf('/api/customer_types/%s', $ulid);
        $dto = new TypePatch($this->faker->word(), $iri);
        $operation = $this->createMock(Operation::class);
        $customerType = $this->createMock(CustomerType::class);
        $command = $this->createMock(UpdateCustomerTypeCommand::class);
        $ulidMock = $this->createMock(Ulid::class);

        $this->setupRepository($customerType, $ulidMock);
        $this->setupUlidFactory($ulid, $ulidMock);
        $this->setupDependencies($customerType, $dto->value, $command);
        $this->setupCustomerType($customerType, $dto->value);

        $result = $this->processor->process($dto, $operation);

        $this->assertSame($customerType, $result);
    }

    public function testProcessThrowsExceptionWhenNoUlidProvided(): void
    {
        $dto = new TypePatch($this->faker->word(), null);
        $operation = $this->createMock(Operation::class);

        $this->expectException(CustomerTypeNotFoundException::class);

        $this->processor->process($dto, $operation);
    }

    private function createDto(): TypePatch
    {
        return new TypePatch(
            $this->faker->word(),
            null
        );
    }

    private function createDtoWithEmptyValue(): TypePatch
    {
        return new TypePatch('', null);
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

    private function setupCustomerType(
        CustomerType $customerType,
        string $value
    ): void {
        $customerType
            ->method('getValue')
            ->willReturn($value);
    }

    private function setupDependencies(
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

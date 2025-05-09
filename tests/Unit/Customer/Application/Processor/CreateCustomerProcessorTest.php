<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Application\DTO\CustomerCreate;
use App\Core\Customer\Application\Factory\CreateCustomerFactoryInterface;
use App\Core\Customer\Application\Processor\CreateCustomerProcessor;
use App\Core\Customer\Application\Transformer\CreateCustomerTransformer;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateCustomerProcessorTest extends UnitTestCase
{
    private CommandBusInterface|MockObject $commandBus;
    private CreateCustomerFactoryInterface|MockObject $factory;
    private IriConverterInterface|MockObject $iriConverter;
    private CreateCustomerTransformer|MockObject $transformer;
    private CreateCustomerProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->factory = $this->createMock(
            CreateCustomerFactoryInterface::class
        );
        $this->iriConverter = $this->createMock(IriConverterInterface::class);
        $this->transformer = $this->createMock(
            CreateCustomerTransformer::class
        );

        $this->processor = new CreateCustomerProcessor(
            $this->commandBus,
            $this->factory,
            $this->iriConverter,
            $this->transformer,
        );
    }

    public function testProcessCreatesAndDispatchesCommand(): void
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);

        $type = $this->createMock(CustomerType::class);
        $status = $this->createMock(CustomerStatus::class);
        $customerEntity = $this->createMock(Customer::class);

        $this->testIriConvertor($dto, $type, $status);

        $this->testTransformerIsCalled($dto, $type, $status, $customerEntity);

        $this->testFactoryAndDispatchAreCalled($customerEntity);

        $result = $this->processor->process($dto, $operation);

        self::assertSame($customerEntity, $result);
    }

    private function createDto(): CustomerCreate
    {
        return new CustomerCreate(
            $this->faker->name(),
            $this->faker->email(),
            $this->faker->phoneNumber(),
            $this->faker->word(),
            '/api/customer_types/'   . $this->faker->ulid(),
            '/api/customer_statuses/' . $this->faker->ulid(),
            $this->faker->boolean()
        );
    }

    private function testIriConvertor(
        CustomerCreate $dto,
        CustomerType $type,
        CustomerStatus $status
    ): void {
        $this->iriConverter
            ->expects(self::exactly(2))
            ->method('getResourceFromIri')
            ->willReturnCallback(static function (
                string $iri
            ) use (
                $dto,
                $type,
                $status
            ) {
                return match ($iri) {
                    $dto->type => $type,
                    $dto->status => $status,
                    default => throw new \InvalidArgumentException(
                        'Unexpected IRI'
                    ),
                };
            });
    }

    private function testFactoryAndDispatchAreCalled(
        MockObject|Customer $customerEntity
    ): void {
        $command = new CreateCustomerCommand($customerEntity);
        $this->factory
            ->expects(self::once())
            ->method('create')
            ->with($customerEntity)
            ->willReturn($command);


        $this->commandBus
            ->expects(self::once())
            ->method('dispatch')
            ->with($command);
    }

    private function testTransformerIsCalled(
        CustomerCreate $dto,
        MockObject|CustomerType $type,
        MockObject|CustomerStatus $status,
        MockObject|Customer $customerEntity
    ): void {
        $this->transformer
            ->expects(self::once())
            ->method('transform')
            ->with(
                $dto->initials,
                $dto->email,
                $dto->phone,
                $dto->leadSource,
                $type,
                $status,
                $dto->confirmed,
            )
            ->willReturn($customerEntity);
    }
}

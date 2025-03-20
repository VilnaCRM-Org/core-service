<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\Processor;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use App\Customer\Application\DTO\CustomerCreateDto;
use App\Customer\Application\Factory\CreateCustomerFactoryInterface;
use App\Customer\Application\Processor\CreateCustomerProcessor;
use App\Customer\Application\Command\CreateCustomerCommand;
use App\Customer\Application\Command\CreateCustomerCommandResponse;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateCustomerProcessorTest extends UnitTestCase
{
    private CommandBusInterface|MockObject $commandBus;
    private CreateCustomerFactoryInterface|MockObject $factory;
    private IriConverterInterface|MockObject $iriConverter;
    private CreateCustomerProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->factory = $this->createMock(CreateCustomerFactoryInterface::class);
        $this->iriConverter = $this->createMock(IriConverterInterface::class);
        $this->processor = new CreateCustomerProcessor(
            $this->commandBus,
            $this->factory,
            $this->iriConverter
        );
    }

    public function testProcessCreatesAndDispatchesCommand(): void
    {
        $dto = $this->createDto();
        $operation = $this->createMock(Operation::class);
        $type = $this->createMock(CustomerType::class);
        $status = $this->createMock(CustomerStatus::class);
        $command = $this->createMock(CreateCustomerCommand::class);
        $customer = $this->createMock(Customer::class);

        $this->iriConverter->expects($this->exactly(2))
            ->method('getResourceFromIri')
            ->willReturnCallback(function (string $iri) use ($type, $status, $dto) {
                return match ($iri) {
                    $dto->type => $type,
                    $dto->status => $status,
                    default => throw new \InvalidArgumentException('Unexpected IRI')
                };
            });

        $this->factory->expects($this->once())
            ->method('create')
            ->with(
                $dto->initials,
                $dto->email,
                $dto->phone,
                $dto->leadSource,
                $type,
                $status,
                $dto->confirmed
            )
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $command->expects($this->once())
            ->method('getResponse')
            ->willReturn(new CreateCustomerCommandResponse($customer));

        $result = $this->processor->process($dto, $operation);

        $this->assertSame($customer, $result);
    }

    private function createDto(): CustomerCreateDto
    {
        return new CustomerCreateDto(
            $this->faker->name(),
            $this->faker->email(),
            $this->faker->phoneNumber(),
            $this->faker->word(),
            '/api/customer_types/' . $this->faker->ulid(),
            '/api/customer_statuses/' . $this->faker->ulid(),
            $this->faker->boolean()
        );
    }
} 
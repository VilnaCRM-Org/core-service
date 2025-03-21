<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Customer\Application\Command\CreateCustomerCommand;
use App\Customer\Application\Command\CreateCustomerCommandResponse;
use App\Customer\Application\CommandHandler\CreateCustomerCommandHandler;
use App\Customer\Application\Transformer\CreateCustomerTransformer;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateCustomerCommandHandlerTest extends UnitTestCase
{
    private CreateCustomerTransformer|MockObject $transformer;
    private CustomerRepositoryInterface|MockObject $repository;
    private CreateCustomerCommandHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transformer = $this->createMock(CreateCustomerTransformer::class);
        $this->repository = $this->createMock(CustomerRepositoryInterface::class);
        $this->handler = new CreateCustomerCommandHandler($this->transformer, $this->repository);
    }

    public function testInvokeCreatesAndSavesCustomer(): void
    {
        $command = $this->createCommand();
        $customer = $this->createMock(Customer::class);

        $this->transformer->expects($this->once())
            ->method('transform')
            ->with($command)
            ->willReturn($customer);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($customer);

        $this->handler->__invoke($command);

        $response = $command->getResponse();
        $this->assertInstanceOf(CreateCustomerCommandResponse::class, $response);
        $this->assertSame($customer, $response->customer);
    }

    private function createCommand(): CreateCustomerCommand
    {
        return new CreateCustomerCommand(
            $this->faker->name(),
            $this->faker->email(),
            $this->faker->phoneNumber(),
            $this->faker->word(),
            $this->createMock(CustomerType::class),
            $this->createMock(CustomerStatus::class),
            $this->faker->boolean()
        );
    }
}

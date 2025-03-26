<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Customer\Application\Command\UpdateCustomerCommand;
use App\Customer\Application\CommandHandler\UpdateCustomerCommandHandler;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateCustomerCommandHandlerTest extends UnitTestCase
{
    private CustomerRepositoryInterface|MockObject $repository;
    private UpdateCustomerCommandHandler $handler;
    private UlidTransformer $ulidTransformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this
            ->createMock(CustomerRepositoryInterface::class);
        $this->ulidTransformer = new UlidTransformer(new UlidFactory());
        $this->handler = new UpdateCustomerCommandHandler($this->repository);
    }

    public function testInvokeUpdatesAndSavesCustomer(): void
    {
        $typeUlid = $this->ulidTransformer->transformFromSymfonyUlid(
            $this->faker->ulid(),
        );
        $statusUlid = $this->ulidTransformer->transformFromSymfonyUlid(
            $this->faker->ulid(),
        );

        $customerType = new CustomerType('individual', $typeUlid);
        $customerStatus = new CustomerStatus('active', $statusUlid);

        $customer = $this->createMock(Customer::class);
        $updateData = new CustomerUpdate(
            newInitials: $this->faker->name(),
            newEmail: $this->faker->email(),
            newPhone: $this->faker->phoneNumber(),
            newLeadSource: $this->faker->word(),
            newType: $customerType,
            newStatus: $customerStatus,
            newConfirmed: $this->faker->boolean(),
        );

        $command = new UpdateCustomerCommand($customer, $updateData);

        $customer->expects($this->once())
            ->method('setInitials')
            ->with($updateData->newInitials);

        $customer->expects($this->once())
            ->method('setEmail')
            ->with($updateData->newEmail);

        $customer->expects($this->once())
            ->method('setPhone')
            ->with($updateData->newPhone);

        $customer->expects($this->once())
            ->method('setLeadSource')
            ->with($updateData->newLeadSource);

        $customer->expects($this->once())
            ->method('setType')
            ->with($updateData->newType);

        $customer->expects($this->once())
            ->method('setStatus')
            ->with($updateData->newStatus);

        $customer->expects($this->once())
            ->method('setConfirmed')
            ->with($updateData->newConfirmed);

        $this->repository->expects($this->once())
            ->method('save')
            ->with($customer);

        $this->handler->__invoke($command);
    }
}

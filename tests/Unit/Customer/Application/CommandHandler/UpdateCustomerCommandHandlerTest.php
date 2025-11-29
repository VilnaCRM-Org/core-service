<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Application\CommandHandler\UpdateCustomerCommandHandler;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Infrastructure\Converter\UlidConverter;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Shared\Infrastructure\Validator\UlidValidator;
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
        $ulidFactory = new UlidFactory();
        $this->ulidTransformer = new UlidTransformer($ulidFactory, new UlidValidator(), new UlidConverter($ulidFactory));
        $this->handler = new UpdateCustomerCommandHandler($this->repository);
    }

    public function createCommand(
        Customer $customer,
        CustomerUpdate $updateData
    ): void {
        $command = new UpdateCustomerCommand($customer, $updateData);

        $this->expectCustomerSetters($customer, $updateData);
        $this->executeCommand($command, $customer);
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

        $this->createCommand($customer, $updateData);
    }

    private function expectCustomerSetters(
        Customer $customer,
        CustomerUpdate $updateData
    ): void {
        $customer->expects($this->once())
            ->method('update')->with($updateData);
    }

    private function executeCommand(
        UpdateCustomerCommand $command,
        Customer $customer
    ): void {
        $this->repository->expects($this->once())
            ->method('save')->with($customer);
        ($this->handler)($command);
    }
}

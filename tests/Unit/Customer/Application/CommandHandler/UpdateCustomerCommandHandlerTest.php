<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Application\CommandHandler\UpdateCustomerCommandHandler;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Entity\CustomerStatus;
use App\Core\Customer\Domain\Entity\CustomerType;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Core\Customer\Domain\ValueObject\CustomerUpdate;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Factory\UlidFactory;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use App\Shared\Infrastructure\Transformer\UlidValueTransformer;
use App\Shared\Infrastructure\Validator\UlidValidator;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class UpdateCustomerCommandHandlerTest extends UnitTestCase
{
    private CustomerRepositoryInterface|MockObject $repository;
    private UpdateCustomerCommandHandler $handler;
    private UlidTransformer $ulidTransformer;
    private EventBusInterface|MockObject $eventBus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this
            ->createMock(CustomerRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $ulidFactory = new UlidFactory();
        $this->ulidTransformer = new UlidTransformer(
            $ulidFactory,
            new UlidValidator(),
            new UlidValueTransformer($ulidFactory)
        );
        $this->handler = new UpdateCustomerCommandHandler($this->repository, $this->eventBus);
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

    public function testInvokeWithUnchangedEmailPublishesEventWithSameEmail(): void
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

        $customerId = (string) $this->faker->ulid();
        $email = 'unchanged@example.com';

        $customer->expects($this->once())
            ->method('update')->with($updateData);

        $this->repository->expects($this->once())
            ->method('save')->with($customer);

        $customer->expects($this->once())
            ->method('getUlid')
            ->willReturn($customerId);

        // Email unchanged - return same value on both calls
        $customer->expects($this->exactly(2))
            ->method('getEmail')
            ->willReturn($email);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(static function ($event) use ($customerId, $email) {
                if (! $event instanceof CustomerUpdatedEvent) {
                    return false;
                }

                // Verify event properties match and emailChanged is false
                // Note: previousEmail is null when email hasn't changed (see UpdateCustomerCommandHandler line 53)
                return $event->customerId() === $customerId
                    && $event->currentEmail() === $email
                    && $event->previousEmail() === null
                    && $event->emailChanged() === false;
            }));

        $command = new UpdateCustomerCommand($customer, $updateData);
        ($this->handler)($command);
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
        $customerId = (string) $this->faker->ulid();
        $previousEmail = 'Old+Update@Example.COM';
        $currentEmail = 'New+Update@Example.COM';

        $this->repository->expects($this->once())
            ->method('save')->with($customer);
        $customer->expects($this->once())
            ->method('getUlid')
            ->willReturn($customerId);
        $customer->expects($this->exactly(2))
            ->method('getEmail')
            ->willReturn($previousEmail, $currentEmail);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->callback(static function ($event) use ($customerId, $currentEmail, $previousEmail) {
                if (! $event instanceof CustomerUpdatedEvent) {
                    return false;
                }

                // Verify event properties match expected values
                return $event->customerId() === $customerId
                    && $event->currentEmail() === $currentEmail
                    && $event->previousEmail() === $previousEmail
                    && $event->emailChanged() === true;
            }));

        ($this->handler)($command);
    }
}

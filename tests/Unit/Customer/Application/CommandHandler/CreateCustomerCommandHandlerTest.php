<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Application\CommandHandler\CreateCustomerCommandHandler;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class CreateCustomerCommandHandlerTest extends UnitTestCase
{
    private CustomerRepositoryInterface&MockObject $repository;
    private EventBusInterface&MockObject $eventBus;
    private CreateCustomerCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(
            CustomerRepositoryInterface::class
        );
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->handler = new CreateCustomerCommandHandler($this->repository, $this->eventBus);
    }

    public function testInvokeSavesCustomer(): void
    {
        $customer = $this->createMock(Customer::class);
        $customerId = (string) $this->faker->ulid();
        $email = 'TeSt+Create@Example.COM';
        $customer->method('getUlid')->willReturn($customerId);
        $customer->method('getEmail')->willReturn($email);

        $command = new CreateCustomerCommand($customer);

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($customer);

        $this->eventBus->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(CustomerCreatedEvent::class));

        ($this->handler)($command);
    }
}

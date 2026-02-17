<?php

declare(strict_types=1);

namespace App\Tests\Unit\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\DeleteCustomerCommand;
use App\Core\Customer\Application\CommandHandler\DeleteCustomerCommandHandler;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @internal
 */
final class DeleteCustomerCommandHandlerTest extends UnitTestCase
{
    private CustomerRepositoryInterface&MockObject $repository;
    private EventBusInterface&MockObject $eventBus;
    private DeleteCustomerCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(CustomerRepositoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->handler = new DeleteCustomerCommandHandler($this->repository, $this->eventBus);
    }

    public function testInvokeDeletesCustomerAndPublishesEvent(): void
    {
        $customerId = (string) $this->faker->ulid();
        $customerEmail = 'test-delete@example.com';

        $customer = $this->createMock(Customer::class);
        $customer->method('getUlid')->willReturn($customerId);
        $customer->method('getEmail')->willReturn($customerEmail);

        $command = new DeleteCustomerCommand($customer);

        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with($customer);

        $this->eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(CustomerDeletedEvent::class));

        ($this->handler)($command);
    }
}

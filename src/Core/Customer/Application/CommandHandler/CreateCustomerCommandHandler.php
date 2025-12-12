<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Domain\Event\CustomerCreatedEvent;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;

/**
 * Create Customer Command Handler
 *
 * Responsibilities:
 * - Save customer to database
 * - Publish CustomerCreatedEvent for cache invalidation
 *
 * Cache invalidation decoupled via events:
 * - No direct cache dependency
 * - Event subscriber handles invalidation
 * - Follows Single Responsibility Principle
 */
final readonly class CreateCustomerCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(CreateCustomerCommand $command): void
    {
        $customer = $command->customer;
        $this->repository->save($customer);

        // Publish domain event for cache invalidation
        $this->eventBus->publish(
            new CustomerCreatedEvent(
                customerId: $customer->getUlid(),
                customerEmail: $customer->getEmail()
            )
        );
    }
}

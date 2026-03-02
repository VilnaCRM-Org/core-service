<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\DeleteCustomerCommand;
use App\Core\Customer\Domain\Event\CustomerDeletedEvent;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;

/**
 * Delete Customer Command Handler
 *
 * Responsibilities:
 * - Delete customer from database
 * - Publish CustomerDeletedEvent for cache invalidation
 */
final readonly class DeleteCustomerCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(DeleteCustomerCommand $command): void
    {
        $customer = $command->customer;
        $customerId = $customer->getUlid();
        $customerEmail = $customer->getEmail();

        $this->repository->delete($customer);

        $this->eventBus->publish(
            new CustomerDeletedEvent(
                customerId: $customerId,
                customerEmail: $customerEmail,
            )
        );
    }
}

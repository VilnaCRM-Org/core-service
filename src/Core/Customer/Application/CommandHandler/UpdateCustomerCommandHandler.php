<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Domain\Event\CustomerUpdatedEvent;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;

/**
 * Update Customer Command Handler
 *
 * Responsibilities:
 * - Capture previous state before update (for email change detection)
 * - Update customer in database
 * - Publish CustomerUpdatedEvent for cache invalidation
 *
 * Email change handling:
 * - Captures previousEmail before update
 * - Passes to event for invalidation of both old and new email caches
 * - Event subscriber handles the complexity
 */
final readonly class UpdateCustomerCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(UpdateCustomerCommand $command): void
    {
        $customer = $command->customer;

        // Capture previous email BEFORE update for email change detection
        $previousEmail = $customer->getEmail();

        $customer->update($command->updateData);
        $this->repository->save($customer);

        $currentEmail = $customer->getEmail();
        $emailChanged = $previousEmail !== $currentEmail;

        // Publish domain event for cache invalidation
        $this->eventBus->publish(
            new CustomerUpdatedEvent(
                customerId: $customer->getUlid(),
                currentEmail: $currentEmail,
                previousEmail: $emailChanged ? $previousEmail : null
            )
        );
    }
}

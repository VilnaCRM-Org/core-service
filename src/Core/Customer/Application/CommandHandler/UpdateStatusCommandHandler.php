<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Core\Customer\Domain\Event\CustomerStatusUpdatedEvent;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;

final readonly class UpdateStatusCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private StatusRepositoryInterface $repository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(UpdateCustomerStatusCommand $command): void
    {
        $customerStatus = $command->customerStatus;
        $previousValue = $customerStatus->getValue();

        $customerStatus->update($command->update);
        $this->repository->save($customerStatus);

        $currentValue = $customerStatus->getValue();
        $valueChanged = $previousValue !== $currentValue;

        $this->eventBus->publish(
            new CustomerStatusUpdatedEvent(
                customerStatusId: $customerStatus->getUlid(),
                currentValue: $currentValue,
                previousValue: $valueChanged ? $previousValue : null
            )
        );
    }
}

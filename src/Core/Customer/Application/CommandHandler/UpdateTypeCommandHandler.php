<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\UpdateCustomerTypeCommand;
use App\Core\Customer\Domain\Event\CustomerTypeUpdatedEvent;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;

final readonly class UpdateTypeCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private TypeRepositoryInterface $repository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(UpdateCustomerTypeCommand $command): void
    {
        $customerType = $command->customerType;
        $previousValue = $customerType->getValue();

        $customerType->update($command->update);
        $this->repository->save($customerType);

        $currentValue = $customerType->getValue();
        $valueChanged = $previousValue !== $currentValue;

        $this->eventBus->publish(
            new CustomerTypeUpdatedEvent(
                customerTypeId: $customerType->getUlid(),
                currentValue: $currentValue,
                previousValue: $valueChanged ? $previousValue : null
            )
        );
    }
}

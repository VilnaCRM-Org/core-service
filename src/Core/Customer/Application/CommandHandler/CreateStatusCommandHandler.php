<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateStatusCommand;
use App\Core\Customer\Domain\Event\CustomerStatusCreatedEvent;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;

final readonly class CreateStatusCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private StatusRepositoryInterface $repository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(CreateStatusCommand $command): void
    {
        $status = $command->status;
        $this->repository->save($status);

        $this->eventBus->publish(
            new CustomerStatusCreatedEvent(
                customerStatusId: $status->getUlid(),
                customerStatusValue: $status->getValue()
            )
        );
    }
}

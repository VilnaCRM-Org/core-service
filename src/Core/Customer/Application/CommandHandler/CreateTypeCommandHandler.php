<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateTypeCommand;
use App\Core\Customer\Domain\Event\CustomerTypeCreatedEvent;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use App\Shared\Domain\Bus\Event\EventBusInterface;

final readonly class CreateTypeCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private TypeRepositoryInterface $repository,
        private EventBusInterface $eventBus,
    ) {
    }

    public function __invoke(CreateTypeCommand $command): void
    {
        $type = $command->type;
        $this->repository->save($type);

        $this->eventBus->publish(
            new CustomerTypeCreatedEvent(
                customerTypeId: $type->getUlid(),
                customerTypeValue: $type->getValue()
            )
        );
    }
}

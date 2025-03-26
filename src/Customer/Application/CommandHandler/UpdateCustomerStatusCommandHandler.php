<?php

declare(strict_types=1);

namespace App\Customer\Application\CommandHandler;

use App\Customer\Application\Command\UpdateCustomerStatusCommand;
use App\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;

final readonly class UpdateCustomerStatusCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private StatusRepositoryInterface $repository,
    ) {
    }

    public function __invoke(UpdateCustomerStatusCommand $command): void
    {
        $command->customerStatus->setValue($command->update->value);
        $this->repository->save($command->customerStatus);
    }
}

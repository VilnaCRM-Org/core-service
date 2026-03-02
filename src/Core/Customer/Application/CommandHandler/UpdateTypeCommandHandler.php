<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\UpdateCustomerTypeCommand;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;

final readonly class UpdateTypeCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private TypeRepositoryInterface $repository,
    ) {
    }

    public function __invoke(UpdateCustomerTypeCommand $command): void
    {
        $command->customerType->update($command->update);
        $this->repository->save($command->customerType);
    }
}

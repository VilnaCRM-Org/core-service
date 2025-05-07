<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateStatusCommand;
use App\Core\Customer\Domain\Repository\StatusRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;

final class CreateStatusCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private StatusRepositoryInterface $repository
    ) {
    }

    public function __invoke(CreateStatusCommand $command): void
    {
        $this->repository->save($command->status);
    }
}

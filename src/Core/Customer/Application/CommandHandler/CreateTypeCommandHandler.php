<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateTypeCommand;
use App\Core\Customer\Domain\Repository\TypeRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;

final class CreateTypeCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private TypeRepositoryInterface $repository
    ) {
    }

    public function __invoke(CreateTypeCommand $command): void
    {
        $this->repository->save($command->type);
    }
}

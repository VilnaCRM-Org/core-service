<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;

final class CreateCustomerCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository
    ) {
    }

    public function __invoke(CreateCustomerCommand $command): void
    {
        $this->repository->save($command->customer);
    }
}

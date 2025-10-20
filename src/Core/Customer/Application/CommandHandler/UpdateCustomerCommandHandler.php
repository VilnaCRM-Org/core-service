<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;

final readonly class UpdateCustomerCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
    ) {
    }

    public function __invoke(UpdateCustomerCommand $command): void
    {
        $customer = $command->customer;
        $updateData = $command->updateData;

        $customer->setInitials($updateData->newInitials);
        $customer->setEmail($updateData->newEmail);
        $customer->setPhone($updateData->newPhone);
        $customer->setLeadSource($updateData->newLeadSource);
        $customer->setType($updateData->newType);
        $customer->setStatus($updateData->newStatus);
        $customer->setConfirmed($updateData->newConfirmed);

        $this->repository->save($customer);
    }
}

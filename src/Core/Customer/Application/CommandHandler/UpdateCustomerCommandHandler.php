<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\UpdateCustomerCommand;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class UpdateCustomerCommandHandler implements
    CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private TagAwareCacheInterface $cache,
    ) {
    }

    public function __invoke(UpdateCustomerCommand $command): void
    {
        $previousEmail = $command->customer->getEmail();
        $command->customer->update($command->updateData);
        $this->repository->save($command->customer);

        $customerId = $command->customer->getUlid();
        $currentEmail = $command->customer->getEmail();

        $this->cache->invalidateTags([
            'customer.' . $customerId,
            'customer.email.' . hash('sha256', strtolower($currentEmail)),
        ]);

        if ($previousEmail !== $currentEmail) {
            $this->cache->invalidateTags([
                'customer.email.' . hash('sha256', strtolower($previousEmail)),
            ]);
        }
    }
}

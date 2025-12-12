<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\CommandHandler;

use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Domain\Repository\CustomerRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class CreateCustomerCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CustomerRepositoryInterface $repository,
        private TagAwareCacheInterface $cache,
    ) {
    }

    public function __invoke(CreateCustomerCommand $command): void
    {
        $this->repository->save($command->customer);
        $this->cache->invalidateTags([
            'customer.' . $command->customer->getUlid(),
            'customer.email.' . hash('sha256', strtolower($command->customer->getEmail())),
        ]);
    }
}

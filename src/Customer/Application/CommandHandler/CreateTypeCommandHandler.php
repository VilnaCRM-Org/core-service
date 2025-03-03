<?php

declare(strict_types=1);

namespace App\Customer\Application\CommandHandler;

use App\Customer\Domain\Repository\CustomerTypeRepositoryInterface;
use App\Shared\Domain\Bus\Command\CommandHandlerInterface;

class CreateTypeCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private CustomerTypeRepositoryInterface $customerTypeRepository,
        private UlidFactory $ulidFactory
    ) {
    }
}

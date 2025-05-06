<?php

declare(strict_types=1);

namespace App\Core\Customer\Application\Transformer;

use App\Core\Customer\Application\Command\CreateCustomerCommand;
use App\Core\Customer\Domain\Entity\Customer;
use App\Core\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Symfony\Component\Uid\Factory\UlidFactory;

final class CreateCustomerTransformer
{
    public function __construct(
        private CustomerFactoryInterface $customerFactory,
        private UlidTransformer $transformer,
        private UlidFactory $ulidFactory,
    ) {
    }

    public function transform(
        CreateCustomerCommand $command
    ): Customer {
        return $this->customerFactory->create(
            $command->initials,
            $command->email,
            $command->phone,
            $command->leadSource,
            $command->type,
            $command->status,
            $command->confirmed,
            $this->transformer->transformFromSymfonyUlid(
                $this->ulidFactory->create()
            )
        );
    }
}

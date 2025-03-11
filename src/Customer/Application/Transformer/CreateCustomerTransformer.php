<?php

declare(strict_types=1);

namespace App\Customer\Application\Transformer;

use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Shared\Domain\Bus\Command\CreateCustomerCommand;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Symfony\Component\Uid\Factory\UlidFactory;

final class CreateCustomerTransformer
{
    public function __construct(
        private CustomerFactoryInterface $customerFactory,
        private UlidTransformer $transformer,
        private UlidFactory $uuidFactory,
    ) {
    }

    public function transformToCustomer(
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
                $this->uuidFactory->create()
            )
        );
    }
}

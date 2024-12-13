<?php

declare(strict_types=1);

namespace App\Customer\Application\Transformer;

use App\Customer\Application\Command\CreateCustomerCommand;
use App\Customer\Domain\Entity\Customer;
use App\Customer\Domain\Factory\CustomerFactoryInterface;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use Symfony\Component\Uid\Factory\UuidFactory;

final readonly class CreateCustomerTransformer
{
    public function __construct(
        private CustomerFactoryInterface $customerFactory,
        private UuidTransformer          $transformer,
        private UuidFactory              $uuidFactory,
    ) {
    }

    public function transformToCustomer(CreateCustomerCommand $command): Customer
    {
        return $this->customerFactory->create(
            $this->transformer->transformFromSymfonyUuid(
                $this->uuidFactory->create()
            ),
            $command->initials,
            $command->email,
            $command->phone,
            $command->leadSource,
            $command->type,
            $command->status
        );
    }
}

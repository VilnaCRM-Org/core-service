<?php

declare(strict_types=1);

namespace App\Customer\Application\Transformer;

use App\Customer\Domain\Entity\CustomerType;
use App\Customer\Domain\Factory\CustomerTypeFactoryInterface;
use App\Shared\Domain\Bus\Command\CreateCustomerTypeCommand;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Symfony\Component\Uid\Factory\UlidFactory;

final class CreateTypeTransformer
{
    public function __construct(
        private CustomerTypeFactoryInterface $userFactory,
        private UlidTransformer $transformer,
        private UlidFactory $uuidFactory,
    ) {
    }

    public function transformToType(CreateCustomerTypeCommand $command): CustomerType
    {
        return $this->userFactory->create(
            $command->value,
            $this->transformer->transformFromSymfonyUuid(
                $this->uuidFactory->create()
            )
        );
    }
}

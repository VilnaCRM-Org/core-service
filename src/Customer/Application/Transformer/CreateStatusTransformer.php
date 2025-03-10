<?php

declare(strict_types=1);

namespace App\Customer\Application\Transformer;

use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Factory\CustomerStatusFactoryInterface;
use App\Shared\Domain\Bus\Command\CreateCustomerStatusCommand;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Symfony\Component\Uid\Factory\UlidFactory;

final class CreateStatusTransformer
{
    public function __construct(
        private CustomerStatusFactoryInterface $statusFactory,
        private UlidTransformer $transformer,
        private UlidFactory $uuidFactory,
    ) {
    }

    public function transformToStatus(CreateCustomerStatusCommand $command): CustomerStatus
    {
        return $this->statusFactory->create(
            $command->value,
            $this->transformer->transformFromSymfonyUuid(
                $this->uuidFactory->create()
            )
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Customer\Application\Transformer;

use App\Customer\Application\Command\CreateStatusCommand;
use App\Customer\Domain\Entity\CustomerStatus;
use App\Customer\Domain\Factory\StatusFactoryInterface;
use App\Shared\Infrastructure\Transformer\UlidTransformer;
use Symfony\Component\Uid\Factory\UlidFactory;

final class CreateStatusTransformer
{
    public function __construct(
        private StatusFactoryInterface $statusFactory,
        private UlidTransformer $transformer,
        private UlidFactory $uuidFactory,
    ) {
    }

    public function transformToStatus(
        CreateStatusCommand $command
    ): CustomerStatus {
        return $this->statusFactory->create(
            $command->value,
            $this->transformer->transformFromSymfonyUlid(
                $this->uuidFactory->create()
            )
        );
    }
}
